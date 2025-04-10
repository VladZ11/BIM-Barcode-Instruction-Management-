<?php

/**
 * @author Vladyslav Zhyokin
 */

/**
 * @brief Class responsible for rendering main application pages.
 *
 * This class manages the display and rendering of all templates related to
 * the main interface of the Barcode Instruction Management (BIM) system.
 * It provides methods for displaying the home page, search results,
 * and instruction data views.
 *
 * @class pagecreator_indexPage
 * @extends W_pagecreator_PageCreator
 */
class pagecreator_indexPage extends W_pagecreator_PageCreator{
    
    /**
     * @brief Renders the main application page.
     * 
     * Displays the primary template for the BIM system home page
     * where users can search for barcodes and view instructions.
     *
     * @return void
     * @access public
     */
    public function indexAction(){
        $this->Display("index.htpl");
    }
    
    /**
     * @brief Renders barcode search results.
     * 
     * Displays only the content portion of the template with
     * list of barcodes matching the search criteria.
     *
     * @return void
     * @access public
     */
    public function findBarcodeAction(){
        $this->DisplayOnlyContent("parts/listBarcodes.htpl");
    }

    /**
     * @brief Renders new barcode search results.
     * 
     * Displays only the content portion of the template with
     * list of new barcodes matching the search criteria.
     *
     * @return void
     * @access public
     */
    public function findnewBarcodeAction(){
        $this->DisplayOnlyContent("parts/newlistBarcodes.htpl");
    }

    /**
     * @brief Renders instruction data for viewing.
     * 
     * Displays only the content portion of the template with
     * detailed instruction data for the selected barcode and side.
     * This is the main view for users to see instruction details.
     *
     * @return void
     * @access public
     */
    public function getDataInstructionsAction(){
        $this->DisplayOnlyContent("parts/searchedData.htpl");
    }
}