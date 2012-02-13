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
 
class CommandPluginExtension_dt extends CommandPluginExtension
{
    function getCachedData($embedding, $params, $paramHash, $content,
                             &$errorMessage) // STATIC
    {
        global $conf;

        // Determine the name of the configuration variable.

        $configName = 'dtformat';
        if(sizeof($params) == 1 && is_string($params[0]))
            $configName .= '_'.$params[0];
        else if(sizeof($params) != 0)
        {
            $errorMessage = "_INVALID_DT_PARAMETERS_";
            return null; // return value doesn't matter in this case
        }

        // Load the css class and the date format from the variable.
    
        $cssClass = null;
        $format = null;

        $configVal = null;
        $configVal = @$conf[$configName];
        
        if($configVal != null)
        {
            $barPos = strpos($configVal, '|');
            if($barPos === false)
                $format = $configVal;
            else
            {
                $cssClass = substr($configVal, 0, $barPos);
                // next line works even if there is no format
                $format = substr($configVal, $barPos + 1);
            }
        }
        
        // Format the date/time.
        
        if(!empty($format))
        {
            if(trim($content) == '')
                $newDT = date($format);
            else
                $newDT = date($format, strtotime($content));
        }
        else
            $newDT = $content;
        
        $newDT = htmlspecialchars($newDT);

        // Return the newly formatted date/time.

        if($embedding == 'block')
        {
            if($cssClass)
                return '<div class=\''.$cssClass.'\'>'.$newDT.'</div>';
            return '<div>'.$newDT.'</div>';
        }
        else if($cssClass)
            return '<span class=\''.$cssClass.'\'>'.$newDT.'</span>';
        return $newDT;
    }
}
?>