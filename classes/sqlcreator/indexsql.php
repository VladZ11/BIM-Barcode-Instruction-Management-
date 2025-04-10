<?php

/**
 * @author Vladyslav Zhyokin
 */

/**
 * @brief SQL creator class for the BIM system index functionality.
 *
 * This class manages database interactions for the main index functionality of the 
 * Barcode Instruction Management (BIM) system. It connects to the QDM database and 
 * provides methods for retrieving barcode data, instruction information, notes, 
 * and component details. It also handles queries related to SMD processes including 
 * programs, maps, and equipment configurations.
 * 
 * @class SqlCreator_IndexSql
 * @extends W_sqlcreator_SQLCreator
 */
class SqlCreator_IndexSql extends W_sqlcreator_SQLCreator{

    /**
     * @brief Database connection handler for TRACE database
     * @var DBManager|null
     * @access private
     */
    private $WPLQDM_TRACE_Manager;
    
    /**
     * @brief Retrieves additional information from SCS (Surface Component System).
     *
     * Fetches stencil, squeegee, paste, and support information for a specific 
     * barcode and side from the SCS system. Different queries are used based on 
     * the specified side (1 or 2).
     *
     * @param int $side PCB side identifier (1 or 2)
     * @param string $barc4 Barcode identifier (4 characters)
     * @return array Results containing SCS complement data
     * @access public
     */
    public function searchAdditionalInfScs($side,$barc4){
        $this->WPLQDM_TRACE_Manager = new DBManager(DB,'trace','spfnut');
        //$this->WPLQDM_TRACE_Manager->debug_mode_on();
        $params = array(':barc4' => $barc4);
        if($side == 1)
        {
            $sql = "SELECT 	
                        com.barc4,
                        psg1.stencil_nr AS stencil_nr,
                        psg1.idx AS stencil_idx,
                        sqg1.squeegee_width AS squeegee_width,
                        ppg1.paste_name AS paste_name,
                        sug1.name AS support_name
                   FROM scs_complement com
                   LEFT OUTER JOIN part_stencil_groups psg1 ON com.wabco1_stencil_group_id = psg1.stencil_group_id
                   LEFT OUTER JOIN part_squeegee_groups sqg1 ON com.wabco1_squeegee_group_id = sqg1.squeegee_group_id
                   LEFT OUTER JOIN part_paste_groups ppg1 ON com.wabco1_paste_group_id = ppg1.paste_group_id
                   LEFT OUTER JOIN part_support_groups sug1 ON com.wabco1_support_group_id = sug1.support_group_id
                   WHERE com.barc4 = :barc4 AND com.VALID = 1
                   ";
        }
        if($side == 2) {
            $sql = "SELECT 	
                        com.barc4,						
                        psg2.stencil_nr AS stencil_nr,
                        psg2.idx AS stencil_idx,
                        sqg2.squeegee_width AS squeegee_width,
                        ppg2.paste_name AS paste_name,
                        sug2.name AS support_name
                   FROM scs_complement com
                   LEFT OUTER JOIN part_stencil_groups psg2 ON com.wabco2_stencil_group_id = psg2.stencil_group_id
                   LEFT OUTER JOIN part_squeegee_groups sqg2 ON com.wabco2_squeegee_group_id = sqg2.squeegee_group_id
                   LEFT OUTER JOIN part_paste_groups ppg2 ON com.wabco2_paste_group_id = ppg2.paste_group_id
                   LEFT OUTER JOIN part_support_groups sug2 ON com.wabco2_support_group_id = sug2.support_group_id
                   WHERE com.barc4 = :barc4 AND com.VALID = 1 
                   ";
        }

        $res = array();
        $this->WPLQDM_TRACE_Manager->doSql($sql, $res, $params);
        return $res;
    }

    /**
     * @brief Finds barcodes matching a partial barcode pattern.
     *
     * Searches the product database for barcodes that match the provided pattern.
     * Uses LIKE operator with wildcards to find partial matches.
     *
     * @param string $barc4 Partial or complete barcode to search for
     * @return array|false Array of matching barcode records or false on error
     * @access public
     */
    public function findBarcode($barc4){
        try{
            //$this->WPLQDM_Manager->debug_mode_on();
            $sql = "SELECT * FROM PROG_PRODUCT_NR WHERE barc4 LIKE  :barc4";
            $params = array(':barc4' => '%'.$barc4.'%');
            $res = array();
            $this->WPLQDM_Manager->doSql($sql, $res, $params);
            return $res;
        }catch (Exception $e){
            return false;
        }
    }
    
