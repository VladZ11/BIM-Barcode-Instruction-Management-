<?php

/**
 * @author Vladyslav Zhyokin // Dariusz Bakiewicz
 */

/**
 * @brief SQL creator class for order management in the BIM system.
 *
 * This class handles database interactions related to component orders,
 * reservations, and warehouse location management. It provides functionality
 * for adding components to orders, managing reservations, updating component
 * locations, and handling transactions. It interacts primarily with the
 * 'ewa_orders', 'ewa_warehouse', and 'ewa_reservations' database tables.
 * 
 * @class sqlcreator_ordersSql
 * @extends W_sqlcreator_SQLCreator
 */
class sqlcreator_ordersSql extends W_sqlcreator_SQLCreator
{
    /**
     * @brief Database connection handler for TRACE database
     * @var DBManager|null
     * @access private
     */
    private $WPLQDM_TRACE_Manager;

    /**
     * @brief Adds components to orders based on area remark.
     *
     * Calls a stored procedure to process components from a specified area
     * and add them to the pending orders queue.
     *
     * @param string $arearemark The area identifier to process orders from
     * @return string|null Error message if an error occurs, null on success
     * @access public
     */
    public function addToOrders($arearemark)
    {
        $res = array();
        $sql = "call trace.ewa_add_to_orders(:arearemark)";
        $params = array(':arearemark' => $arearemark);
        $this->WPLQDM_Manager->doPlSql($sql, $res, $params);
        $error = $this->WPLQDM_Manager->get_oci_error();
        return $error;
    }

    /**
     * @brief Updates the area location for a component in the warehouse.
     *
     * Changes the area_id for a specific component identified by group ID and reel ID.
     * If no area is specified, uses the area from the session.
     *
     * @param string $gdid The component group identifier
     * @param string $rdid The component reel identifier
     * @param string|bool $area The target area name or false to use session area
     * @return array Result array with operation status and row count
     * @access public
     */
    public function updateAreaID($gdid, $rdid, $area = false)
    {
        if ($area == null)
            $area = $_SESSION['EWA']['AreaInfo']['area'];
        $r = array();
        $sql = "UPDATE ewa_warehouse 
                   SET area_id = (SELECT area_id FROM ewa_areas WHERE area_name = :area)
                 WHERE reel_group_id = :gdid 
                   AND reel_id = :rdid";
        $params = array(':area' => $area, ':gdid' => $gdid, ':rdid' => $rdid);
        try {
            $this->WPLQDM_Manager->doSql($sql, $r['RES'], $params);
            $r['INF']['N'] = $this->WPLQDM_Manager->get_numrow();
        }catch (Exception $e){
            $r['INF']['ORA_ERROR'] = $this->WPLQDM_Manager->get_oci_error();
            return $r;
        }
        return $r;
    }

    /**
     * @brief Deletes an order for a specific component.
     *
     * Removes a component order identified by group ID and reel ID from
     * the orders table.
     *
     * @param string $gdid The component group identifier
     * @param string $rdid The component reel identifier
     * @return array Result array with operation status and row count
     * @access public
     */
    public function deleteOrder($gdid, $rdid)
    {
        $r = array();
        $sql = "DELETE FROM ewa_orders
                      WHERE reel_group_id = :gdid
                        AND reel_id = :rdid";
        $params = array(':gdid' => $gdid, ':rdid' => $rdid);
        try {
            $this->WPLQDM_Manager->doSql($sql, $r['RES'], $params);
            $r['INF']['N'] = $this->WPLQDM_Manager->get_numrow();
        }catch (Exception $e){
            $r['INF']['ORA_ERROR'] = $this->WPLQDM_Manager->get_oci_error();
            return $r;
        }
        return $r;
    }

    /**
     * @brief Commits the current database transaction.
     *
     * Executes a COMMIT statement to finalize all pending database changes.
     *
     * @return bool Always returns true
     * @access public
     */
    public function commit() {
        $this->WPLQDM_Manager->doSql("commit", $res, array());
        return true;
    }

    /**
     * @brief Rolls back the current database transaction.
     *
     * Executes a ROLLBACK statement to cancel all pending database changes.
     *
     * @return bool Always returns true
     * @access public
     */
    public function rollback() {
        $this->WPLQDM_Manager->doSql("rollback", $res, array());
        return true;
    }

