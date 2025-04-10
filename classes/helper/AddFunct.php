<?php

/**
 * @author Vladyslav Zhyokin
 */

/**
 * @brief Helper class providing static utility methods for the BIM system.
 *
 * This class contains static utility methods for common operations throughout
 * the BIM system, such as string manipulation, device management, UI preparation,
 * and file handling functions.
 * 
 * @class Helper_AddFunct
 */
class Helper_AddFunct
{
    /**
     * @brief Removes all whitespace from a string.
     *
     * Useful for sanitizing input strings like barcodes where whitespace
     * should be ignored for consistent matching.
     *
     * @param string|null $variable The string to process
     * @return string|null The string with all whitespace removed, or null if input was null
     * @static
     */
    public static function removeWhitespace($variable){
        if($variable)
        {
            return preg_replace('/\s+/', '', $variable);
        }
        return;
    }

    /**
     * @brief Returns an array of available device identifiers.
     *
     * Provides a standardized list of device identifiers used throughout the application.
     * The first element is a special "all" identifier, followed by individual device codes.
     *
     * @return array List of device identifiers
     * @static
     */
    public static function  devices(){
        return array("all","1r","2r", "3r", "4r", "1g", "2g","3g");
    }
    
    /**
     * @brief Validates if all provided devices exist in the system.
     *
     * Checks if each device in the provided array exists in the master list
     * of valid device identifiers.
     *
     * @param array $devices List of device identifiers to validate
     * @return int 1 if all devices are valid, 0 if any device is invalid
     * @static
     */
    public static function checkDevices($devices)
    {
        $devicesSource = self::devices();
        foreach ($devices as $device) {
            if(!in_array($device, $devicesSource))
            {
                return 0;
            }
        }
        return 1;
    }

    /**
     * @brief Handles the "all" device special case.
     *
     * If "all" is included in the devices array, returns a list of all
     * specific device identifiers. Otherwise, returns 1 to indicate
     * no special handling is needed.
     *
     * @param array $devices List of selected device identifiers
     * @return array|int Array of all specific device IDs or 1 if no special handling needed
     * @static
     */
    public static function deviceAll($devices){
        if(in_array("all", $devices))
        {
            return $devicesSource = array_slice(self::devices(),1);
        }
        else{
            return 1;
        }
    }

    /**
     * @brief Prepares device list data structure for display in templates.
     *
     * Creates an associative array with device names and selected status indicators
     * for use in UI elements like dropdown lists or checkboxes.
     *
     * @param array $devices Currently selected devices
     * @return array Associative array with "name" and "selected" keys containing parallel arrays
     * @static
     */
    public static function prepareListDeviceOnPage($devices){
        $listDevices = array("name" => array(), "selected" => array());
        foreach (array_slice(self::devices(),1) as $device) {
            if(in_array($device, $devices))
            {
                array_push($listDevices["name"],$device);
                array_push($listDevices["selected"],1);

            }
            else{
                array_push($listDevices["name"],$device);
                array_push($listDevices["selected"],0);
            }
        }
        return $listDevices;
    }

    /**
     * @brief Formats a string for display by adding separators.
     *
     * Takes a continuous string (like a barcode or product number) and formats it
     * with dots and spaces at specific intervals for improved readability.
     *
     * @param string|null $strToCat The string to format
     * @return string The formatted string with separators added
     * @static
     */
    public static function prepareStringOnPage($strToCat){
        $strToReturn = "";
        if(isset($strToCat))
        {
            $strToReturn = substr($strToCat, 0, 3).
                '.'.substr($strToCat, 3, 3).
                '.'.substr($strToCat, 6, 3).
                '.'.substr($strToCat, 9, 1).
                ' '.substr($strToCat, 10, 15);
        }
        return $strToReturn;
    }

    /**
     * @brief Retrieves assembly plan PDF files for a given barcode and subgroup.
     *
     * Scans the FTP directory for PDF files matching the provided barcode prefix
     * and subgroup. Sets the matching file paths in the page context object.
     *
     * @param string $barc3 First 3 digits of the barcode (directory identifier)
     * @param string $subgroup Subgroup identifier to match in filenames
     * @param object $pagec Page context object to update with file paths
     * @return void
     * @static
     */
    public static function getAssemblyPlanFiles($barc3, $subgroup, $pagec){        
        $directory = "ftp://serverpath/INSTRUKCJE/BIM/PDF";
        $paths = array_values(array_diff(scandir($directory."/".$barc3), array('..', '.', 'Thumbs.db')));
        $subgroup = str_replace(".","", $subgroup);
        if(isset($subgroup))
        {
            $subGroup = substr($subgroup,0,10);
            $pathsFiles = array();
            foreach ($paths as $path)
            {
                if(strpos($path,$subGroup)  !== false)
                {
                    array_push($pathsFiles,$path);
                }
            }            
            $pagec->pathsFiles = $pathsFiles;
        }
    }

    /**
     * @brief Converts a string to uppercase.
     *
     * Utility method that ensures consistent uppercase conversion for strings,
     * particularly for production line identifiers.
     *
     * @param string $chosenLine The string to convert to uppercase
     * @return string The uppercase version of the input string
     * @static
     */
    public static function stringToUpper($chosenLine)
    {
        return  strtoupper($chosenLine);
    }

}