    /**
     * @brief Finds new barcodes matching a partial barcode pattern.
     *
     * Similar to findBarcode but specifically used for searching new barcodes.
     * The implementation is currently identical to findBarcode.
     *
     * @param string $newbarc4 Partial or complete barcode to search for
     * @return array|false Array of matching barcode records or false on error
     * @access public
     */
    public function findnewBarcode($newbarc4){
        try{
            //$this->WPLQDM_Manager->debug_mode_on();
            $sql = "SELECT * FROM PROG_PRODUCT_NR WHERE barc4 LIKE  :newbarc4";
            $params = array(':newbarc4' => '%'.$newbarc4.'%');
            $res = array();
            $this->WPLQDM_Manager->doSql($sql, $res, $params);
            return $res;
        }catch (Exception $e){
            return false;
        }
    }

    /**
     * @brief Retrieves the document number for a barcode and side.
     *
     * Fetches the document number associated with a specific barcode and side
     * from the instruction header table.
     *
     * @param string $barc4 Barcode identifier
     * @param int $side Side identifier (1 or 2)
     * @return string|null|false Document number, null if not found, or false on error
     * @access public
     */
    public function getDocNrByBarcode($barc4, $side) {
        try {
            $sql = "SELECT DOC_NR FROM BIM_SMD_IM_HEADER WHERE BARC4 = :barc4 and SIDE = :side";
            $params = array(':barc4' => $barc4,':side' => $side);
            $res = array();
            $this->WPLQDM_Manager->doSql($sql, $res, $params);
            return isset($res['DOC_NR'][0]) ? $res['DOC_NR'][0] : null;
        } catch (Exception $e) {
            return false;
        }
    }

    /**
     * @brief Loads general information for a barcode and side.
     *
     * Retrieves all general information fields for a specified barcode and side
     * from the general information table.
     *
     * @param string $barc4 Barcode identifier
     * @param int $side Side identifier (1 or 2)
     * @return array|false General information records or false on error
     * @access public
     */
    public function loadGeneralInformation($barc4, $side)
    {
        try{
            //$this->WPLQDM_Manager->debug_mode_on();
            $sql = "SELECT * from BIM_SMD_IM_GEN_INFORM inform 
                WHERE inform.barc4 = :barc4 and inform.side = :side";
            $params = array(':barc4' => $barc4,':side' => $side);
            $res = array();
            $this->WPLQDM_Manager->doSql($sql, $res, $params);
            return $res;
        }catch (Exception $e){
            return false;
        }
    }

    /**
     * @brief Inserts a print program record.
     *
     * Creates a new entry in the print program table associating a program
     * with a barcode, side, and production line.
     *
     * @param string $barc4 Barcode identifier
     * @param int $side Side identifier (1 or 2)
     * @param string $program Program name/identifier
     * @param string $line Production line identifier
     * @return int|false Number of affected rows or false on error
     * @access public
     */
    public function insertPrintProgIM($barc4, $side, $program, $line){
        try{
            //$this->WPLQDM_Manager->debug_mode_on();
            $params = array(":barc4" => $barc4, ':side' => $side, ":program" => $program, ":line" => $line);
            $sql ="INSERT INTO BIM_SMD_IM_DEV_PRINT_PROG (BARC4, SIDE, PROGRAM, LINE) 
                   VALUES (:barc4, :side, :program, :line)";
            $res = array();
            $this->WPLQDM_Manager->doSql($sql, $res, $params, false);
            return $this->WPLQDM_Manager->get_numrow();
        }catch (Exception $e){
            return false;
        }
    }

    /**
     * @brief Inserts a dock program record.
     *
     * Creates a new entry in the dock program table associating a program
     * with a barcode, side, and production line.
     *
     * @param string $barc4 Barcode identifier
     * @param int $side Side identifier (1 or 2)
     * @param string $program Program name/identifier
     * @param string $line Production line identifier
     * @return int|false Number of affected rows or false on error
     * @access public
     */
    public function insertDockProIM($barc4, $side,  $program, $line){
        try{
            // $this->WPLQDM_Manager->debug_mode_on();
            $params = array(":barc4" => $barc4, ":side" => $side, ":program" => $program, ":line" => $line);
            $sql ="INSERT INTO BIM_SMD_IM_DOCK_DEV_PRO (BARC4, SIDE, PROGRAM, LINE) 
                   VALUES (:barc4, :side, :program, :line)";
            $res = array();
            $this->WPLQDM_Manager->doSql($sql, $res, $params, false);
            return $this->WPLQDM_Manager->get_numrow();
        }catch (Exception $e){
            return false;
        }
    }

