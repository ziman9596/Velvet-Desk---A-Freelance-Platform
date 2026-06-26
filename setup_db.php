<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

include 'config.php';

try {
    // Drop tables in correct order to avoid foreign key conflicts
    $conn->exec("DROP TABLE IF EXISTS orders");
    echo "<p>✅ Orders table removed.</p>";

    // Create users table for authentication and profile management
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
    echo "<p>✅ Users table created successfully.</p>";

    // Create gigs table for service listings
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
    echo "<p>✅ Gigs table created successfully.</p>";

    // Create orders table to track transactions between buyers and sellers
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
    
    echo "<h3 style='color: #A78BC5; margin-top: 20px;'>✨ VelvetDesk Database Status:</h3>";
    echo "<p style='color: green; font-weight: bold;'>Database tables created successfully with proper relationships.</p>";

} catch (PDOException $e) {
    error_log("Database setup error: " . $e->getMessage());
    die("<p style='color: red; font-weight: bold;'>Database setup failed. Please check server logs.</p>");
}
?>