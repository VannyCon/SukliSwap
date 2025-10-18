<?php
// require __DIR__ . '/../vendor/autoload.php';
// Set expiration date and time (YYYY-MM-DD HH:MM:SS format)
$expireDateTime = strtotime("2025-12-24 12:00:00"); // Change to your desired date & time
$currentDateTime = time();

// If the time has passed, prevent file inclusion
if ($currentDateTime >= $expireDateTime) {
    die("Access to this service is no longer available. Please contact the administrator.");
}
session_start();
//Database Config
define("H", "mysql-d77de55-vannycon001-3b2f.c.aivencloud.com:25521");
define("U", "avnadmin");
define("P", "AVNS_M8MYUL4UG_rvOxyfubU");
define("DB", "sukliswap_scheme");
// define("H", "localhost");
// define("U", "root");
// define("P", "");
// define("DB", "sukliswap_scheme");
define("URL", "http://localhost/Projects/sukliswap/");
define("FILEPATH", "C:\xampp\htdocs\Projects\sukliswap");
// date_default_timezone_set("Asia/Manila")
// $dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/..');
// $dotenv->load();
// session_start();
// define("H", $_ENV["DB_HOST"]);
// define("U", $_ENV["DB_USER"]);
// define("P", $_ENV["DB_PASS"]);
// define("DB", $_ENV["DB_NAME"]);
// define("URL", $_ENV["APP_URL"]);
// define("FILEPATH", $_ENV["FILE_PATH"]);



// Try to load environment variables from .env file (if it exists)
// $envFile = __DIR__ . '/../.env';
// if (file_exists($envFile)) {
//     try {
//         $dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/..');
//         $dotenv->load();
        
//         // Use environment variables if available
//         define("H", $_ENV["DB_HOST"] ?? "localhost");
//         define("U", $_ENV["DB_USER"] ?? "root");
//         define("P", $_ENV["DB_PASS"] ?? "");
//         define("DB", $_ENV["DB_NAME"] ?? "cementery_system_db");
//         define("URL", $_ENV["APP_URL"] ?? "http://localhost/Projects/cementry_system_mapgl/");
//         define("FILEPATH", $_ENV["FILE_PATH"] ?? "C:\xampp\htdocs\Projects\cementry_system_mapgl");
//     } catch (Exception $e) {
//         // Fallback to hardcoded values if .env loading fails
//         define("H", "mysql-d77de55-vannycon001-3b2f.c.aivencloud.com:25521");
//         define("U", "avnadmin");
//         define("P", "AVNS_M8MYUL4UG_rvOxyfubU");
//         define("DB", "cementery_system_db");
//         define("URL", "http://localhost/Projects/cementry_system_mapgl/");
//         define("FILEPATH", "C:\xampp\htdocs\Projects\cementry_system_mapgl");
//     }
// } else {
//     // Use hardcoded values for local development
//     define("H", "localhost");
//     define("U", "root");
//     define("P", "");
//     define("DB", "cementery_system_db");
//     define("URL", "http://localhost/Projects/cementry_system_mapgl/");
//     define("FILEPATH", "C:\xampp\htdocs\Projects\cementry_system_mapgl");
// }

date_default_timezone_set("Asia/Manila");
?><?php
require __DIR__ . '/../vendor/autoload.php';

// Set expiration date and time (YYYY-MM-DD HH:MM:SS format)
$expireDateTime = strtotime("2025-12-24 12:00:00"); // Change to your desired date & time
$currentDateTime = time();

// If the time has passed, prevent file inclusion
if ($currentDateTime >= $expireDateTime) {
    die("Access to this service is no longer available. Please contact the administrator.");
}

session_start();

// Load environment variables from .env file
try {
    $dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/..');
    $dotenv->load();
    
    // Use environment variables
    define("H", $_ENV["DB_HOST"]);
    define("U", $_ENV["DB_USER"]);
    define("P", $_ENV["DB_PASS"]);
    define("DB", $_ENV["DB_NAME"]);
    define("URL", $_ENV["APP_URL"]);
    define("FILEPATH", $_ENV["FILE_PATH"]);
    
    // Set timezone
    date_default_timezone_set($_ENV["TIMEZONE"] ?? "Asia/Manila");
} catch (Exception $e) {
    die("Configuration error: Unable to load environment variables. Please ensure .env file exists.");
}

?>

