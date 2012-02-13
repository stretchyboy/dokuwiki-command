<?php

/******************************************************************************
CommandPluginExtension - Base class for a command of the Command Plugin.

Each command is a subclass of CommandPluginExtension.  The name of a subclass
must be of the form CommandPluginExtension_<command>, where <command> is the
name of the command as it occurs in the syntax, in lowercase letters.  Each
subclass must go in its own file, a file with name <command>.php.

The behavior of a command may vary according to how the command was embedded
in the text.  The Commmand Plugin provides two embedding modes: inline and
block.  When embedding inline, any replacement text that the command produces
will always appear within an HTML paragraph.  When block-embedding, the
replacement text is guaranteed to occur outside of a paragraph.  In commands
that are sensibly placed in either an HTML div or span, the commmand may
output a div or a span according to the embedding mode chosen.

The class provides two methods.  getCachedData() is a stub that each command
must override with its own implementation.  runCommand() by default causes
the command to be replaced with the string returned by getCachedData() and 
only needs to be overridden if this isn't the desired behavior.

The Command Plugin never instantiates this class.  Both methods are static
methods and the $this variable is not available for use.

NOTICE: COMMANDS THAT OUTPUT USER-PROVIDED CONTENT SHOULD BE CAREFUL TO ESCAPE
SPECIAL HTML CHARACTERS FOUND IN THAT CONTENT, unless of course, the purpose
of the command is to allow the user to embed HTML of his/her choosing.

See http://www.splitbrain.org/plugin:command for more information.

@license    GPL 2 (http://www.gnu.org/licenses/gpl.html)
@author     Joe Lapp <http://www.spiderjoe.com>

Modification history:

8/29/05 - Added $renderer parameter to runCommand(). JTL
******************************************************************************/

class CommandPluginExtension
{
    /**
     * getCachedData() pre-processes a command to produce data that can be
     * cached.  DokuWiki pre-compiles its pages to extract the information
     * that does not change each time the page is generated.  This method
     * provides that information.  Usually a command that only produces
     * replacement text can produce all of that replacement text in its
     * final form during this method and then return it for caching.
     *
     * This method is also the only means by which parameters and content are
     * handed to the commmand, so if these values cannot be processed until
     * the page is actually generated, the method will be responsible for
     * returning them (or some aspect of them) to be cached.
     * 
     * The $params and $paramHash arguments of this method contain the
     * parameters that the user provided to the command.  $params is an array
     * that describes all of the parameters, fully characterizing exactly what
     * the user provided.  $paramHash is a convenience argument that makes it
     * easy to get to certain parameter values, but which which loses some
     * information that is only avaiable in $params.
     *
     * This method does nothing by default and must be overridden to
     * implement the command, or at least to cache any needed arguments.
     *
     * This method is the Command Plugin analogue of the DokuWiki syntax
     * plugin's handle() method.
     *
     * @param string $embedding 'inline' or 'block'
     * @param array $params An array of the command's parameters, indexed by
     *      their order of occurrence in the parameter list.  If the parameter
     *      was an assignment (using '='), its element will be an array of the
     *      form array(name, value).  If the parameter was a simple value (no
     *      '='), its element will be a string containing that value.  If
     *      there are no parameters, $params will be an empty array.
     * @param array $paramHash An associative array indexed by parameter name
     *      (for assignment parameters) and by parameter value (for simple
     *      value parameters).  If no value was assigned to a parameter, or if
     *      the parameter is itself just a value, the value for the index key
     *      is an empty string.  For example, "?1&1&2&a&b=&c=3&d=4&d=5"
     *      produces array('1'=>'', '2'=>'', 'a'=>'', 'b'=>'', 'c'=>'3',
     *      'd'=>'5').
     * @param string $content The content that the user provided to the
     *      command.  Empty string if there was no content.
     * @param reference $errorMessage The method may set this variable to a
     *      non-empty string to report an error and provide an error message.
     *      If the method does this, this error message will be output into
     *      the text, replacing the commmand, and runCommand() is not called.
     * @return object The method may return any object that it would like
     *      cached for later use by runCommand().  The method may even return
     *      null.  If the command is using the default implementation of
     *      runCommand(), getCachedData() must return a string containing the
     *      replacement text for the command, unless the method reports an
     *      error by setting $errorMessage.  If the returned value is the
     *      replacement text, THIS METHOD SHOULD FIRST ESCAPE ANY SPECIAL
     *      HTML CHARACTERS THAT THE USER MAY HAVE PROVIDED.
     */

    function getCachedData($embedding, $params, $paramHash, $content,
                             &$errorMessage) // STATIC
    {
        return null;
    }
    
    /**
     * runCommand() implements the behavior that must occur each time the page
     * is generated and to return the replacement text for the command.
     *
     * The default behavior is to return the string that getCachedData()
     * returned, thus relegating the responsibility for producing the
     * replacement text to getCachedData().  This behavior is appropriate
     * when the command produces static text that can be cached in its
     * entirety.  A command should only override the default implementation
     * if some aspect of the command is dynamic.
     *
     * This method is the Command Plugin analogue of the DokuWiki syntax
     * plugin's render() method.
     *
     * @param string $embedding 'inline' or 'block'
     * @param object $cachedData This is the value that getCachedData()
     *      returned for the command, which may be null.
     * @param reference $errorMessage The method may set this variable to a
     *      non-empty string to report an error and provide an error message.
     *      If the method does this, this error message will be output into
     *      the text, serving as the replacement text for the command.
     * @param reference $renderer This is the renderer object passed by the
     *      parser to the plugin's render() method.  Some commands may need
     *      to access this object.  It is possible to output text via the
     *      renderer, but the function should return text instead.
     * @return string Replacement text for the command.  The replacement text
     *      may be either null or the empty string, which are equivalent.
     *      The return value is ignored if the method reported an error by
     *      setting $errorMessage.  If the method returns replacement text
     *      and getCachedData() has not already escaped special HTML
     *      characters, THIS METHOD SHOULD FIRST ESCAPE ANY SPECIAL HTML
     *      CHARACTERS THAT THE USER MAY HAVE PROVIDED.
     */
    
    function runCommand($embedding, $cachedData, &$renderer,
                          &$errorMessage) // STATIC
    {
        return $cachedData;
    }
}
?>