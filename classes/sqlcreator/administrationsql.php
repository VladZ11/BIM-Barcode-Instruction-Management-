<?php

/**
 * @author Vladyslav Zhyokin
 */

/**
 * @brief SQL creator class for administration operations in the BIM system.
 *
 * This class manages database interactions for the administration module of the
 * Barcode Instruction Management (BIM) system. It provides methods for managing
 * notes, images, program configurations, and instruction data. The class handles
 * CRUD operations (Create, Read, Update, Delete) for various entities including
 * general information records, header data, print programs, dock programs, and notes.
 * 
 * @class SqlCreator_AdministrationSql
 * @extends W_sqlcreator_SQLCreator
 */
class SqlCreator_AdministrationSql extends W_sqlcreator_SQLCreator {

    /**
     * @brief Deletes the image name from a note by setting IMG_NOTE to null.
     *
     * Updates a specific note record to remove the image reference while
     * preserving the rest of the note data.
     *
     * @param int $id_note The ID of the note to update
     * @return int|false The number of affected rows or false on error
     * @access public
     */
    public function deleteNameImageFromNote($id_note) {
        try {
            // Prepare SQL query to update IMG_NOTE to null for the given ID_NOTE
            $sql = "UPDATE BIM_SMD_IM_NOTE 
                    SET IMG_NOTE = null
                    WHERE ID_NOTE = :id_note";
            $params = array(':id_note' => $id_note);
            $res = array();
            // Execute the SQL query
            $this->WPLQDM_Manager->doSql($sql, $res, $params, true);
            // Return the number of affected rows
            return $this->WPLQDM_Manager->get_numrow();
        } catch (Exception $e) {
            return false;
        }
    }

    /**
     * @brief Retrieves the image name from a note.
     *
     * Gets the image filename associated with a specific note
     * identified by its ID.
     *
     * @param int $id_note The ID of the note to query
     * @return string|false The image filename or false on error
     * @access public
     */
    public function getNameNoteImage($id_note) {
        try {
            $sql = "SELECT IMG_NOTE FROM BIM_SMD_IM_NOTE WHERE ID_NOTE = :id_note";
            $params = array(':id_note' => $id_note);
            $res = array();
            $this->WPLQDM_Manager->doSql($sql, $res, $params);
            // Return the image name
            return $res["IMG_NOTE"][0];
        } catch (Exception $e) {
            return false;
        }
    }

    /**
     * @brief Retrieves typical notes along with their categories.
     *
     * Gets all typical comment templates along with their associated categories
     * by joining the typical comment table with the category table.
     *
     * @return array|false Array of typical comments with categories or false on error
     * @access public
     */
    public function getTypicalNotes() {
        try {
            $sql = "SELECT * FROM BIM_SMD_IM_TYPICAL_COMMENT tc
                    INNER JOIN BIM_SMD_IM_CATEGORY_NOTE cn ON tc.CATEGORY = cn.ID";
            $res = array();
            $this->WPLQDM_Manager->doSql($sql, $res);
            // Return the result set
            return $res;
        } catch (Exception $e) {
            return false;
        }
    }

    /**
     * @brief Updates notes by setting the parent note ID.
     *
     * Updates all notes that contain the specified text pattern
     * to associate them with a specific parent note template.
     *
     * @param int $id_parent_note The parent note ID to set
     * @param string $note The note text pattern to search for
     * @return int|false The number of affected rows or false on error
     * @access public
     */
    public function updateNotes($id_parent_note, $note) {
        try {
            $sql = "UPDATE BIM_SMD_IM_NOTE 
                    SET ID_PARENT_NOTE = :id_parent_note
                    WHERE NOTE LIKE :note";
            $params = array(':id_parent_note' => $id_parent_note, ':note' => '%' . $note . '%');
            $res = array();
            $this->WPLQDM_Manager->doSql($sql, $res, $params, true);
            // Return the number of affected rows
            return $this->WPLQDM_Manager->get_numrow();
        } catch (Exception $e) {
            return false;
        }
    }

    /**
     * @brief Updates the image name in notes.
     *
     * Associates a specific image filename with a note identified by its ID.
     * Used after uploading an image for a note.
     *
     * @param int $id_row The ID of the note to update
     * @param string $imgName The image filename to associate with the note
     * @return int|false The number of affected rows or false on error
     * @access public
     */
    public function updateImageInNotes($id_row, $imgName) {
        try {
            $sql = "UPDATE BIM_SMD_IM_NOTE 
                    SET IMG_NOTE = :imgName
                    WHERE ID_NOTE = :id_row";
            $params = array(':id_row' => $id_row, ':imgName' => $imgName);
            $res = array();
            $this->WPLQDM_Manager->doSql($sql, $res, $params, true);
            // Return the number of affected rows
            return $this->WPLQDM_Manager->get_numrow();
        } catch (Exception $e) {
            return false;
        }
    }

    /**
     * @brief Deletes general information for a given barcode and side.
     *
     * Removes the general information record for a specific barcode and PCB side
     * from the database.
     *
     * @param string $barc4 The barcode identifier
     * @param int $side The side identifier (1 or 2)
     * @return int|false The number of affected rows or false on error
     * @access public
     */
    public function deleteGeneralInformation($barc4, $side) {
        try {
            $params = array(":barc4" => $barc4, ':side' => $side);
            $sql = "DELETE FROM BIM_SMD_IM_GEN_INFORM 
                    WHERE barc4 = :barc4 AND SIDE = :side";
            $res = array();
            $this->WPLQDM_Manager->doSql($sql, $res, $params, false);
            // Return the number of affected rows
            return $this->WPLQDM_Manager->get_numrow();
        } catch (Exception $e) {
            return false;
        }
    }

    /**
     * @brief Deletes header information for a given barcode and side.
     *
     * Removes the header information record for a specific barcode and PCB side
     * from the database.
     *
     * @param string $barc4 The barcode identifier
     * @param int $side The side identifier (1 or 2)
     * @return int|false The number of affected rows or false on error
     * @access public
     */
    public function deleteHeaderInformation($barc4, $side) {
        try {
            $params = array(":barc4" => $barc4, ':side' => $side);
            $sql = "DELETE FROM BIM_SMD_IM_HEADER 
                    WHERE barc4 = :barc4 AND SIDE = :side";
            $res = array();
            $this->WPLQDM_Manager->doSql($sql, $res, $params, false);
            // Return the number of affected rows
            return $this->WPLQDM_Manager->get_numrow();
        } catch (Exception $e) {
            return false;
        }
    }

