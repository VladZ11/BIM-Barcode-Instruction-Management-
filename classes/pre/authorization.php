<?php

/**
 * @author Vladyslav Zhyokin
 */

/**
 * @brief Authorization controller for the BIM system.
 *
 * This pre-controller class handles user authentication and authorization 
 * for the Barcode Instruction Management system. It loads user data from 
 * the session, determines user roles, and enforces access control restrictions
 * based on those roles. It also prepares user variables for display in templates.
 * 
 * @class Pre_Authorization
 * @extends W_Preoperation_preController
 */
class Pre_Authorization extends W_Preoperation_preController{
    
    /**
     * @brief Main entry point for the authorization process.
     *
     * This method runs before the main controller action. It:
     * 1. Sets the theme from cookies or uses default
     * 2. Retrieves user data from session
     * 3. Initializes access control
     * 4. Sets user properties based on session data
     * 5. Checks if user has permission to access the requested resource
     *
     * @return void
     * @access public
     */
    public function preMethod(){
        $this->pagec->ThemeName = (isset($_COOKIE['ThemeName'])) ? $_COOKIE['ThemeName'] : 'flatly';
        $userTrumna = isset($_SESSION['Trumna']['User']) ? $_SESSION['Trumna']['User'] : array();
        $acl = new Helper_Acl();
        $user = Helper_UserData::instance();
        if(isset($userTrumna["USER_FIRSTNAME"][0]))
        {
            $qdmPassWord = implode(",",$userTrumna["QDMPASSWORD"][0]);
            $user->setUserName($userTrumna["USER_FIRSTNAME"][0]);
            $user->setUserLastName($userTrumna["USER_SURNAME"][0]);
            $user->setUserCode($qdmPassWord);
            $user->setUserRole(self::userRole($qdmPassWord));
            $user->setUserImid($userTrumna["IMID"][0]);
            $this->prepSmartyUserVars($user);
            self::isAllowed($user, $acl);
        }
        else{
            $user->setUserRole(self::userRole(""));
            $this->prepSmartyUserVars($user);
            self::isAllowed($user, $acl);
        }
    }

    /**
     * @brief Checks if a user is allowed to access a resource.
     *
     * Uses the ACL system to verify if the user's role has permission to access
     * the requested controller. If access is denied, redirects to the index page.
     *
     * @param Helper_UserData $user The user object containing role information
     * @param Helper_Acl $acl The access control list object
     * @return void
     * @access private
     */
    private static function isAllowed(Helper_UserData $user, Helper_Acl $acl)
    {
        $resource = (isset($_REQUEST['controller'])) ? $_REQUEST['controller'] : 'index';
        if (!$acl->isAllowed($user->getUserRole(), $resource)) {
            header('Location: index.php?controller=index');
        }
    }

    /**
     * @brief Determines the user's role based on password.
     *
     * Analyzes the user password to determine appropriate role:
     * - If password contains 'bIM', assigns ADMIN role
     * - Otherwise, assigns GUEST role
     *
     * @param string $userpasswd The user's password string
     * @return string The assigned role ('ADMIN' or 'GUEST')
     * @access private
     */
    private static function userRole($userpasswd)
    {
        $role = 'GUEST';
        if(preg_match('/bIM/', $userpasswd)){
            $role = 'ADMIN';
        }
        return $role;
    }

    /**
     * @brief Prepares user variables for Smarty templates.
     *
     * Takes user information from the UserData object and assigns
     * it to the page context for use in Smarty templates.
     *
     * @param Helper_UserData $user The user data object
     * @return void
     * @access public
     */
    public function prepSmartyUserVars(Helper_UserData $user)
    {
        $this->pagec->userName = $user->getUserName();
        $this->pagec->userLastName = $user->getUserLastName();
        $this->pagec->userCode = $user->getUserCode();
        $this->pagec->userRole = $user->getUserRole();
        $this->pagec->userImid = $user->getUserImid();
    }
}