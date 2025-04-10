<?php

/**
 * @file index.php
 * @brief Entry point for the Barcode Instruction Management (BIM) system
 * 
 * This is the main entry point for the BIM application. It initializes the environment,
 * sets up error handling, loads the WABCO framework, and bootstraps the application.
 * It handles session management, autoloading of classes, and provides basic
 * exception handling for the application lifecycle.
 *
 * @author Vladyslav Zhyokin
 * 
 * @section initialization Application Initialization
 * The initialization process includes:
 * - Setting session storage location and starting the session
 * - Configuring error display and reporting
 * - Defining framework directory paths
 * - Loading required framework files
 * - Initializing the autoloader
 * - Bootstrapping the application
 * - Providing exception handling
 * 
 * @note The session data is stored in C:\Temp
 * @note Error display is set to ON for debugging purposes
 * 
 * @see W_Autoloader_Autoloader Class responsible for autoloading application components
 * @see W_Bootstrap Class responsible for bootstrapping the application
 */
session_save_path('C:\\Temp');
session_start();
ini_set('display_errors', 'ON');
error_reporting(E_ALL);
define("WABCO_DIR", "C:\Inetpub\\wwwroot\\classes\\wabcoFramework\\");
/**
 *
 * @var path to wabcoFramework
 */
//define("WABCO_DIR", ROOT_DIR);

require_once(WABCO_DIR."bootstrap.php");
require_once(WABCO_DIR."autoloader/autoloader.php");
//require_once("C:\Inetpub\\wwwroot\\plugins\\ChartDirector\\lib\\phpchartdir.php");


W_Autoloader_Autoloader::getInstance(WABCO_DIR);
//exit();
try {
    $bootstrap = new W_Bootstrap(WABCO_DIR);
    $bootstrap->execute();
}catch (Exception $e) {
    echo "<div style=\"background:red; width: 100%; color: white;\">";
    echo 'Caught exception: ',  $e->getMessage(), "\n<br>";
    echo $e->getFile();
    echo "</div>";
}