    /**
     * @brief Adds a component to the reservation system.
     *
     * Calls a stored function to reserve a component identified by WPN
     * in a specific area. Output parameters receive the result code and message.
     *
     * @param string $wpn Work Process Number of the component to reserve
     * @param string $areaname Target area name to reserve the component for
     * @param string $arearemark Area remark identifier
     * @param string &$ecode Output parameter to receive error code
     * @param string &$emessage Output parameter to receive error message
     * @return bool True on success, false on failure
     * @access public
     */
    public function addToReservation($wpn, $areaname, $arearemark, &$ecode, &$emessage)
    {
        $this->WPLQDM_TRACE_Manager = new DBManager(DB,'trace','spfnut');
        //$this->WPLQDM_TRACE_Manager->debug_mode_on();
        $res = array();
        $sql = "select * from table(ewa_add_to_reservation2(:wpn, :areaname, :arearemark))";
        $params = array(
            ':wpn' => $wpn,
            ':areaname' => $areaname,
            ':arearemark' => $arearemark
        );
        try {
            $this->WPLQDM_TRACE_Manager->doSql($sql, $res, $params, true);
            $ecode = $res['ECODE'][0];
            $emessage = $res['EMSSAGE'][0];
        }catch (Exception $e){
            return false;
        }
        return true;
    }

    /**
     * @brief Deletes all reservations for a specific area remark.
     *
     * Calls a stored procedure to remove all component reservations
     * associated with the given area remark.
     *
     * @param string $arearemark The area remark identifier
     * @return string|null Error message if an error occurs, null on success
     * @access public
     */
    public function deleteReservation($arearemark)
    {
        $res = array();
        $error = null;
        $sql = "call trace.ewa_del_from_reservation(:arearemark)";
        $params = array(':arearemark' => $arearemark);
        try {
            $this->WPLQDM_Manager->doSql($sql, $res, $params);
        }catch (Exception $e){
            return $this->WPLQDM_Manager->get_oci_error();
        }
        return $error;
    }

    /**
     * @brief Deletes a specific reservation by component ID.
     *
     * Calls a stored procedure to remove a component reservation
     * identified by the direct component ID.
     *
     * @param string $did Direct component identifier
     * @return string|null Error message if an error occurs, null on success
     * @access public
     */
    public function deleteReservationSR($did)
    {
        $res = array();
        $error = null;
        $sql = "call trace.ewa_del_from_reservation_sr($did)";
        try {
            $this->WPLQDM_Manager->doSql($sql, $res);
        }catch (Exception $e){
            return $this->WPLQDM_Manager->get_oci_error();
        }
        return $error;
    }

    /**
     * @brief Retrieves reservation log entries for a specific target.
     *
     * Queries the reservation log table for entries matching the specified
     * remark identifier.
     *
     * @param string $target The remark identifier to search for
     * @return array|string Log entries or error message on failure
     * @access public
     */
    public function getReservationLog($target)
    {
        $res = array();
        $sql = "select * from ewa_reservations_log where remark = :target";
        $params = array(':target' => $target);
        try {
            $this->WPLQDM_Manager->doSql($sql, $res, $params);
        }catch (Exception $e){
            return $this->WPLQDM_Manager->get_oci_error();
        }
        return $res;
    }

    /**
     * @brief Checks component availability by WPN.
     *
     * Queries the warehouse to find available components matching the
     * specified Work Process Number, grouped by area name with counts.
     * Returns default values if no components are found.
     *
     * @param string $wpn Work Process Number to check availability for
     * @return array Availability data with area names and counts
     * @access public
     */
    public function getAvailable($wpn)
    {
        $res = array();
        $sql = "select area_name, count(area_id) as n 
                  from ewa_warehouse 
                  join part_reel_groups using (reel_group_id) 
                  join ewa_areas using (area_id)
                 where wpn = :wpn group by area_name";
        $params = array(':wpn' => $wpn);
        try {
            $this->WPLQDM_Manager->doSql($sql, $res, $params);
        }catch (Exception $e){
            return $this->WPLQDM_Manager->get_oci_error();
        }
        return ($this->WPLQDM_Manager->get_numrow() == 0)? array('AREA_NAME' => array('DOSTĘPNE'), 'N' => array(0)) : $res;
    }
    
    /**
     * @brief Confirms a reservation for processing.
     *
     * Calls a stored procedure to finalize a reservation identified by
     * the area remark, making it ready for further processing.
     *
     * @param string $arearemark The area remark identifier for the reservation
     * @return string|null Error message if an error occurs, null on success
     * @access public
     */
    public function reservationConfirm($arearemark)
    {
        $res = array();
        $error = null;
        $sql = "call trace.ewa_confirm_reservation(:ar)";
        $params = array(':ar' => $arearemark);
        try {
            $this->WPLQDM_Manager->doSql($sql, $res, $params);
        }catch (Exception $e){
            return $this->WPLQDM_Manager->get_oci_error();
        }
        return $error;
    }
}