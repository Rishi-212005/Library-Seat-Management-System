<?php
session_start();
require_once 'config/database.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit();
}

$input = json_decode(file_get_contents('php://input'), true);
$username = trim($input['username'] ?? '');
$password = trim($input['password'] ?? '');

// Simple hardcoded credentials
$validUsername = 'USERNAME@2025';
$validPassword = 'PASSWORD@2025';

if ($username === $validUsername && $password === $validPassword) {
    // Set session variables
    $_SESSION['librarian_logged_in'] = true;
    $_SESSION['librarian_username'] = $username;
    $_SESSION['librarian_name'] = 'Librarian';
    $_SESSION['login_time'] = time();
    
    echo json_encode([
        'success' => true,
        'message' => 'Login successful',
        'redirect' => 'librarian-dashboard.html'
    ]);
} else {
    echo json_encode([
        'success' => false,
        'message' => 'Invalid username or password'
    ]);
}
?>
