<?php
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

