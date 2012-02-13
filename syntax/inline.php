<?php
/**
 * Inline Command Plugin - Perform a command whose output is embedded inline.
 *
 * See the plugin's wiki documentation for information on creating commands:
 *   http://wiki.splitbrain.org/plugin:command
 *
 * @license    GPL 2 (http://www.gnu.org/licenses/gpl.html)
 * @author     Joe Lapp <http://www.spiderjoe.com>
 *
 * Modification history:
 *
 * 8/26/05 - Reverted back to 'substition'. JTL
 */

if(!defined('DOKU_INC'))
    define('DOKU_INC',realpath(dirname(__FILE__).'/../../../').'/');
if(!defined('DOKU_PLUGIN'))
    define('DOKU_PLUGIN',DOKU_INC.'lib/plugins/');
if(!defined('COMMANDPLUGIN_PATH'))
    define('COMMANDPLUGIN_PATH', DOKU_PLUGIN.'command/');
require_once(COMMANDPLUGIN_PATH.'inc/embedding.php');
require_once(COMMANDPLUGIN_PATH.'inc/extension.php');

class syntax_plugin_command_inline extends CommandEmbedding
{
    function getInfo()
    {
        return array(
            'author' => 'Joe Lapp',
            'email'  => '',
            'date'   => '2005-08-21',
            'name'   => 'Inline Command',
            'desc'   => 'Perform a command whose output is embedded inline',
            'url'    => 'http://wiki.splitbrain.org/plugin:command',
        );
    }
 
    function getType()
        { return 'substition'; }
        
    function getSort()
        { return 500; } // until I learn better
    
    function connectTo($mode)
    {
        // Because the lexer doesn't accept subpatterns, we'll match invalid
        // parameter sets, provided that the parameter characters are each
        // individually valid.  The plugin will however report an error.
        
        $prefix = '\%';
        $suffix = '\x28.*?\x29\%';

        $this->Lexer->addSpecialPattern(
                $prefix.COMMANDPLUGIN_LEX_CMD.$suffix,
                $mode, 'plugin_command_inline');
        $this->Lexer->addSpecialPattern(
                $prefix.COMMANDPLUGIN_LEX_CMD_PARAMS.$suffix,
                $mode, 'plugin_command_inline');
    }

    function getEmbeddingType()
    {
        return 'inline';
    }
    
    function parseCommandSyntax($match)
    {
        $parenPos = strpos($match, '(');
        $callString = substr($match, 1, $parenPos - 1);
        // next line works even if there is no content
        $content = substr($match, $parenPos + 1, -2);
        return array($callString, $content);
    }
}

?>