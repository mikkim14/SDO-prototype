<?php
/**
 * Configuration File
 */

// Database Configuration
define('DB_HOST', '127.0.0.1');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'ghg_database');
define('DB_PORT', 3306);

// Application Settings
define('APP_NAME', 'GHG Management System');
define('APP_VERSION', '1.0');
define('BASE_URL', 'https://yourdomain.com/ghg/');
define('SITE_ROOT', dirname(__DIR__) . '/');

// Session Configuration
define('SESSION_TIMEOUT', 30 * 60); // 30 minutes

// Campus List
define('CAMPUSES', [
    'Alangilan',
    'ARASOF-Nasugbu',
    'Balayan',
    'Central',
    'JPLPC-Malvar',
    'Lipa',
    'Lemery',
    'Lobo',
    'Mabini',
    'Pablo Borbon',
    'Rosario',
    'San Juan'
]);

// Offices
define('OFFICES', [
    'Central Sustainable Office',
    'Sustainable Development Office',
    'Environmental Management Unit',
    'External Affair',
    'Procurement Office'
]);

// Include classes
require_once __DIR__ . '/Database.php';
require_once __DIR__ . '/Auth.php';
require_once __DIR__ . '/Helper.php';
require_once __DIR__ . '/AccessControl.php';

// Initialize database connection
$database = new Database();
$db = $database->connect();

// Check for session timeout
if (isset($_SESSION['last_activity'])) {
    $elapsed = time() - $_SESSION['last_activity'];
    if ($elapsed > SESSION_TIMEOUT) {
        session_destroy();
        if (basename($_SERVER['PHP_SELF']) != 'login.php' && basename($_SERVER['PHP_SELF']) != 'index.php') {
            header("Location: " . BASE_URL . "login.php?timeout=1");
            exit();
        }
    }
}
$_SESSION['last_activity'] = time();
?>