    /**
     * @brief Deletes print programs for a given barcode and side.
     *
     * Removes all print program records for a specific barcode and PCB side
     * from the database.
     *
     * @param string $barc4 The barcode identifier
     * @param int $side The side identifier (1 or 2)
     * @return int|false The number of affected rows or false on error
     * @access public
     */
    public function deletePrintProg($barc4, $side) {
        try {
            $params = array(":barc4" => $barc4, ':side' => $side);
            $sql = "DELETE FROM BIM_SMD_IM_DEV_PRINT_PROG 
                    WHERE barc4 = :barc4 AND SIDE = :side";
            $res = array();
            $this->WPLQDM_Manager->doSql($sql, $res, $params, false);
            // Return the number of affected rows
            return $this->WPLQDM_Manager->get_numrow();
        } catch (Exception $e) {
            return false;
        }
    }

    /**
     * @brief Deletes dock programs for a given barcode and side.
     *
     * Removes all dock program records for a specific barcode and PCB side
     * from the database.
     *
     * @param string $barc4 The barcode identifier
     * @param int $side The side identifier (1 or 2)
     * @return int|false The number of affected rows or false on error
     * @access public
     */
    public function deletDockProg($barc4, $side) {
        try {
            $params = array(":barc4" => $barc4, ':side' => $side);
            $sql = "DELETE FROM BIM_SMD_IM_DOCK_DEV_PRO 
                    WHERE barc4 = :barc4 AND SIDE = :side";
            $res = array();
            $this->WPLQDM_Manager->doSql($sql, $res, $params, false);
            // Return the number of affected rows
            return $this->WPLQDM_Manager->get_numrow();
        } catch (Exception $e) {
            return false;
        }
    }

    /**
     * @brief Deletes notes for a given barcode and side.
     *
     * Removes all note records for a specific barcode and PCB side
     * from the database.
     *
     * @param string $barc4 The barcode identifier
     * @param int $side The side identifier (1 or 2)
     * @return int|false The number of affected rows or false on error
     * @access public
     */
    public function deletNotes($barc4, $side) {
        try {
            $params = array(":barc4" => $barc4, ':side' => $side);
            $sql = "DELETE FROM BIM_SMD_IM_NOTE 
                    WHERE barc4 = :barc4 AND SIDE = :side";
            $res = array();
            $this->WPLQDM_Manager->doSql($sql, $res, $params, false);
            // Return the number of affected rows
            return $this->WPLQDM_Manager->get_numrow();
        } catch (Exception $e) {
            return false;
        }
    }

    /**
     * @brief Retrieves images from notes where IMG_NOTE is not null.
     *
     * Gets a list of all image filenames associated with notes for a specific
     * barcode and PCB side.
     *
     * @param string $barc4 The barcode identifier
     * @param int $side The side identifier (1 or 2)
     * @return array|false Array of image filenames or false on error
     * @access public
     */
    public function getImageFromNotes($barc4, $side) {
        try {
            $params = array(":barc4" => $barc4, ':side' => $side);
            $sql = "SELECT IMG_NOTE FROM BIM_SMD_IM_NOTE 
                    WHERE barc4 = :barc4 AND SIDE = :side AND img_note IS NOT NULL";
            $res = array();
            $this->WPLQDM_Manager->doSql($sql, $res, $params);
            // Return the array of image names
            return $res["IMG_NOTE"];
        } catch (Exception $e) {
            return false;
        }
    }

    /**
     * @brief Deletes a specific print program for a given barcode, side, and line.
     *
     * Removes a print program record for a specific barcode, PCB side, and
     * production line from the database.
     *
     * @param string $barc4 The barcode identifier
     * @param int $side The side identifier (1 or 2)
     * @param string $program Unused parameter (not used in the query)
     * @param string $line The production line identifier
     * @return int|false The number of affected rows or false on error
     * @access public
     */
    public function deletePrintProgIM($barc4, $side, $program, $line) {
        try {
            $params = array(":barc4" => $barc4, ':side' => $side, ':line' => $line);
            $sql = "DELETE FROM BIM_SMD_IM_DEV_PRINT_PROG 
                    WHERE barc4 = :barc4 AND SIDE = :side AND LINE = :line";
            $res = array();
            $this->WPLQDM_Manager->doSql($sql, $res, $params, false);
            // Return the number of affected rows
            return $this->WPLQDM_Manager->get_numrow();
        } catch (Exception $e) {
            return false;
        }
    }

    /**
     * @brief Deletes a specific dock program record.
     *
     * Removes a dock program record for a specific barcode, PCB side,
     * program, and production line from the database.
     *
     * @param string $barc4 The barcode identifier
     * @param int $side The side identifier (1 or 2)
     * @param string $program The program identifier
     * @param string $line The production line identifier
     * @return int|false The number of affected rows or false on error
     * @access public
     */
    public function deleteInsertDockProIM($barc4, $side, $program, $line) {
        try {
            $params = array(":barc4" => $barc4, ':side' => $side, ':program' => $program, ':line' => $line);
            $sql = "DELETE FROM BIM_SMD_IM_DOCK_DEV_PRO 
                    WHERE barc4 = :barc4 AND SIDE = :side AND PROGRAM = :program AND LINE = :line";
            $res = array();
            $this->WPLQDM_Manager->doSql($sql, $res, $params, false);
            // Return the number of affected rows
            return $this->WPLQDM_Manager->get_numrow();
        } catch (Exception $e) {
            return false;
        }
    }

