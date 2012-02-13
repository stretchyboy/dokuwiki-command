<?php
/**
 * CommandEmbedding -- Base class for command embedding syntaxes.
 *
 * A command embedding is a type of syntax plugin whose syntax includes
 * a compliant call string and optional content.  A call string has the
 * following syntax, expressed in W3C-style BNF:
 *
 *   call_string  ::= command_name ('?' params)?
 *   command_name ::= name
 *   params       ::= param ('&' param)*
 *   param        ::= value | name '=' value?
 *   name         ::= [a-zA-Z] [a-zA-Z0-9_]*
 *   value        ::= [a-zA-Z0-9_\-.]+
 *
 * This class is a subclass of DokuWiki_Syntax_Plugin.  It implements handle()
 * and render() on behalf of the subclass, but the subclass is responsible for
 * implementing all other required methods of DokuWiki_Syntax_Plugin.
 *
 * @license    GPL 2 (http://www.gnu.org/licenses/gpl.html)
 * @author     Joe Lapp <http://www.spiderjoe.com>
 *
 * Modification history:
 *
 * 8/26/05 - Reverted back to 'substition' to permit recursion. JTL
 * 8/29/05 - Added $renderer parameter to runCommand(). JTL
 * 8/29/05 - Fixed failure to load extension for pre-cached data. JTL
 */
 
//// CONSTANTS ////////////////////////////////////////////////////////////////

require_once(DOKU_PLUGIN.'syntax.php');

if(!defined('COMMANDPLUGIN_DEFS'))
{
    define('COMMANDPLUGIN_EXT_PATH', DOKU_PLUGIN.'command/ext/');
    
    define('COMMANDPLUGIN_LEX_CMD', '[a-zA-Z][a-zA-Z0-9_]*');
    define('COMMANDPLUGIN_LEX_CMD_PARAMS', // lexer prohibits subpatterns
        COMMANDPLUGIN_LEX_CMD.'\?[a-zA-Z0-9_.\-=&]*');

    $TMP_NAME = '[a-zA-Z][a-zA-Z0-9_\-]*';
    $TMP_VCHAR = '[a-zA-Z0-9_.\-]';
    $TMP_PARAM = '('.$TMP_VCHAR.'+|'.$TMP_NAME.'\='.$TMP_VCHAR.'*)';
    define('COMMANDPLUGIN_CALL_PREG',
        '/^('.$TMP_NAME.')(\?'.$TMP_PARAM.'(&'.$TMP_PARAM.')*)?$/');

    define('COMMANDPLUGIN_NOT_FOUND', '##_COMMAND_NOT_FOUND_##');
    define('COMMANDPLUGIN_INVALID_SYNTAX', '##_INVALID_COMMAND_SYNTAX_##');

    define('COMMANDPLUGIN_DEFS', '*');
}
 
/******************************************************************************
  CommandEmbedding
******************************************************************************/

class CommandEmbedding extends DokuWiki_Syntax_Plugin
{
    //// POLYMORPHIC METHODS //////////////////////////////////////////////////

    /**
     * getEmbeddingType() returns a string that uniquely identifies the
     * particular command embedding and ideally also suggests the behavior
     * of the command embedding.  This string is passed to the command
     * extensions to allow them to behave in part according to the type of
     * embedding the command was called from.  A command typically only
     * uses this information if it outputs text for inclusion on the page.
     *
     * Each subclass must override and implement this method.
     */

    function getEmbeddingType()
    {
        return null; // subclass must return non-empty string
    }
    
    /**
     * parseCommandSyntax() takes a string that contains the entire text of
     * the command and parses out the call string and the content.  It returns
     * an array of the form array(callString, content).  This function cannot
     * return an error; it is presumed that if the lexer matched the syntax,
     * then the embedding is able to extract the call string and the content.
     *
     * Each subclass must override and implement this method.
     */
    
    function parseCommandSyntax($match)
    {
        return null; // subclass must return array(callString, content)
    }
    
    //// PUBLIC METHODS ///////////////////////////////////////////////////////

    function handle($match, $state, $pos, &$handler)
    {
        // Parse the command.

        $parse = $this->parseCommandSyntax($match);
        list($callString, $content) = $parse;
    
        $call = $this->parseCommandCall($callString);
        if($call == null)
            return array($state, array('=', COMMANDPLUGIN_INVALID_SYNTAX));
        
        $cmdName = $call[0];
        $params = $call[1];
        $paramHash = $call[2];
        
        // Load the command extension.
        
        if(!$this->loadExtension($cmdName))
            return array($state, array('=', COMMANDPLUGIN_NOT_FOUND));

        // Invoke the command.

        $embedding = $this->getEmbeddingType();
        $methodObj = $this->getMethodObject($cmdName, 'getCachedData',
                      '$p, $pH, $c, &$e', '"'.$embedding.'", $p, $pH, $c, $e');
        $errMsg = '';
        $cachedData = $methodObj($params, $paramHash, $content, $errMsg);
        if(!empty($errMsg))
            return array($state, array('=', '##'.$errMsg.'##'));
        
        // Return successful results.
        
        return array($state, array($cmdName, $cachedData));
    }
 
