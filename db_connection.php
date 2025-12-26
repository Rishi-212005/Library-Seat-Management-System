<?php
$host = "sql200.infinityfree.com";
$user = "if0_39418937";
$pass = "library2025";
$db = "if0_39418937_library_management";

$conn = new mysqli($host, $user, $pass, $db);

if ($conn->connect_error) {
    die(json_encode(["success" => false, "error" => "Database connection failed: " . $conn->connect_error]));
}
?>
