<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

include 'config.php';

try {
    // Purani conflict peda krne wali tables ko clean drop krna
    $conn->exec("DROP TABLE IF EXISTS orders");
    echo "<p>🗑️ Old orders conflict cache dropped successfully.</p>";

    // 1. CREATE USERS TABLE
    $users_table = "CREATE TABLE IF NOT EXISTS users (
        user_id INTEGER PRIMARY KEY AUTOINCREMENT,
        username TEXT NOT NULL,
        email TEXT NOT NULL UNIQUE,
        password TEXT NOT NULL,
        role TEXT NOT NULL, 
        platform_balance REAL DEFAULT 0.00,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP
    )";
    $conn->exec($users_table);
    echo "<p>✅ Users table structural matrix initialized.</p>";

    // 2. CREATE GIGS TABLE
    $gigs_table = "CREATE TABLE IF NOT EXISTS gigs (
        gig_id INTEGER PRIMARY KEY AUTOINCREMENT,
        user_id INTEGER,
        title TEXT NOT NULL,
        description TEXT NOT NULL,
        price REAL NOT NULL,
        image_path TEXT DEFAULT 'assets/uploads/default-gig.png',
        portfolio_link TEXT DEFAULT NULL,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY(user_id) REFERENCES users(user_id)
    )";
    $conn->exec($gigs_table);
    echo "<p>✅ Gigs service listings table structured.</p>";

    // 3. CREATE ORDERS TABLE (Freshly mapped with seller_id)
    $orders_table = "CREATE TABLE orders (
        order_id INTEGER PRIMARY KEY AUTOINCREMENT,
        gig_id INTEGER,
        buyer_id INTEGER,
        seller_id INTEGER,
        order_status TEXT DEFAULT 'In Progress', 
        delivery_file_path TEXT DEFAULT NULL,
        submission_date DATETIME DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY(gig_id) REFERENCES gigs(gig_id),
        FOREIGN KEY(buyer_id) REFERENCES users(user_id),
        FOREIGN KEY(seller_id) REFERENCES users(user_id)
    )";
    $conn->exec($orders_table);
    
    echo "<h3 style='color: #A78BC5; margin-top: 20px;'>✨ VelvetDesk Database Architecture Status:</h3>";
    echo "<p style='color: green; font-weight: bold;'>Zabardast! All tables recreated with 'seller_id' mapping path.</p>";

} catch (PDOException $e) {
    die("<p style='color: red; font-weight: bold;'>Database Schema Execution Failed: " . htmlspecialchars($e->getMessage()) . "</p>");
}
?>