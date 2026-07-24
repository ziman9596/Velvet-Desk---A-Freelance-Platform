<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

include 'config.php';

try {
    // 1. DROP EXISTING TABLES (Reverse relational order to avoid constraint conflicts)
    $conn->exec("DROP TABLE IF EXISTS reviews");
    $conn->exec("DROP TABLE IF EXISTS order_messages");
    $conn->exec("DROP TABLE IF EXISTS orders");
    $conn->exec("DROP TABLE IF EXISTS gigs");
    $conn->exec("DROP TABLE IF EXISTS users");
    echo "<p>🗑️ Existing database tables removed successfully.</p>";

    // 2. CREATE USERS TABLE
    $users_table = "CREATE TABLE users (
        user_id INTEGER PRIMARY KEY AUTOINCREMENT,
        username TEXT NOT NULL,
        email TEXT NOT NULL UNIQUE,
        password TEXT NOT NULL,
        role TEXT NOT NULL, 
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP
    )";
    $conn->exec($users_table);
    echo "<p>✅ Users table created successfully.</p>";

    // 3. CREATE GIGS TABLE
    $gigs_table = "CREATE TABLE gigs (
        gig_id INTEGER PRIMARY KEY AUTOINCREMENT,
        user_id INTEGER,
        title TEXT NOT NULL,
        description TEXT NOT NULL,
        price REAL NOT NULL,
        image_path TEXT DEFAULT 'assets/uploads/default-gig.svg',
        portfolio_link TEXT DEFAULT NULL,
        category TEXT DEFAULT '',
        subcategory TEXT DEFAULT '',
        service_type TEXT DEFAULT '',
        package_type TEXT DEFAULT 'Standard',
        delivery_time_days INTEGER DEFAULT 3,
        revisions INTEGER DEFAULT 2,
        gig_tags TEXT DEFAULT '',
        highlights TEXT DEFAULT '',
        requirements TEXT DEFAULT '',
        faq TEXT DEFAULT '',
        includes_source_files INTEGER DEFAULT 0,
        video_link TEXT DEFAULT '',
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY(user_id) REFERENCES users(user_id)
    )";
    $conn->exec($gigs_table);
    echo "<p>✅ Gigs table created successfully.</p>";

    // 4. CREATE ORDERS TABLE
    $orders_table = "CREATE TABLE orders (
        order_id INTEGER PRIMARY KEY AUTOINCREMENT,
        gig_id INTEGER,
        buyer_id INTEGER,
        seller_id INTEGER,
        order_status TEXT DEFAULT 'Conversation Started',
        delivery_file_path TEXT DEFAULT NULL,
        submission_date DATETIME DEFAULT CURRENT_TIMESTAMP,
        conversation_started_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        order_placed_at DATETIME DEFAULT NULL,
        confirmed_at DATETIME DEFAULT NULL,
        FOREIGN KEY(gig_id) REFERENCES gigs(gig_id),
        FOREIGN KEY(buyer_id) REFERENCES users(user_id),
        FOREIGN KEY(seller_id) REFERENCES users(user_id)
    )";
    $conn->exec($orders_table);
    echo "<p>✅ Orders table created successfully.</p>";

    // 5. CREATE ORDER MESSAGES TABLE
    $messages_table = "CREATE TABLE order_messages (
        message_id INTEGER PRIMARY KEY AUTOINCREMENT,
        order_id INTEGER NOT NULL,
        sender_id INTEGER NOT NULL,
        message_text TEXT NOT NULL,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY(order_id) REFERENCES orders(order_id) ON DELETE CASCADE,
        FOREIGN KEY(sender_id) REFERENCES users(user_id) ON DELETE CASCADE
    )";
    $conn->exec($messages_table);
    echo "<p>✅ Order Messages table created successfully.</p>";

    // 6. CREATE REVIEWS TABLE
    $reviews_table = "CREATE TABLE reviews (
        review_id INTEGER PRIMARY KEY AUTOINCREMENT,
        order_id INTEGER NOT NULL UNIQUE,
        reviewer_id INTEGER NOT NULL,
        seller_id INTEGER NOT NULL,
        rating INTEGER NOT NULL CHECK(rating BETWEEN 1 AND 5),
        review_text TEXT NOT NULL,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY(order_id) REFERENCES orders(order_id) ON DELETE CASCADE,
        FOREIGN KEY(reviewer_id) REFERENCES users(user_id) ON DELETE CASCADE,
        FOREIGN KEY(seller_id) REFERENCES users(user_id) ON DELETE CASCADE
    )";
    $conn->exec($reviews_table);
    echo "<p>✅ Reviews table created successfully.</p>";

    echo "<h3 style='color: #3D2F5C; margin-top: 20px;'>✨ VelvetDesk Database Status:</h3>";
    echo "<p style='color: #6F5E93; font-weight: bold;'>Database tables have been completely reset and are ready for your custom inserts!</p>";

} catch (PDOException $e) {
    error_log("Database setup error: " . $e->getMessage());
    die("<p style='color: #31212B; font-weight: bold;'>Database setup failed. Please check server logs.</p>");
}
?>