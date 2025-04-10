<?php

/**
 * @author Vladyslav Zhyokin
 */

/**
 * @brief The main controller in this project.
 *
 * Class responsible for handling the primary application flow and controls.
 * Manages request environment, displays instruction data, and provides
 * barcode lookup functionality for the Barcode Instruction Management (BIM) system.
 * 
 * @class Controllers_IndexControl
 * @extends W_Controller_Controller
 */
class Controllers_IndexControl extends W_Controller_Controller {
    
    /**
     * @brief Initializes the controller.
     * 
     * This method is called before any action is executed.
     * Override this method to perform setup operations.
     *
     * @return void
     * @access public
     */
    public function init(){

    }

    /**
     * @brief Main index action for the controller.
     * 
     * Prepares device options for multi-select dropdown elements
     * on the main page.
     *
     * @return void
     * @access public
     */
    public function indexAction()
    {
        $this->pagec->devicesForMultiselect = Helper_AddFunct::devices();
    }

    /**
     * @brief Finds barcodes matching a partial string.
     * 
     * Handles AJAX requests to search for barcodes that match
     * the provided input string. Removes whitespace from input
     * for consistent matching.
     *
     * @param array $_POST["barc4"] The barcode search string
     * @return void Sets $this->pagec->findBarcode with search results
     * @access public
     */
    public function findBarcodeAction(){
        $barc4 = $_POST["barc4"];
        $barc4 = Helper_AddFunct::removeWhitespace($barc4);
        $findBarcode = $this->sqlc->findBarcode($barc4);
        $this->pagec->findBarcode = $findBarcode['BARC4'];
    }

    /**
     * @brief Finds new barcodes matching a partial string.
     * 
     * Similar to findBarcodeAction but specifically for the "new barcode" 
     * field. Removes whitespace from input for consistent matching.
     *
     * @param array $_POST["newbarc4"] The new barcode search string
     * @return void Sets $this->pagec->findnewBarcode with search results
     * @access public
     */
    public function findnewBarcodeAction(){
        $newbarc4 = $_POST["newbarc4"];
        $newbarc4 = Helper_AddFunct::removeWhitespace($newbarc4);
        $findnewBarcode = $this->sqlc->findnewBarcode($newbarc4);
        $this->pagec->findnewBarcode = $findnewBarcode['BARC4'];
    }

    /**
     * @brief Sets up a module table with component data.
     * 
     * Creates a standardized array structure for SMD component 
     * positioning data. For positions that exist in the input data,
     * copies the values; for missing positions, fills with null values.
     *
     * @param int $maxPos Maximum position index to include in the table
     * @param array $tableSmdMap Raw SMD map data from database
     * @return array Standardized and complete module table with all positions
     * @access private
     */
    private function setModule($maxPos, $tableSmdMap){
        $tableSmdMapFullTable = array("MODULE_NUMBER" => array(),
            "POSITION_ON_TABLE" => array(),
            "PART_NUMBER" => array(),
            "WIDTH" => array(),
            "BOUND" => array(),
            "QUANTITY" => array(),
            "BARCODE"  => array()
        );

        for($mtp = 0; $mtp <=$maxPos; $mtp++)
        {
            if (in_array($mtp, $tableSmdMap["POSITION_ON_TABLE"])) {
                $key = array_search($mtp, $tableSmdMap["POSITION_ON_TABLE"]);
                $tableSmdMapFullTable["MODULE_NUMBER"][$mtp] = $tableSmdMap["MODULE_NUMBER"][$key];
                $tableSmdMapFullTable["POSITION_ON_TABLE"][$mtp] = $tableSmdMap["POSITION_ON_TABLE"][$key];
                $tableSmdMapFullTable["PART_NUMBER"][$mtp] = $tableSmdMap["PART_NUMBER"][$key];
                $tableSmdMapFullTable["WIDTH"][$mtp] = $tableSmdMap["WIDTH"][$key];
                $tableSmdMapFullTable["BOUND"][$mtp] = $tableSmdMap["BOUND"][$key];
                $tableSmdMapFullTable["QUANTITY"][$mtp] = $tableSmdMap["QUANTITY"][$key];
                $tableSmdMapFullTable["BARCODE"][$mtp] = $tableSmdMap["BARCODE"][$key];
            }
            else{
                $tableSmdMapFullTable["MODULE_NUMBER"][$mtp] = null;
                $tableSmdMapFullTable["POSITION_ON_TABLE"][$mtp] = null;
                $tableSmdMapFullTable["PART_NUMBER"][$mtp] = null;
                $tableSmdMapFullTable["WIDTH"][$mtp] = null;
                $tableSmdMapFullTable["BOUND"][$mtp] = null;
                $tableSmdMapFullTable["QUANTITY"][$mtp] = null;
                $tableSmdMapFullTable["BARCODE"][$mtp] = null;
            }
        }
        return $tableSmdMapFullTable;
    }

