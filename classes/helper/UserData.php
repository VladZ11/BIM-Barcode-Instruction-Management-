<?php

/**
 * @author Vladyslav Zhyokin
 */

/**
 * @brief Helper class for managing user data in the BIM system.
 *
 * This class implements the singleton pattern to provide global access to 
 * the current user's information throughout the application. It stores essential
 * user details such as name, code, role, and identification numbers.
 * 
 * @class Helper_UserData
 */
class Helper_UserData
{
    /**
     * @brief Singleton instance of the class
     * @var Helper_UserData|null
     * @access static
     */
    static $instance;

    /**
     * @brief User's first name
     * @var string|null
     * @access private
     */
    private $ufname = null;

    /**
     * @brief User's last name
     * @var string|null
     * @access private
     */
    private $ulname = null;

    /**
     * @brief User's identifying code
     * @var string|null
     * @access private
     */
    private $uscode = null;

    /**
     * @brief User's IM identifier
     * @var string|null
     * @access private
     */
    private $uimid = null;

    /**
     * @brief User's role in the system (default: USER)
     * @var string
     * @access private
     */
    private $usrole = 'USER';

    /**
     * @brief Private constructor to prevent direct instantiation
     * 
     * Part of the singleton pattern implementation that ensures
     * only one instance of this class exists.
     *
     * @access private
     */
    private function __construct()
    {
    }

    /**
     * @brief Private clone method to prevent duplication
     * 
     * Prevents creating copies of the singleton instance through cloning.
     *
     * @access private
     */
    private function __clone()
    {
    }

    /**
     * @brief Returns the singleton instance of the class
     * 
     * Creates a new instance if one doesn't already exist,
     * otherwise returns the existing instance.
     *
     * @return Helper_UserData The singleton instance
     * @access public static
     */
    static function instance()
    {
        if (empty(self::$instance)) self::$instance = new Helper_UserData;
        return self::$instance;
    }

    /**
     * @brief Sets the user's first name
     *
     * @param string $value The user's first name
     * @return void
     * @access public
     */
    public function setUserName($value)
    {
        $this->ufname = $value;
    }

    /**
     * @brief Gets the user's first name
     *
     * @return string|null The user's first name or null if not set
     * @access public
     */
    public function getUserName()
    {
        return $this->ufname;
    }

    /**
     * @brief Sets the user's last name
     *
     * @param string $value The user's last name
     * @return void
     * @access public
     */
    public function setUserLastName($value)
    {
        $this->ulname = $value;
    }

    /**
     * @brief Gets the user's last name
     *
     * @return string|null The user's last name or null if not set
     * @access public
     */
    public function getUserLastName()
    {
        return $this->ulname;
    }

    /**
     * @brief Sets the user's identifying code
     *
     * @param string $value The user's code
     * @return void
     * @access public
     */
    public function setUserCode($value)
    {
        $this->uscode = $value;
    }

    /**
     * @brief Gets the user's identifying code
     *
     * @return string|null The user's code or null if not set
     * @access public
     */
    public function getUserCode()
    {
        return $this->uscode;
    }

    /**
     * @brief Sets the user's role in the system
     *
     * @param string $value The user's role (e.g., 'USER', 'ADMIN')
     * @return void
     * @access public
     */
    public function setUserRole($value)
    {
        $this->usrole = $value;
    }

    /**
     * @brief Gets the user's role in the system
     *
     * @return string The user's role (defaults to 'USER' if not set)
     * @access public
     */
    public function getUserRole()
    {
        return $this->usrole;
    }

    /**
     * @brief Sets the user's IM identifier
     *
     * @param string $value The user's IM identifier
     * @return void
     * @access public
     */
    public function setUserImid($value)
    {
        $this->uimid = $value;
    }

    /**
     * @brief Gets the user's IM identifier
     *
     * @return string|null The user's IM identifier or null if not set
     * @access public
     */
    public function getUserImid()
    {
        return $this->uimid;
    }
}