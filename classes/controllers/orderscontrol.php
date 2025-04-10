<?php

/**
 * @author Vladyslav Zhyokin
 */

/**
 * @brief Orders controller for handling reservation and orders functionality.
 *
 * Manages order processing workflow including reservations, status changes,
 * confirmations, and retrieving order details from the database.
 * 
 * @class Controllers_OrdersControl
 * @extends W_Controller_Controller
 */
class Controllers_OrdersControl extends W_Controller_Controller
{
    /**
     * @brief Displays the main orders page.
     * 
     * Retrieves and sets up reservation data, error messages,
     * and other variables required for the main page view.
     *
     * @return void
     * @access public
     */
    public function mainPageAction()
    {
        $this->pagec->reservation = $this->sqlc->getReserved();
        $this->showError($this->request->ecode, $this->request->num);
        $this->pagec->errors = $errors = $this->getReservationError();
    }
    
    /**
     * @brief Processes error codes and displays appropriate messages.
     * 
     * Creates an error object based on code and number, retrieves the
     * appropriate message and type, then outputs JSON-encoded error information.
     *
     * @param string $ecode Error code
     * @param string $number Reference number (typically WPN)
     * @param string $emessage Optional additional error message
     * @return void Outputs JSON directly
     * @access private
     */
    private function showError($ecode, $number, $emessage)
    {
        $error = new Helper_ECodes($ecode, $number);
        $getEmsg = $error->getEmsg();
        $getEtype = $error->getEtype();

        $error = json_encode(array("number" => $number, "ecode" => $ecode, "getEmsg" => $getEmsg, "getEtype" => $getEtype, "emessage" => $emessage));
        echo json_encode($error);
    }

    /**
     * @brief Adds an item to the reservation system.
     * 
     * Validates the WPN (Work Process Number) format, then calls the SQL
     * connector to add the reservation. Shows appropriate errors if validation fails.
     *
     * @return void Redirects or outputs error JSON
     * @access public
     */
    public function addToReservationAction()
    {
        $wpn = htmlspecialchars($this->request->orderwpn);
        $num = new Helper_NumRecognizer($wpn);
        if($num->getNumberType() != 'WPN'){
            header("Location: index.php?controller=orders&action=mainPage&ecode=0002&num=$wpn");
            exit;
        }else{
            $this->sqlc->addToReservation($wpn, $_SESSION['EWA']['AreaInfo']['area'], $_SESSION['EWA']['AreaInfo']['areaRemark'], $ecode, $emessage);
            $this->showError($ecode, $wpn, $emessage);
        }
    }
    
    /**
     * @brief Confirms a reservation in the system.
     * 
     * Uses the area remark from session data to identify which reservation
     * to confirm. Returns 1 for success or 0 for failure.
     *
     * @return void Outputs 1 or 0 directly
     * @access public
     */
    public function reservationConfirmAction()
    {
        $res = $this->sqlc->reservationConfirm($_SESSION['EWA']['AreaInfo']['areaRemark']);
        if (preg_match('/ORA-/', $res)) {
            echo 0;
            exit;
        } else {
            echo 1;
            exit;
        }
    }

    /**
     * @brief Cancels an existing reservation.
     * 
     * Removes a reservation from the system using the area remark stored
     * in session data. Returns 1 for success or 0 for failure.
     *
     * @return void Outputs 1 or 0 directly
     * @access public
     */
    public function reservationCancelAction()
    {
        $res = $this->sqlc->deleteReservation($_SESSION['EWA']['AreaInfo']['areaRemark']);
        if (preg_match('/ORA-/', $res)) {
            echo 0;
            exit;
        } else {
            echo 1;
            exit;
        }
    }
}