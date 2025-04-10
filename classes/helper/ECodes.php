<?php

/**
 * @author Vladyslav Zhyokin
 */

/**
 * @brief Error code handling class for the BIM system.
 *
 * This class is responsible for mapping error codes to appropriate messages
 * and error types. It provides a centralized way to handle error messages
 * throughout the application, supporting multiple error scenarios with
 * customizable messages.
 * 
 * @class Helper_ECodes
 */
class Helper_ECodes{

    /**
     * @brief The error message text
     * @var string
     * @access private
     */
    private $emsg = '';
    
    /**
     * @brief Reference number (typically a WPN or component number)
     * @var string
     * @access private
     */
    private $num = '';
    
    /**
     * @brief Additional contextual message or parameter
     * @var string
     * @access private
     */
    private $msg = '';
    
    /**
     * @brief The error code identifier
     * @var string
     * @access private
     */
    private $ecode = '0000';
    
    /**
     * @brief The error type (danger, warning, success)
     * @var string
     * @access private
     */
    private $etype = 'danger';

    /**
     * @brief Constructs a new error code object.
     * 
     * Initializes an error code object with the specified code, reference number,
     * and optional additional message.
     *
     * @param string $ecode The error code
     * @param string $num The reference number (WPN, component ID, etc.)
     * @param string|null $msg Optional additional contextual message
     * @access public
     */
    public function __construct($ecode, $num, $msg = null)
    {
        $this->ecode = $ecode;
        $this->num = $num;
        $this->msg = $msg;
    }

    /**
     * @brief Gets the error message.
     * 
     * Returns the error message text after ensuring it's properly set
     * based on the error code.
     *
     * @return string The error message
     * @access public
     */
    public function getEmsg()
    {
        $this->setEmsg();
        return $this->emsg;
    }

    /**
     * @brief Gets the error type.
     * 
     * Returns the error type (danger, warning, success) after ensuring
     * it's properly set based on the error code.
     *
     * @return string The error type
     * @access public
     */
    public function getEtype()
    {
        $this->setEmsg();
        return $this->etype;
    }

    /**
     * @brief Sets the error message and type based on error code.
     * 
     * Maps each error code to its corresponding message and type.
     * Contains messages for various error scenarios like validation errors,
     * database errors, component status errors, and more.
     *
     * @return void
     * @access private
     */
    private function setEmsg()
    {
        switch ($this->ecode) {
            case '0000':
                $this->emsg = "OK";
                $this->etype = "success";
                break;
            case '0001':
                $this->emsg = "Wprowadzony numer DID/SSCC jest nieprawidłowy. Prawidłowy numer składa się z 10 cyfr w przypadku numeru DID/WPN lub 20 w przypadku SSCC";
                break;
            case '0002':
                $this->emsg = "Wprowadzony numer nie został odnaleziony w bazie danych.";
                break;
            case '0003':
                $this->emsg = "Wprowadzono numer WPN gdy oczekiwano numeru DID lub SSCC.";
                break;
            case '0004':
                $num = new Helper_NumRecognizer($this->num);
                $this->emsg = "Wprowadzony numer jest zezłomowany. Data złomowania: ".$num->getScrapDate();
                break;
            case '0005':
                $this->emsg = "Wprowadzony numer nie znajduje się na magazynie.";
                break;
            case '0006':
                $num = new Helper_NumRecognizer($this->num);
                $this->emsg = "Transkacja Zwroty nie zezwala na przeniesienie części z obszaru ".$num->getAreaName()." na obszar WAREHOUSE. Skorzystaj z odpowiedniej transakcji.";
                break;
            case '0007':
                $this->emsg = "Transakcja Zwroty nie zezwala na przeniesienie części buforowanych (Pasty, Kleje).";
                break;
            case '0008':
                $this->emsg = "Dla podanego numeru WPN/DID/SSCC w zadanym przedziale czasowym nie istnieją wpisy w historii. Sprawdź czy wpisałeś poprawny numer i czy część została przyjęta do stystemu EWA.";
                $this->etype = 'warning';
                break;
            case '0009':
                $this->emsg = "Oczekiwany jest WPN. Wprowadzony numer to DID/SSCC.";
                break;
            case '0010':
                $this->emsg = "Nie można zamawiać Pasty lub Kleju";
                break;
            case '0011':
                $this->emsg = "Nie można zamawiać komponentów na obszar BUFOR";
                break;
            case '0012':
                $this->emsg = "Brak komponentu na magazynie";
                $this->etype = 'warning';
                break;
            case '0013':
                $this->emsg = "Przekroczony limit na produkcji";
                break;
            case '0014':
                $this->emsg = "Zdublowany WPN $this->num.";
                break;
            case '0015':
                $this->emsg = "Błąd bazy danych<br>Skontaktuj się z zespołem wsparcia IT.";
                break;
            case '0016':
                $this->emsg = "Komponent $this->num znajduje się na obszarze $this->msg, z którego nie może zostać przeniesiony.";
                break;
            case '0017':
                $this->emsg = "Komponent $this->num znajduje się już na obszarze $this->msg. Przeniesienie nie jest wymagane.";
                $this->etype = 'warning';
                break;
            case '0018':
                $this->emsg = "Komponent $this->num już istnieje.";
                $this->etype = 'warning';
                break;
            case '0019':
                $this->emsg = "Nieprawidłowy format numeru WPN. Numer WPN składa się z 10 cyfr.";
                break;
            case '0019': // Note: This is a duplicate case
                $this->emsg = "Wszystkie pola formularza muszą być wypełnione. Proszę poprawnie wypełnić formularz.";
                break;
            case '0020':
                $this->emsg = "Komponent znajduje się na obszarze WAREHOUSE. Złomowanie z tego obszaru nie jest możliwe.";
                break;
            case '0021':
                $this->emsg = "Komponent jest już zablokowany na obszarze $this->msg.";
                $this->etype = 'warning';
                break;
            case '0022': // TRAX Error
                $this->emsg = $_SESSION['EWA']['Error'];
                unset($_SESSION['EWA']['Error']);
                break;
            case '0023': // GTIN Error
                $this->emsg = "Nie odnaleziono numeru GTIN w bazie danych EWY.";
                break;
            default:
                $this->emsg = "Nierozpoznany błąd.";
        }
    }

    /**
     * @brief Destructor to clean up resources.
     * 
     * Ensures the database connection is properly cleared when
     * the object is destroyed.
     *
     * @access public
     */
    public function __destruct()
    {
        $this->db = null;
    }
}