    /**
     * @brief Inserts a note record.
     *
     * Creates a new entry in the notes table with the provided text,
     * image reference, and category, associated with a barcode and side.
     *
     * @param string $barc4 Barcode identifier
     * @param int $side Side identifier (1 or 2)
     * @param string $note Note text content
     * @param string $imageComment Image filename/reference
     * @param int $noteCategory Category identifier for the note
     * @return int|false Number of affected rows or false on error
     * @access public
     */
    public function insertNoteIM($barc4, $side, $note, $imageComment, $noteCategory){
        try{
            //$this->WPLQDM_Manager->debug_mode_on();
            $params = array(":barc4" => $barc4, ":side" => $side, ':note' => $note, ':imageComment' => $imageComment, ':noteCategory' => $noteCategory);
            $sql ="INSERT INTO BIM_SMD_IM_NOTE (BARC4, SIDE, NOTE, IMG_NOTE, CATEGORY) 
                   VALUES (:barc4, :side, :note, :imageComment, :noteCategory)";
            $res = array();
            $this->WPLQDM_Manager->doSql($sql, $res, $params, false);
            return $this->WPLQDM_Manager->get_numrow();
        }catch (Exception $e){
            return false;
        }
    }

    /**
     * @brief Retrieves notes for a barcode and side.
     *
     * Gets all notes associated with a barcode and side, including their
     * category information and typical comment details through joins.
     * Results are ordered by note ID.
     *
     * @param string $barc4 Barcode identifier
     * @param int $side Side identifier (1 or 2)
     * @return array|false Notes records or false on error
     * @access public
     */
    public function notes($barc4, $side)
    {
        try{
            //$this->WPLQDM_Manager->debug_mode_on();
            $sql = "SELECT note.barc4, note.side, note.img_note, note.id_note, note.id_parent_note, tc.category, tc.note, cn.category  as catText from BIM_SMD_IM_NOTE note
                    INNER JOIN BIM_SMD_IM_TYPICAL_COMMENT tc ON tc.ID_COMMENT = note.ID_PARENT_NOTE
                    INNER JOIN BIM_SMD_IM_CATEGORY_NOTE cn ON cn.ID = tc.category
                WHERE note.barc4 = :barc4 and note.side = :side
                ORDER BY ID_NOTE ASC
                ";
            $params = array(':barc4' => $barc4,':side' => $side);
            $res = array();
            $this->WPLQDM_Manager->doSql($sql, $res, $params);
            return $res;
        }catch (Exception $e){
            return false;
        }
    }

    /**
     * @brief Prepares a SQL-friendly string of quoted device identifiers.
     *
     * Converts an array of device identifiers to a comma-separated string
     * with each value properly quoted for use in SQL IN clauses.
     *
     * @param array $devices Array of device identifiers
     * @return string Comma-separated quoted string of device identifiers
     * @access private
     */
    private function prepareLine($devices){
        $deviceSql = '';
        $deviceSqlIndex = 0;
        foreach ($devices as $device) {
            $deviceSql .= "'".$device."'";
            if(count($devices) != $deviceSqlIndex+1)
            {
                $deviceSql .= ",";
            }
            $deviceSqlIndex++;
        }
        return $deviceSql;
    }

    /**
     * @brief Retrieves print programs for a barcode, side, and set of devices.
     *
     * Gets all print program entries for the specified barcode and side
     * that match any of the provided device identifiers.
     *
     * @param string $barc4 Barcode identifier
     * @param int $side Side identifier (1 or 2)
     * @param array $devices Array of device identifiers to include
     * @return array|false Print program records or false on error
     * @access public
     */
    public function printProg($barc4, $side, $devices)
    {
        try{
            $deviceSql = $this->prepareLine($devices);
            //$this->WPLQDM_Manager->debug_mode_on();
            $sql = "SELECT * from BIM_SMD_IM_DEV_PRINT_PROG 
                WHERE barc4 = :barc4 and side = :side AND LINE IN($deviceSql)";
            $params = array(':barc4' => $barc4,':side' => $side);
            $res = array();
            $this->WPLQDM_Manager->doSql($sql, $res, $params);
            return $res;
        }catch (Exception $e){
            return false;
        }
    }

    /**
     * @brief Retrieves dock programs for a barcode, side, and set of devices.
     *
     * Gets all dock program entries for the specified barcode and side
     * that match any of the provided device identifiers.
     *
     * @param string $barc4 Barcode identifier
     * @param int $side Side identifier (1 or 2)
     * @param array $devices Array of device identifiers to include
     * @return array|false Dock program records or false on error
     * @access public
     */
    public function dockPro($barc4, $side, $devices)
    {
        try{
            //$this->WPLQDM_Manager->debug_mode_on();
            $deviceSql = $this->prepareLine($devices);
            $sql = "SELECT * from BIM_SMD_IM_DOCK_DEV_PRO 
                 WHERE barc4 = :barc4 and side = :side AND LINE IN($deviceSql)";
            $params = array(':barc4' => $barc4,':side' => $side);
            $res = array();
            $this->WPLQDM_Manager->doSql($sql, $res, $params);
            return $res;
        }catch (Exception $e){
            return false;
        }
    }