    /**
     * @brief Saves general information by updating the record.
     *
     * Updates the general information record for a specific barcode and PCB side
     * with new values. Validates the number of tiles in panel to ensure it's a
     * valid numeric value.
     *
     * @param string $barc4 The barcode identifier
     * @param int $side The side identifier (1 or 2)
     * @param string $name1 The component name
     * @param string $subgroup The subgroup identifier
     * @param string $tile The tile identifier
     * @param int $num_tiles_in_panel The number of tiles in panel (must be 1-5 digits)
     * @param string $width_tiles The width of tiles
     * @param string $assembly_order The assembly order identifier
     * @return int|false The number of affected rows or false on error
     * @access public
     */
    public function saveGeneralInformation($barc4, $side, $name1, $subgroup, $tile, $num_tiles_in_panel, $width_tiles, $assembly_order) {
        try {
            // Validate num_tiles_in_panel - allow only numbers up to 5 digits
            if (!preg_match('/^\d{1,5}$/', $num_tiles_in_panel)) {
                error_log("Invalid num_tiles_in_panel value (must be 1-5 digits): " . $num_tiles_in_panel);
                return false;
            }
            
            $num_tiles_in_panel = intval($num_tiles_in_panel);
            
            $sql = "UPDATE BIM_SMD_IM_GEN_INFORM 
                    SET NAME_I = :name1,
                        SUBGROUP = :subgroup,
                        TILE = :tile,
                        NUM_TILES_IN_PANEL = :num_tiles_in_panel,
                        WIDTH_TILES = :width_tiles,
                        ASSEMBLY_ORDER = :assembly_order
                    WHERE BARC4 = :barc4 AND SIDE = :side";
                    
            $params = array(
                ":barc4" => $barc4,
                ":side" => $side,
                ":name1" => $name1,
                ":subgroup" => $subgroup,
                ":tile" => $tile,
                ":num_tiles_in_panel" => $num_tiles_in_panel,
                ":width_tiles" => $width_tiles,
                ":assembly_order" => $assembly_order
            );
            
            $res = array();
            $this->WPLQDM_Manager->doSql($sql, $res, $params, true);
            return $this->WPLQDM_Manager->get_numrow();
        } catch (Exception $e) {
            error_log("Error in saveGeneralInformation: " . $e->getMessage());
            return false;
        }
    }
   
    /**
     * @brief Copies general information by inserting a new record.
     *
     * Creates a new general information record for a specific barcode and PCB side
     * based on values from another record. Used in the copy operation to duplicate
     * instructions from one barcode to another.
     *
     * @param string $barc4 The target barcode identifier
     * @param int $side The side identifier (1 or 2)
     * @param string $name1 The component name
     * @param string $subgroup The subgroup identifier
     * @param string $tile The tile identifier
     * @param int $num_tiles_in_panel The number of tiles in panel
     * @param string $width_tiles The width of tiles
     * @param string $assembly_order The assembly order identifier
     * @return int|false The number of affected rows or false on error
     * @access public
     */
    public function copyGeneralInformation($barc4, $side, $name1, $subgroup, $tile, $num_tiles_in_panel, $width_tiles, $assembly_order) {
        try {
            $sql = "INSERT INTO BIM_SMD_IM_GEN_INFORM (BARC4, SIDE, NAME_I, SUBGROUP, TILE, NUM_TILES_IN_PANEL, WIDTH_TILES, ASSEMBLY_ORDER) 
                    VALUES (:barc4, :side, :name1, :subgroup, :tile, :num_tiles_in_panel, :width_tiles, :assembly_order)";
            // Prepare parameters for insertion
            $params = array(
                ":barc4" => $barc4, 
                ":side" => $side,
                ":name1" => $name1, 
                ":subgroup" => $subgroup,
                ":tile" => $tile, 
                ":num_tiles_in_panel" => $num_tiles_in_panel,
                ":width_tiles" => $width_tiles, 
                ":assembly_order" => $assembly_order
            );
            $res = array();
            $this->WPLQDM_Manager->doSql($sql, $res, $params, true);
            // Return the number of affected rows
            return $this->WPLQDM_Manager->get_numrow();
        } catch (Exception $e) {
            return false;
        }
    }

   
    /**
     * @brief Retrieves image notes for a given barcode and side.
     *
     * Queries the database for all image notes associated with a specific 
     * barcode and PCB side. Returns all IMG_NOTE field values from matching records.
     *
     * @param string $barc The barcode identifier
     * @param int $side The side identifier (1 or 2)
     * @return array|false Array of image note data or false on error
     * @access public
     */
    public function getImageNotes($barc, $side) {
        try {
            $params = array(':barc4' => $barc, ':side' => $side);
            $sql = "SELECT IMG_NOTE FROM BIM_SMD_IM_NOTE WHERE BARC4 = :barc4 AND SIDE = :side";
            $res = array();
            $this->WPLQDM_Manager->doSql($sql, $res, $params, false);
            // Return the result set
            return $res; 
        } catch (Exception $e) {
            return false; 
        }
    }
    
    /**
     * @brief Saves notes by updating the parent note ID.
     *
     * Updates a specific note record to associate it with a parent note template.
     * This is used to standardize notes by linking them to predefined templates.
     *
     * @param int $id The ID of the note to update
     * @param int $id_parent_note The parent note template ID to associate with
     * @return int|false The number of affected rows or false on error
     * @access public
     */
    public function saveNotes($id, $id_parent_note) {
        try {
            $sql = "UPDATE BIM_SMD_IM_NOTE 
                    SET ID_PARENT_NOTE = :id_parent_note
                    WHERE ID_NOTE = :id";
            $params = array(":id" => $id, ":id_parent_note" => $id_parent_note);
            $res = array();
            $this->WPLQDM_Manager->doSql($sql, $res, $params, false);
            // Return the number of affected rows
            return $this->WPLQDM_Manager->get_numrow();
        } catch (Exception $e) {
            return false;
        }
    }
    
    /**
     * @brief Retrieves categories of notes.
     *
     * Gets all note categories from the category table.
     * Categories determine the appearance and behavior of notes in the UI.
     *
     * @return array|false Array of note categories or false on error
     * @access public
     */
    public function categoryNotes() {
        try {
            $sql = "SELECT * FROM BIM_SMD_IM_CATEGORY_NOTE";
            $params = array();
            $res = array();
            $this->WPLQDM_Manager->doSql($sql, $res, $params);
            // Return the result set
            return $res;
        } catch (Exception $e) {
            return false;
        }
    }
    