    /**
     * @brief Prepares session data for the EWA subsystem.
     * 
     * Sets up session variables required by the EWA (Electronic Work Assistant)
     * system by creating standardized area identifiers from the chosen line.
     *
     * @param string $chosenLine The production line identifier
     * @return void Session variables are set directly
     * @access private
     */
    private function prepeareSessionForEwa($chosenLine){
        $chosenLineUpperText = Helper_AddFunct::stringToUpper($chosenLine);
        $pastNameLine = "WPL";
        $_SESSION['EWA']['AreaInfo']['area'] = $pastNameLine.$chosenLineUpperText;
        $_SESSION['EWA']['AreaInfo']['areaRemark'] = $pastNameLine.$chosenLineUpperText;
    }

    /**
     * @brief Retrieves and prepares instruction data for display.
     * 
     * This complex method handles the main data loading for instruction views:
     * 1. Validates and processes input parameters (barcode, side, devices)
     * 2. Loads general information, notes, print programs, and machine programs
     * 3. Retrieves SMD component mapping information for selected modules
     * 4. Formats and prepares all data for template display
     *
     * @param array $_POST Required parameters:
     *        - barc4: string - Barcode identifier
     *        - side: int - Side identifier (1 or 2)
     *        - devices: string - Comma-separated list of device lines
     * 
     * @return void Sets multiple template variables via $this->pagec
     * @access public
     */
    public function getDataInstructionsAction()
    {
        $barc4 = $_POST["barc4"];
        $barc4 = Helper_AddFunct::removeWhitespace($barc4);
        $this->pagec->side = $side = $_POST["side"];
        $chosenLine = $_POST["devices"];
        $devices = explode(",",$chosenLine); // Lines sent via POST

        $docNr = $this->sqlc->getDocNrByBarcode($barc4, $side);
        $this->pagec->docNr = $docNr;

        $checkDevices = Helper_AddFunct::checkDevices($devices); // Checks if such line exists
        $deviceAll = Helper_AddFunct::deviceAll($devices); // Lines without "all"

        if($checkDevices)
        {
            if($deviceAll != 1)
            {
                $devices = $deviceAll;
            }
            $this->prepeareSessionForEwa($chosenLine);
            $this->pagec->devices = Helper_AddFunct::prepareListDeviceOnPage($devices); // Marks lines as selected
            $findWpn = $this->sqlc->findBarcode($barc4);
            $this->pagec->findWpn = Helper_AddFunct::prepareStringOnPage($findWpn["WABCONR"][0]);
            $lG = $this->sqlc->loadGeneralInformation($barc4, $side);
            $lG["SUBGROUP"][0] = Helper_AddFunct::prepareStringOnPage(isset($lG["SUBGROUP"][0]) ? $lG["SUBGROUP"][0] : "");
            $lG["TILE"][0] = Helper_AddFunct::prepareStringOnPage(isset($lG["TILE"][0]) ? $lG["TILE"][0] : "");
            $this->pagec->loadGeneralInformation = $lG;
            $scsInf = $this->sqlc->searchAdditionalInfScs($side, $barc4);
            $scsInf["STENCIL_NR"][0] = Helper_AddFunct::prepareStringOnPage(isset($scsInf["STENCIL_NR"][0]) ? $scsInf["STENCIL_NR"][0] : "");

            $this->pagec->scsInf = $scsInf;
            $this->pagec->notes = $notes = $this->sqlc->notes($barc4, $side);
            $this->pagec->printProg = $printProg = $this->sqlc->printProg($barc4, $side, $devices);
            $this->pagec->dockPro = $dockPro = $this->sqlc->dockPro($barc4, $side, $devices);
            $this->pagec->barc3 = $barc3 = substr($barc4,0,3);
            $this->pagec->headerSmdMap = $headerSmdMap = $this->sqlc->headerSmdMap($barc4, $side, $chosenLine);
            $map_id = isset($headerSmdMap["MAP_ID"][0]) ? $headerSmdMap["MAP_ID"][0] : 0;

            if($devices != "all" and $map_id != 0)
            {
                $tableSmdMapM1 = $this->sqlc->tableSmdMap(1, $map_id);
                $tableSmdMapM2 = $this->sqlc->tableSmdMap(2, $map_id);
                $tableSmdMapM3 = $this->sqlc->tableSmdMap(3, $map_id);
                $tableSmdMapM4 = $this->sqlc->tableSmdMap(4, $map_id);
                $tableSmdMapM5 = $this->sqlc->tableSmdMap(5, $map_id);
                $tableSmdMapM6 = $this->sqlc->tableSmdMap(6, $map_id);
                $tableSmdMapM7 = $this->sqlc->tableSmdMap(7, $map_id);

                $this->pagec->m1 = $this->setModule(20, $tableSmdMapM1);
                $this->pagec->m2 = $this->setModule(20, $tableSmdMapM2);
                $this->pagec->m3 = $this->setModule(20, $tableSmdMapM3);
                $this->pagec->m4 = $this->setModule(20, $tableSmdMapM4);
                $this->pagec->m5 = $this->setModule(45, $tableSmdMapM5);
                $this->pagec->m6 = $this->setModule(45, $tableSmdMapM6);
                $this->pagec->m7 = $this->setModule(45, $tableSmdMapM7);
            }

            isset($lG["SUBGROUP"][0]) ? Helper_AddFunct::getAssemblyPlanFiles($barc3, $lG["SUBGROUP"][0], $this->pagec) : "";
        }
    }
}