    function render($mode, &$renderer, $data)
    {
        if($mode == 'xhtml')
        {
            // Extract cached data.
        
            list($state, $match) = $data;
            list($cmdName, $cachedData) = $match;
            
            // Output data raw, as when command not found or error.
            
            if($cmdName == '=')
                $renderer->doc .= htmlspecialchars($cachedData);
                
            // Run the command with the cached data.
            
            else
            {
                if(!$this->loadExtension($cmdName))
                {
                    $renderer->doc .= COMMANDPLUGIN_NOT_FOUND;
                    return true;
                }  

                $embedding = $this->getEmbeddingType();
                $methodObj = $this->getMethodObject($cmdName, 'runCommand',
                                '$c, &$r, &$e', '"'.$embedding.'", $c, $r, $e');
                $errMsg = '';
                $output = $methodObj($cachedData, $renderer, $errMsg);
                if(!empty($errMsg))
                    $renderer->doc .= htmlspecialchars($errMsg);
                else if($output !== null)
                    $renderer->doc .= $output;
            }
            return true;
        }
        return false;
    }
    
    //// PRIVATE METHODS //////////////////////////////////////////////////////
    
    /**
     * parseCommandCall() validates and parses the call string portion of a
     * command.  This string includes the command name and all of its
     * parameters, but excludes the content and the bounding syntax.
     *
     * Returns an array of the form array(cmd, params, hash):
     *
     * cmd: The name of the command in lowercase.
     *
     * params: An array of parameters, indexed by their order of occurrence
     * in the parameter list.  If the parameter was an assignment (using '='),
     * the element will be an array of the form array(name, value).  If the
     * parameter was a simple value (no '='), the element will be a string
     * containing that value.
     *
     * hash: An associative array indexed by parameter name (for assignment
     * parameters) and by parameter value (for simple value parameters).  If
     * no value was assigned to a parameter, or if the parameter is itself
     * just a value, the value for the key is an empty string.  For example,
     * the call string "do?1.5&1.5&a&b=&c=2" produces array('1.5' => '',
     * 'a' => '', 'b' => '', 'c' => '1').
     *
     * If there are no parameters, the method returns array(cmd, null, null).
     */
    
    function parseCommandCall($callString)
    {
        $matches = array();
        if(!preg_match(COMMANDPLUGIN_CALL_PREG, $callString, $matches))
            return null; // syntax error
            
        $cmdName = strtolower($matches[1]);
        if(sizeof($matches) == 2)
            return array($cmdName, array(), array()); // no parameters

        $rawParams = explode('&', substr($matches[2], 1));
        $params = array();
        $paramHash = array();
        
        foreach($rawParams as $rawParam)
        {
            $equalsPos = strpos($rawParam, '=');
            if($equalsPos === false)
            {
                $params[] = $rawParam;
                $paramHash[$rawParam] = '';
            }
            else
            {
                $paramName = substr($rawParam, 0, $equalsPos);
                // next line works even if '=' is last char
                $paramValue = substr($rawParam, $equalsPos + 1);
                $params[] = array($paramName, $paramValue);
                $paramHash[$paramName] = $paramValue;
            }
        }
        return array($cmdName, $params, $paramHash);
    }
    
    /**
     * loadExtension() loads the command extension.
     */
     
    function loadExtension($cmdName)
    {
        $extFile = COMMANDPLUGIN_EXT_PATH.$cmdName.'.php';
        if(!file_exists($extFile)) // PHP caches the results, fortunately
            return false;
        
        require_once($extFile);
        return true;
    }
    
    /**
     * getMethodObject() returns a method object that the caller may use to
     * invoke a method.  The method object is cached as a function of
     * command name and method name so that multiple calls use the same
     * method object, saving clock cycles.
     */
    
    function &getMethodObject($cmdName, $methodName, $methodSig, $callArgs)
    {
        static $methodObjects = array();
    
        $key = $cmdName.'*'.$methodName;
        if(isset($methodObjects[$key]))
            return $methodObjects[$key];
        
        $className = 'CommandPluginExtension_'.$cmdName;
        $methodObj = create_function($methodSig,
                       'return '.$className.'::'.$methodName.'('.$callArgs.');');
        $methodObjects[$key] =& $methodObj;
        return $methodObj;
    }
}

?>