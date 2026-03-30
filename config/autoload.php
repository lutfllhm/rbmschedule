<?php
/**
 * Autoloader Configuration
 * 
 * Automatically loads classes and helpers
 * 
 * @package RBM\Schedule
 * @author RBM Development Team
 * @version 1.0.0
 */

// Define base paths
define('ROOT_PATH', dirname(__DIR__));
define('CLASSES_PATH', ROOT_PATH . '/classes');
define('HELPERS_PATH', ROOT_PATH . '/helpers');

/**
 * Autoload classes
 * 
 * @param string $className Class name to load
 */
spl_autoload_register(function ($className) {
    $classFile = CLASSES_PATH . '/' . $className . '.php';
    if (file_exists($classFile)) {
        require_once $classFile;
    }
});

// Load helper functions
if (file_exists(HELPERS_PATH . '/functions.php')) {
    require_once HELPERS_PATH . '/functions.php';
}

// Load path configuration
if (file_exists(ROOT_PATH . '/config/paths.php')) {
    require_once ROOT_PATH . '/config/paths.php';
}

// Initialize logger
if (class_exists('Logger')) {
    Logger::init();
}


