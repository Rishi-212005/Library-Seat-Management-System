<?php
// Database configuration
define('DB_HOST', 'sql200.infinityfree.com');
define('DB_USERNAME', 'if0_39418937');
define('DB_PASSWORD', 'library2025');
define('DB_NAME', 'if0_39418937_library_management');

// Create connection
function getDBConnection() {
    try {
        $pdo = new PDO(
            "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4",
            DB_USERNAME,
            DB_PASSWORD,
            [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false
            ]
        );
        return $pdo;
    } catch (PDO\Exception $e) {
        die("Database connection failed: " . $e->getMessage());
    }
}

// Test database connection
function testConnection() {
    try {
        $pdo = getDBConnection();
        return true;
    } catch (Exception $e) {
        return false;
    }
}
?>