    /**
     * @brief Finds barcodes matching a given partial barcode.
     *
     * Searches for barcodes in the product number table that match
     * the provided pattern. Uses SQL LIKE with wildcards for partial matching.
     *
     * @param string $barc4 Partial or complete barcode to search for
     * @return array|false Array of matching barcode records or false on error
     * @access public
     */
    public function findBarcode($barc4) {
        try {
            $sql = "SELECT * FROM PROG_PRODUCT_NR WHERE barc4 LIKE :barc4";
            $params = array(':barc4' => '%' . $barc4 . '%');
            $res = array();
            $this->WPLQDM_Manager->doSql($sql, $res, $params);
            // Return the result set
            return $res;
        } catch (Exception $e) {
            return false;
        }
    }
    
    /**
     * @brief Finds new barcodes and determines their situation.
     *
     * Performs a complex query that identifies barcodes and their status:
     * - 'both': Exists in both product table and general information table
     * - 'prog_only': Exists only in product table (no instruction data)
     * - 'none': Other situations
     *
     * @param string $newbarc4 Partial or complete barcode to search for
     * @return array|false Array of barcodes with situation indicators or false on error
     * @access public
     */
    public function findnewBarcode($newbarc4) {
        if (empty($newbarc4)) {
            return []; // Returns empty array if input string is empty
        }
        try {
            $sql = "SELECT p.barc4,
                           CASE
                               WHEN p.barc4 IS NOT NULL AND b.barc4 IS NOT NULL THEN 'both'
                               WHEN p.barc4 IS NOT NULL AND b.barc4 IS NULL THEN 'prog_only'
                               ELSE 'none'
                           END AS situation
                    FROM PROG_PRODUCT_NR p
                    LEFT JOIN BIM_SMD_IM_GEN_INFORM b ON p.barc4 = b.barc4
                    WHERE p.barc4 LIKE :newbarc4
                    UNION
                    SELECT b.barc4, 'none'
                    FROM BIM_SMD_IM_GEN_INFORM b
                    WHERE b.barc4 LIKE :newbarc4 AND NOT EXISTS (
                        SELECT 1 FROM PROG_PRODUCT_NR p WHERE p.barc4 = b.barc4
                    )";
            $params = array(':newbarc4' => '%' . $newbarc4 . '%');
            $res = array();
            $this->WPLQDM_Manager->doSql($sql, $res, $params);
            // Return the result set
            return $res;
        } catch (Exception $e) {
            return false;
        }
    }
    
    /**
     * @brief Loads general information for a given barcode and side.
     *
     * Retrieves all fields from the general information table for a specific
     * barcode and PCB side combination.
     *
     * @param string $barc4 The barcode identifier
     * @param int $side The side identifier (1 or 2)
     * @return array|false General information record or false on error
     * @access public
     */
    public function loadGeneralInformation($barc4, $side) {
        try {
            $sql = "SELECT * FROM BIM_SMD_IM_GEN_INFORM inform 
                    WHERE inform.barc4 = :barc4 AND inform.side = :side";
            $params = array(':barc4' => $barc4, ':side' => $side);
            $res = array();
            $this->WPLQDM_Manager->doSql($sql, $res, $params);
            // Return the result set
            return $res;
        } catch (Exception $e) {
            return false;
        }
    }
    
    /**
     * @brief Checks barcode for a given WPN number.
     *
     * Searches for barcodes associated with a specific Work Process Number
     * in the product table.
     *
     * @param string $wpnNumber The WPN/Work Process Number to search for
     * @return array|false Array of matching barcodes or false on error
     * @access public
     */
    public function checkBarcodeForWpn($wpnNumber) {
        try {
            $sql = "SELECT barc4 FROM PROG_PRODUCT_NR WHERE wabconr = :wpnNumber";
            $params = array(':wpnNumber' => $wpnNumber);
            $res = array();
            $this->WPLQDM_Manager->doSql($sql, $res, $params);
            // Return the array of barcodes
            return $res["BARC4"];
        } catch (Exception $e) {
            return false;
        }
    }
    
    /**
     * @brief Inserts header information for a barcode instruction.
     *
     * Creates a new header record with document number, creation date,
     * author, and other metadata for a specific barcode and side.
     *
     * @param string $barc4 The barcode identifier
     * @param int $side The side identifier (1 or 2)
     * @param string $documentNr The document reference number
     * @param string $date_created Creation date in YYYY/MM/DD HH24:MI:SS format
     * @param string $author Author name or identifier
     * @return int|false The number of affected rows or false on error
     * @access public
     */
    public function insertHeaderIM($barc4, $side, $documentNr, $date_created, $author) {
        try {
            $params = array(
                ':barc4' => $barc4, 
                ":side" => $side, 
                ":doc_nr" => $documentNr, 
                ":date_created" => $date_created, 
                ":author" => $author
            );
            $sql = "INSERT INTO BIM_SMD_IM_HEADER (BARC4, SIDE, DOC_NR, DATE_CREATED, AUTHOR) 
                    VALUES (:barc4, :side, :doc_nr, to_date(:date_created,'YYYY/MM/DD HH24:MI:SS'), :author)";
            $res = array();
            $this->WPLQDM_Manager->doSql($sql, $res, $params, false);
            // Return the number of affected rows
            return $this->WPLQDM_Manager->get_numrow();
        } catch (Exception $e) {
            return false;
        }
    }
    
    /**
     * @brief Inserts general information for a barcode instruction.
     *
     * Creates a new general information record with component name, subgroup,
     * tile information, and assembly parameters for a specific barcode and side.
     *
     * @param string $barc4 The barcode identifier
     * @param int $side The side identifier (1 or 2)
     * @param string $name Component name
     * @param string $subgroup Subgroup identifier
     * @param string $tile Tile/PCB identifier
     * @param int $numberTilesPanel Number of tiles in panel
     * @param string $tileWidth Width of tiles
     * @param string $assemblyOrder Assembly order sequence
     * @return int|false The number of affected rows or false on error
     * @access public
     */
    public function insertGenInformIM($barc4, $side, $name, $subgroup, $tile, $numberTilesPanel, $tileWidth, $assemblyOrder) {
        try {
            $params = array(
                ':barc4' => $barc4,
                ':side' => $side,
                ":name_i" => $name,
                ":subgroup" => $subgroup,
                ":tile" => $tile,
                ":numberTilesPanel" => $numberTilesPanel,
                ":tileWidth" => $tileWidth,
                ":assemblyOrder" => $assemblyOrder
            );
            $sql = "INSERT INTO BIM_SMD_IM_GEN_INFORM (BARC4, SIDE, NAME_I, SUBGROUP, TILE, NUM_TILES_IN_PANEL, WIDTH_TILES, ASSEMBLY_ORDER) 
                    VALUES (:barc4, :side, :name_i, :subgroup, :tile, :numberTilesPanel, :tileWidth, :assemblyOrder)";
            $res = array();
            $this->WPLQDM_Manager->doSql($sql, $res, $params, false);
            // Return the number of affected rows
            return $this->WPLQDM_Manager->get_numrow();
        } catch (Exception $e) {
            return false;
        }
    }
    
