<?php
// Session check aur initialize krne ke liye block
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Database setup configurations (SQLite file resource pathway)
$db_file = "velvetdesk_marketplace.db";

try {
    // Connection string initialize krna aur attributes inject krna
    $conn = new PDO("sqlite:" . $db_file);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    // Error tracking exception execution routing
    die("Critical Database Connection Failure: " . $e->getMessage());
}
?>