    /**
     * @brief Retrieves header information for SMD maps.
     *
     * Gets detailed map information including dimensions, components count,
     * and creation date for a specific barcode, side, and production line
     * by joining multiple SMD-related tables.
     *
     * @param string $barc4 Barcode identifier
     * @param int $side Side identifier (1 or 2)
     * @param string $chosenLine Production line identifier
     * @return array|false SMD map header information or false on error
     * @access public
     */
    public function headerSmdMap($barc4, $side, $chosenLine)
    {
        try{
            $sql = "SELECT bsm.MAP_ID, bsm.LINE, bsm.PROGRAM, bsm.VERSION, bsm.COM, bsm.AUTHOR, bsm.PCB_LENGTH, bsm.PCB_WIDTH, bsm.THICKNESS, bsm.SIDE, msms.QUANTITY_COMPONENTS, msms.NUMBER_FEEDERS, to_char(msms.CREATE_DATE,'YY/MM/DD HH24:MI:SS') as CREATE_DATE  FROM BIM_SMD_IM_DOCK_DEV_PRO bsi
                    JOIN  BIM_SMD_MAPS bsm ON bsm.PROGRAM = bsi.PROGRAM
                    JOIN BIM_SMD_MODULES msms ON msms.MAP_ID = bsm.MAP_ID
                    WHERE  bsi.BARC4=:barc4 AND bsi.SIDE=:side AND bsi.LINE = :chosenLine";
            $params = array('barc4' => $barc4, 'side' => $side, 'chosenLine' => $chosenLine);
            $res = array();
            $this->WPLQDM_Manager->doSql($sql, $res, $params);
            return $res;
        }catch (Exception $e){
            return false;
        }
    }

    /**
     * @brief Retrieves detailed SMD map conversion data.
     *
     * Gets module-specific conversion information for a given module number
     * and map ID, limited to the most recent module version.
     *
     * @param int $m Module number
     * @param int $map_id Map identifier
     * @return array|false SMD map conversion records or false on error
     * @access public
     */
    public function tableSmdMap($m, $map_id){
        try{
            $sql = "SELECT * FROM BIM_SMD_CONVERSION_MAPS WHERE MODULE_ID = (SELECT MAX(MODULE_ID) as module_id_max FROM BIM_SMD_MODULES WHERE MAP_ID = $map_id) AND MODULE_NUMBER = $m";            
            $params = array();
            $res = array();
            $this->WPLQDM_Manager->doSql($sql, $res, $params);
            return $res;
        }catch (Exception $e){
            return false;
        }
    }

    /**
     * @brief Retrieves information about an employee by ID.
     *
     * Looks up employee details in the MITARBEITER table based on the
     * provided employee ID (case insensitive search).
     *
     * @param string $mid Employee identifier
     * @return array|false Employee records or false on error
     * @access public
     */
    public function getMidInformation($mid){
        try{
            $params = array(':mid'=>$mid);
            $sql = "SELECT * FROM MITARBEITER WHERE UPPER(MID)=UPPER(:mid)";
            //$this->WPLQDM_Manager->debug_mode_on();
            $this->WPLQDM_Manager->doSql($sql, $res, $params);
            return $res;
        }catch (Exception $e){
            return $res;
        }
    }

    /**
     * @brief Retrieves reserved components for a specific production area.
     *
     * Gets a list of components that have been reserved for a particular
     * production area (currently hardcoded to "WPL1R"). Includes component
     * identifiers, work process numbers, and location information.
     *
     * @return array|string Component reservation records or error message on failure
     * @access public
     */
    public function getReserved()
    {
        $this->WPLQDM_TRACE_Manager = new DBManager(DB,'trace','spfnut');

        $res = array();
        $remark = "WPL1R"; //$_SESSION['EWA']['AreaInfo']['areaRemark']
        $sql = "select prg.wpn, er.reel_group_id, er.reel_id, ea.area_name, nvl(sscc, er.reel_group_id||er.reel_id) as num
                  from ewa_reservations er
                  join part_reel_groups prg on (er.reel_group_id = prg.reel_group_id)
                  join part_reels pr on(er.reel_group_id = pr.reel_group_id and er.reel_id = pr.reel_id)
                  join ewa_areas ea on (er.area_id = ea.area_id)
                 where er.remark = :remark
                 order by er.order_date desc";
        $params = array(':remark' => $remark);
        try {
            $this->WPLQDM_TRACE_Manager->doSql($sql, $res, $params);
        }catch (Exception $e){
            return $this->WPLQDM_TRACE_Manager->get_oci_error();
        }
        return $res;
    }
}