    /**
     * @brief Inserts a print program record.
     *
     * Creates a new print program entry associating a program name with
     * a specific barcode, side, and production line.
     *
     * @param string $barc4 The barcode identifier
     * @param int $side The side identifier (1 or 2)
     * @param string $program Program name/identifier
     * @param string $line Production line identifier
     * @return int|false The number of affected rows or false on error
     * @access public
     */
    public function insertPrintProgIM($barc4, $side, $program, $line) {
        try {
            $params = array(":barc4" => $barc4, ':side' => $side, ":program" => $program, ":line" => $line);
            $sql = "INSERT INTO BIM_SMD_IM_DEV_PRINT_PROG (BARC4, SIDE, PROGRAM, LINE) 
                    VALUES (:barc4, :side, :program, :line)";
            $res = array();
            $this->WPLQDM_Manager->doSql($sql, $res, $params, false);
            // Return the number of affected rows
            return $this->WPLQDM_Manager->get_numrow();
        } catch (Exception $e) {
            error_log("Error in insertPrintProgIM: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * @brief Retrieves all history records from the database.
     *
     * Gets all instruction history records ordered by change date in descending order
     * (newest first). Logs any errors encountered during the query execution.
     *
     * @return array|false Array of history records or false on error
     * @access public
     */
    public function getHistoryRecords() {
        try {
            $sql = "SELECT * FROM BIM_SMD_IM_HISTORY ORDER BY CHANGE_DATE DESC";
            $params = array();
            $res = array();
            $result = $this->WPLQDM_Manager->doSql($sql, $res, $params);
    
            if ($result === false) {
                error_log("Błąd podczas wykonywania zapytania SQL: " . $this->WPLQDM_Manager->get_oci_error());
                return false;
            }
    
            return $res;
        } catch (Exception $e) {
            error_log("Wyjątek podczas wykonywania SQL: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * @brief Retrieves a single history record by its ID.
     *
     * Gets a specific history record and converts the column-based result array
     * into a more convenient row-based associative array structure.
     * Throws exceptions for error conditions such as SQL errors or missing records.
     *
     * @param int $id The history record ID to retrieve
     * @return array|false Associative array with history record data or false on error
     * @access public
     */
    public function getHistoryRecordById($id) {
        try {
            $sql = "SELECT * FROM BIM_SMD_IM_HISTORY WHERE ID_HISTORY = :id";
            $params = array(':id' => $id);
            $res = array();
            $result = $this->WPLQDM_Manager->doSql($sql, $res, $params);
    
            if ($result === false) {
                $error = $this->WPLQDM_Manager->get_oci_error();
                throw new Exception("Ошибка при выполнении запроса SQL: " . $error);
            }
    
            if (isset($res['ID_HISTORY']) && count($res['ID_HISTORY']) > 0) {
                // Преобразуем данные в ассоциативный массив одной записи
                $record = [];
                foreach ($res as $field => $values) {
                    $record[$field] = $values[0]; // Берем первую (и единственную) запись
                }
                return $record;
            } else {
                throw new Exception("Запись с ID_HISTORY = $id не найдена.");
            }
        } catch (Exception $e) {
            error_log("Exception in getHistoryRecordById: " . $e->getMessage());
            return false;
        }
    }
    
 
    /**
     * @brief Retrieves the previous history record for a specific barcode and side.
     *
     * Fetches the most recent history record that was created before the specified
     * timestamp for the given barcode and side. Returns detailed information about
     * the previous version of the instruction.
     *
     * @param string $barc4 The barcode identifier
     * @param int $side The side identifier (1 or 2)
     * @param string $changeDate The timestamp to compare against, formatted as Oracle date string
     * @return array|false Associative array with previous record data or false if no record found
     * @access public
     */
    public function getPreviousHistoryRecord($barc4, $side, $changeDate) {
        try {
            $sql = "SELECT * FROM BIM_SMD_IM_HISTORY
                    WHERE BARC4 = :barc4 AND SIDE = :side AND CHANGE_DATE < TO_TIMESTAMP(:changeDate, 'DD-MON-RR HH12.MI.SS.FF AM')
                    ORDER BY CHANGE_DATE DESC FETCH FIRST 1 ROWС ONLY";
            $params = array(
                ':barc4' => $barc4,
                ':side' => $side,
                ':changeDate' => $changeDate
            );
            $res = array();
            $result = $this->WPLQDM_Manager->doSql($sql, $res, $params);
    
            if ($result === false) {
                $error = $this->WPLQDM_Manager->get_oci_error();
                throw new Exception("Ошибка при выполнении запроса SQL: " . $error);
            }
    
            if (isset($res['ID_HISTORY']) && count($res['ID_HISTORY']) > 0) {
                // Convert the data into an associative array of a single record
                $record = [];
                foreach ($res as $field => $values) {
                    $record[$field] = $values[0]; // Let's take the first (and only) entry
                }
                return $record;
            } else {
                return false; // No previous entry
            }
        } catch (Exception $e) {
            error_log("Exception in getPreviousHistoryRecord: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * @brief Retrieves note details for multiple note IDs.
     *
     * Gets details for multiple notes based on their IDs, including their associated
     * typical comments and category information through table joins. Returns data
     * containing the note content, image references, and category details.
     *
     * @param array $notesIds Array of note IDs to retrieve
     * @return array|false Array of note records or empty array if no notes found, false on error
     * @access public
     */
    public function getNotesByIds($notesIds) {
        try {
            if (empty($notesIds)) {
                return [];
            }
    
            // Prepare named placeholders and parameters
            $placeholders = [];
            $params = [];
            foreach ($notesIds as $index => $id) {
                $paramName = ':id' . $index;
                $placeholders[] = $paramName;
                $params[$paramName] = $id;
            }
            $placeholdersString = implode(',', $placeholders);
    
            $sql = "SELECT note.ID_NOTE, note.IMG_NOTE, note.ID_PARENT_NOTE, tc.CATEGORY, tc.NOTE, cn.CATEGORY AS CAT_TEXT
                    FROM BIM_SMD_IM_NOTE note
                    INNER JOIN BIM_SMD_IM_TYPICAL_COMMENT tc ON tc.ID_COMMENT = note.ID_PARENT_NOTE
                    INNER JOIN BIM_SMD_IM_CATEGORY_NOTE cn ON cn.ID = tc.CATEGORY
                    WHERE note.ID_NOTE IN ($placeholdersString)";
    
            $res = array();
            $result = $this->WPLQDM_Manager->doSql($sql, $res, $params);
    
            if ($result === false) {
                $error = $this->WPLQDM_Manager->get_oci_error();
                throw new Exception("Ошибка при выполнении запроса SQL: " . $error);
            }
    
            return $res;
        } catch (Exception $e) {
            error_log("Exception in getNotesByIds: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * @brief Inserts a history record for instruction changes.
     *
     * Creates a comprehensive history record that documents all details of
     * an instruction at a specific point in time. Stores all instruction data
     * including barcode, side, program details, machine configurations, notes,
     * and change comments.
     *
     * @param array $data Associative array containing all history record field values
     * @return int|false The number of affected rows or false on error
     * @access public
     */
    public function insertHistoryRecord($data) {
        try {
            $sql = "INSERT INTO BIM_SMD_IM_HISTORY (
                        BARC4, SIDE, LINE, WPN, NAME_I, SUBGROUP, TILE, NUM_TILES_IN_PANEL, 
                        WIDTH_TILES, ASSEMBLY_ORDER, PROGRAM_PRINT_PROG, 
                        MACHINE_PROGRAM_1R, MACHINE_PROGRAM_2R, MACHINE_PROGRAM_3R, MACHINE_PROGRAM_4R, 
                        MACHINE_PROGRAM_1G, MACHINE_PROGRAM_2G, MACHINE_PROGRAM_3G, 
                        NOTES_ID_1, NOTES_ID_2, NOTES_ID_3, NOTES_ID_4, 
                        AUTHOR, COMMENTS
                    ) VALUES (
                        :barc4, :side, :line, :wpn, :name_i, :subgroup, :tile, :num_tiles_in_panel, 
                        :width_tiles, :assembly_order, :program_print_prog, 
                        :machine_program_1r, :machine_program_2r, :machine_program_3r, :machine_program_4r, 
                        :machine_program_1g, :machine_program_2g, :machine_program_3g, 
                        :notes_id_1, :notes_id_2, :notes_id_3, :notes_id_4, 
                        :author, :comments
                    )";
    
            $params = [
                ':barc4' => $data['BARC4'],
                ':side' => $data['SIDE'],
                ':line' => $data['LINE'],
                ':wpn' => $data['WPN'],
                ':name_i' => $data['NAME_I'],
                ':subgroup' => $data['SUBGROUP'],
                ':tile' => $data['TILE'],
                ':num_tiles_in_panel' => $data['NUM_TILES_IN_PANEL'],
                ':width_tiles' => $data['WIDTH_TILES'],
                ':assembly_order' => $data['ASSEMBLY_ORDER'],
                ':program_print_prog' => $data['PROGRAM_PRINT_PROG'],
                ':machine_program_1r' => $data['MACHINE_PROGRAM_1R'],
                ':machine_program_2r' => $data['MACHINE_PROGRAM_2R'],
                ':machine_program_3r' => $data['MACHINE_PROGRAM_3R'],
                ':machine_program_4r' => $data['MACHINE_PROGRAM_4R'],
                ':machine_program_1g' => $data['MACHINE_PROGRAM_1G'],
                ':machine_program_2g' => $data['MACHINE_PROGRAM_2G'],
                ':machine_program_3g' => $data['MACHINE_PROGRAM_3G'],
                ':notes_id_1' => $data['NOTES_ID_1'],
                ':notes_id_2' => $data['NOTES_ID_2'],
                ':notes_id_3' => $data['NOTES_ID_3'],
                ':notes_id_4' => $data['NOTES_ID_4'],
                ':author' => $data['AUTHOR'],
                ':comments' => $data['COMMENTS']
            ];
    
            $res = array();
            $this->WPLQDM_Manager->doSql($sql, $res, $params, true);
            // Return the number of affected rows
            return $this->WPLQDM_Manager->get_numrow();
        } catch (Exception $e) {
            error_log("Error in insertHistoryRecord: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * @brief Inserts, updates, or deletes a dock program record.
     *
     * Performs smart handling of dock program records based on the program value:
     * - If program is empty/zero: Deletes the record for the given barcode, side, and line
     * - If program has value and record exists: Updates the existing record
     * - If program has value and record doesn't exist: Creates a new record
     *
     * @param string $barc4 The barcode identifier
     * @param int $side The side identifier (1 or 2)
     * @param string $program The program identifier (empty string or "0" to delete)
     * @param string $line The production line identifier
     * @return bool True on successful operation, false on failure
     * @access public
     */
    public function insertDockProIM($barc4, $side, $program, $line) {
        try {
            $params = array(
                ":barc4" => $barc4, 
                ":side" => $side, 
                ":program" => $program, 
                ":line" => $line
            );
    
            if ($program === "" || $program === "0") {
                // If program is empty or zero, delete the record
                $sql = "DELETE FROM BIM_SMD_IM_DOCK_DEV_PRO 
                        WHERE BARC4 = :barc4 AND SIDE = :side AND LINE = :line";
                $this->WPLQDM_Manager->doSql($sql, $res, $params, false);
                $rows = $this->WPLQDM_Manager->get_numrow();
                return $rows > 0;
            } else {
                // Try to update the existing record
                $sql = "UPDATE BIM_SMD_IM_DOCK_DEV_PRO 
                        SET PROGRAM = :program 
                        WHERE BARC4 = :barc4 AND SIDE = :side AND LINE = :line";
                $this->WPLQDM_Manager->doSql($sql, $res, $params, false);
                $rows = $this->WPLQDM_Manager->get_numrow();
    
                if ($rows === 0) {
                    // If no record was updated, insert a new one
                    $sql = "INSERT INTO BIM_SMD_IM_DOCK_DEV_PRO (BARC4, SIDE, PROGRAM, LINE) 
                            VALUES (:barc4, :side, :program, :line)";
                    $this->WPLQDM_Manager->doSql($sql, $res, $params, false);
                    $rows = $this->WPLQDM_Manager->get_numrow();
                    return $rows > 0;
                }
                return true;
            }
        } catch (Exception $e) {
            error_log("Error in insertDockProIM: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * @brief Inserts a new note for a barcode.
     *
     * Creates a new note record associated with a specific barcode and side,
     * with an optional image reference and parent note template ID.
     *
     * @param string $barc4 The barcode identifier
     * @param int $side The side identifier (1 or 2)
     * @param string $imageComment Optional image filename/reference for the note
     * @param int $note The parent note template ID (ID_PARENT_NOTE)
     * @return int|false The number of affected rows or false on error
     * @access public
     */
    public function insertNoteIM($barc4, $side, $imageComment, $note) {
        try {
            $params = array(":barc4" => $barc4, ":side" => $side, ':note' => $note, ':imageComment' => $imageComment);
            $sql = "INSERT INTO BIM_SMD_IM_NOTE (BARC4, SIDE, IMG_NOTE, ID_PARENT_NOTE) 
                    VALUES (:barc4, :side, :imageComment, :note)";
            $res = array();
            $this->WPLQDM_Manager->doSql($sql, $res, $params, false);
            // Return the number of affected rows
            return $this->WPLQDM_Manager->get_numrow();
        } catch (Exception $e) {
            error_log("Error in insertNoteIM: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * @brief Adds a new typical note template.
     *
     * Creates a new reusable note template in the typical comments table
     * that can be referenced from individual notes. Templates are categorized
     * to control their appearance and behavior.
     *
     * @param int $catNotice The category ID for the note
     * @param string $addNotice The note text content
     * @return int|false The number of affected rows or false on error
     * @access public
     */
    public function addNewNote($catNotice, $addNotice) {
        try {
            $params = array(":catNotice" => $catNotice, ":addNotice" => $addNotice);
            $sql = "INSERT INTO BIM_SMD_IM_TYPICAL_COMMENT (CATEGORY, NOTE) 
                    VALUES (:catNotice, :addNotice)";
            $res = array();
            $this->WPLQDM_Manager->doSql($sql, $res, $params, true);
            // Return the number of affected rows
            return $this->WPLQDM_Manager->get_numrow();
        } catch (Exception $e) {
            return false;
        }
    }
    
    /**
     * @brief Deletes a typical note template.
     *
     * Removes a note template from the typical comments table based on its ID.
     * Logs detailed information about the deletion process for debugging.
     *
     * @param int $deleteNotice The ID of the typical comment to delete
     * @return bool True if deletion was successful, false otherwise
     * @access public
     */
    public function deleteNotice($deleteNotice) {
        try {
            $params = array(":deleteNotice" => $deleteNotice);
            $sql = "DELETE FROM BIM_SMD_IM_TYPICAL_COMMENT WHERE ID_COMMENT = :deleteNotice";
            $res = array();
            $result = $this->WPLQDM_Manager->doSql($sql, $res, $params, true);
            
            if (!$result) {
                error_log("SQL deletion failed in deleteNotice: " . $this->WPLQDM_Manager->get_oci_error());
                return false;
            }
            
            $rowsAffected = $this->WPLQDM_Manager->get_numrow();
            error_log("Deleted notice with ID: $deleteNotice, Rows affected: $rowsAffected");
            return $rowsAffected > 0;
        } catch (Exception $e) {
            error_log("Exception in deleteNotice: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * @brief Deletes a specific note by ID and side.
     *
     * Removes a note record from the database for a given ID and side.
     * The side constraint ensures that notes are only deleted from the
     * correct PCB side context.
     *
     * @param int $noteId The ID of the note to delete
     * @param int $side The side identifier (1 or 2) that the note belongs to
     * @return bool True if deletion was successful, false otherwise
     * @access public
     */
    public function deleteNote($noteId, $side) {
        try {
            $sql = "DELETE FROM BIM_SMD_IM_NOTE WHERE ID_NOTE = :note_id AND SIDE = :side";
            $params = array(':note_id' => $noteId, ':side' => $side);
            $res = array();
            $this->WPLQDM_Manager->doSql($sql, $res, $params, true);
            return $this->WPLQDM_Manager->get_numrow() > 0;
        } catch (Exception $e) {
            error_log("Delete note error: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * @brief Adds a new note and returns its ID.
     *
     * Creates a new note record for a barcode and side with a reference to
     * a parent note template, then retrieves the newly created note ID.
     * Formats input parameters to ensure proper data types and constraints.
     *
     * @param string $barc4 The barcode identifier (will be trimmed to 4 characters)
     * @param int $side The side identifier (1 or 2)
     * @param int $parentNoteId The parent note template ID
     * @return int|false The newly created note ID or false on error
     * @access public
     */
    public function addNote($barc4, $side, $parentNoteId) {
        try {
            // Format BARC4 as CHAR(4)
            $barc4 = substr(trim($barc4), 0, 4);
            $side = intval($side);
            $parentNoteId = intval($parentNoteId);
            
            // First we do the insertion
            $sql = "INSERT INTO BIM_SMD_IM_NOTE (BARC4, SIDE, ID_PARENT_NOTE) 
                    VALUES (:barc4, :side, :parent_note_id)";
            $params = array(
                ':barc4' => $barc4,
                ':side' => $side,
                ':parent_note_id' => $parentNoteId
            );
            $res = array();
            $result = $this->WPLQDM_Manager->doSql($sql, $res, $params, true);
            
            if (!$result) {
                error_log("SQL insertion failed in addNote: " . $this->WPLQDM_Manager->get_oci_error());
                return false;
            }
            
            // Then we get the ID of the last inserted record
            $idSql = "SELECT MAX(ID_NOTE) as LAST_ID FROM BIM_SMD_IM_NOTE 
                     WHERE BARC4 = :barc4 AND SIDE = :side AND ID_PARENT_NOTE = :parent_note_id";
            
            $this->WPLQDM_Manager->doSql($idSql, $res, $params);
            
            if (isset($res['LAST_ID']) && !empty($res['LAST_ID'])) {
                return $res['LAST_ID'][0];
            } else {
                error_log("Could not retrieve last inserted ID in addNote");
                return false;
            }
        } catch (Exception $e) {
            error_log("Exception in addNote: " . $e->getMessage());
            return false;
        }
    }
   
/**
 * @brief Retrieves notes for a given barcode and side.
 *
 * Gets all notes associated with a specific barcode and side, including their
 * category information and typical comment details through joins with the
 * typical comments and category tables. Results are ordered by note ID.
 *
 * @param string $barc4 The barcode identifier
 * @param int $side The side identifier (1 or 2)
 * @return array|false Array of note records or false on error
 * @access public
 */
public function notes($barc4, $side) {
    try {
        $sql = "SELECT note.barc4, note.side, note.img_note, note.id_note, note.id_parent_note, tc.category, tc.note, cn.category AS catText 
                FROM BIM_SMD_IM_NOTE note
                INNER JOIN BIM_SMD_IM_TYPICAL_COMMENT tc ON tc.ID_COMMENT = note.ID_PARENT_NOTE
                INNER JOIN BIM_SMD_IM_CATEGORY_NOTE cn ON cn.ID = tc.category
                WHERE note.barc4 = :barc4 AND note.side = :side
                ORDER BY ID_NOTE ASC";
        $params = array(':barc4' => $barc4, ':side' => $side);
        $res = array();
        $this->WPLQDM_Manager->doSql($sql, $res, $params);
        // Return the result set
        return $res;
    } catch (Exception $e) {
        return false;
    }
}

/**
 * @brief Prepares a comma-separated list of devices for SQL IN clause.
 *
 * Converts an array of device identifiers to a comma-separated string
 * with each value properly quoted for use in SQL IN clauses.
 *
 * @param array $devices Array of device identifiers
 * @return string Comma-separated quoted string of device identifiers
 * @access private
 */
private function prepareLine($devices) {
    $deviceSql = '';
    $deviceSqlIndex = 0;
    foreach ($devices as $device) {
        $deviceSql .= "'" . $device . "'";
        if (count($devices) != $deviceSqlIndex + 1) {
            $deviceSql .= ",";
        }
        $deviceSqlIndex++;
    }
    return $deviceSql;
}

/**
 * @brief Retrieves print programs for a given barcode, side, and devices.
 *
 * Gets all print program entries for the specified barcode and side
 * that match any of the provided device identifiers. Uses the prepareLine
 * method to format the device list for the SQL IN clause.
 *
 * @param string $barc4 The barcode identifier
 * @param int $side The side identifier (1 or 2)
 * @param array $devices Array of device identifiers to include
 * @return array|false Print program records or false on error
 * @access public
 */
public function printProg($barc4, $side, $devices) {
    try {
        $deviceSql = $this->prepareLine($devices);
        $sql = "SELECT * FROM BIM_SMD_IM_DEV_PRINT_PROG 
                WHERE barc4 = :barc4 AND side = :side AND LINE IN ($deviceSql)";
        $params = array(':barc4' => $barc4, ':side' => $side);
        $res = array();
        $this->WPLQDM_Manager->doSql($sql, $res, $params);
        // Return the result set
        return $res;
    } catch (Exception $e) {
        return false;
    }
}

/**
 * @brief Retrieves dock programs for a given barcode, side, and devices.
 *
 * Gets all dock program entries for the specified barcode and side
 * that match any of the provided device identifiers. Uses the prepareLine
 * method to format the device list for the SQL IN clause.
 *
 * @param string $barc4 The barcode identifier
 * @param int $side The side identifier (1 or 2)
 * @param array $devices Array of device identifiers to include
 * @return array|false Dock program records or false on error
 * @access public
 */
public function dockPro($barc4, $side, $devices) {
    try {
        $deviceSql = $this->prepareLine($devices);
        $sql = "SELECT * FROM BIM_SMD_IM_DOCK_DEV_PRO 
                WHERE barc4 = :barc4 AND side = :side AND LINE IN ($deviceSql)";
        $params = array(':barc4' => $barc4, ':side' => $side);
        $res = array();
        $this->WPLQDM_Manager->doSql($sql, $res, $params);
        // Return the result set
        return $res;
    } catch (Exception $e) {
        return false;
    }
}

/**
 * @brief Rolls back the current database transaction.
 *
 * Executes a ROLLBACK statement to cancel all pending database changes.
 * Also collects error information and affected row count for debugging.
 *
 * @return bool Always returns true
 * @access public
 */
public function rollback() {
    $params = array();
    $res = array();
    $this->WPLQDM_Manager->doSQL("rollback", $res, $params);
    $res['INF']['N'] = $this->WPLQDM_Manager->get_numrow();
    $res['INF']['ORA_ERROR'] = $this->WPLQDM_Manager->get_oci_error();
    return true;
}

/**
 * @brief Commits the current database transaction.
 *
 * Executes a COMMIT statement to finalize all pending database changes.
 * Also collects error information and affected row count for debugging.
 *
 * @return bool Always returns true
 * @access public
 */
public function commit() {
    $params = array();
    $res = array();
    $this->WPLQDM_Manager->doSQL("commit", $res, $params);
    $res['INF']['N'] = $this->WPLQDM_Manager->get_numrow();
    $res['INF']['ORA_ERROR'] = $this->WPLQDM_Manager->get_oci_error();
    return true;
}

/**
 * @brief Checks if an instruction exists for a specific barcode and side.
 *
 * Queries the general information table to determine if an instruction
 * record exists for the specified barcode and side combination.
 *
 * @param string $barc4 The barcode identifier to check
 * @param int $side The side identifier (1 or 2) to check
 * @return bool True if instruction exists, false otherwise or on error
 * @access public
 */
public function checkBarcodeInstruction($barc4, $side) {
    try {
        $sql = "SELECT COUNT(*) as COUNT 
                FROM BIM_SMD_IM_GEN_INFORM 
                WHERE BARC4 = :barc4 AND SIDE = :side";
        
        $params = array(':barc4' => $barc4, ':side' => $side);
        $res = array();
        $this->WPLQDM_Manager->doSql($sql, $res, $params);
        return isset($res['COUNT'][0]) ? $res['COUNT'][0] > 0 : false;
    } catch (Exception $e) {
        return false;
    }
}
}
?>
