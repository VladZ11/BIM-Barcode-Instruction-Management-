<?php

/**
 * @author Vladyslav Zhyokin
 */

/**
 * @brief Configuration file for the Barcode Instruction Management (BIM) system.
 *
 * This file contains all essential configuration constants and parameters for 
 * connecting to databases, initializing Smarty templating engine, and setting 
 * various application-wide defaults.
 *
 * Key configurations include:
 * - Database connection parameters
 * - Database manager path
 * - Application name
 * - Smarty template engine settings
 * - Zend framework directory
 * - Controller class prefixes
 * - Default template file
 * - Error display settings
 */

// Database connection parameters
define("DB", "database_name");
$cO['db']['db'] = DB;
$cO['db']['username'] = "username";
$cO['db']['password'] = "password";

/**
 * Path to the database manager file
 * @var string
 */
$cO['dbManager'] = "C:\Inetpub\\wwwroot\\classes\\DBManager2.php";

/**
 * Application name identifier
 * @var string
 */
$cO['appName'] = "BIM";

/**
 * Smarty template engine configuration
 * @var array
 */
$cO['smarty']['dir'] = "C:\Inetpub\\wwwroot\\plugins\\smarty\\libs\\Smarty-2.6.22\\libs\\Smarty.class.php";
$cO['smarty']['template_dir'] = "templates";
$cO['smarty']['compile_dir'] = "C:\Inetpub\\wwwroot\\templates_c";
$cO['smarty']['compile_id'] = "BIM_09_04_2025";
// $cO['smarty']['cache_dir'] = "cache";

/**
 * Zend framework directory path
 * @var string
 */
$cO['Zend']['dir'] = "C:\Inetpub\\wwwroot\\plugins\\";

/**
 * Pre-controller authorization class name
 * @var string
 */
$cO['pre']['ControllerClass'] = "Pre_Authorization";

/**
 * Default index template file
 * @var string
 */
$cO['indexTpl'] = "home.htpl";

/**
 * Error display configuration
 * @var boolean
 */
@ini_set('display_errors', 'off');
//define('_PS_DEBUG_SQL_', true);