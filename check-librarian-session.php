<?php
session_start();
header('Content-Type: application/json');

$isLoggedIn = isset($_SESSION['librarian_logged_in']) && $_SESSION['librarian_logged_in'] === true;

echo json_encode([
    'logged_in' => $isLoggedIn,
    'username' => $isLoggedIn ? ($_SESSION['librarian_username'] ?? '') : '',
    'name' => $isLoggedIn ? ($_SESSION['librarian_name'] ?? '') : ''
]);
?>
