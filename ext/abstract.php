<?php
/**
 * Date/time Command: Formats a date/time to a pre-configured format.
 *
 * For a full description of this Command Plugin command, see:
 *   http://www.splitbrain.org/plugin:date-time
 *
 * @license    GPL 2 (http://www.gnu.org/licenses/gpl.html)
 * @author     Joe Lapp <http://www.spiderjoe.com>
 */
 
class CommandPluginExtension_abstract extends CommandPluginExtension
{
    function getCachedData($embedding, $params, $paramHash, $content,
                             &$errorMessage) // STATIC
    {
      $len = 0;
        if(sizeof($params) == 1 && ($params[0]))
            $len = $params[0];
        else if(sizeof($params) != 0)
        {
            $errorMessage = "_INVALID_DT_PARAMETERS_";
            return null; // return value doesn't matter in this case
        }
        $content = trim($content);

        if($len && $len < strlen($content)){
            $content = utf8_substr($content, 0, $len).'…';
        }
       
        if($embedding == 'block')
        {
                return '<div>'.$content.'</div>';
        }
        return $content;
    }
}
?>