<?php
// Database configuration
$host = 'sql200.infinityfree.com';
$dbname = 'if0_39418937_library_management';
$username = 'if0_39418937'; // Change this to your database username
$password = 'library2025';     // Change this to your database password

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
} catch(PDOException $e) {
    die("Connection failed: " . $e->getMessage());
}
?>
