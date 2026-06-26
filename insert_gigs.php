<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

include 'config.php';

try {
    // Check if any users exist in the database
    $user_check = $conn->query("SELECT user_id FROM users LIMIT 1");
    $user = $user_check->fetch(PDO::FETCH_ASSOC);

    // If no users exist, create a sample seller account
    if (!$user) {
        $hashed_password = password_hash("test1234", PASSWORD_BCRYPT);
        $insert_user = $conn->prepare("INSERT INTO users (username, email, password, role) VALUES ('creative_dev', 'seller@velvetdesk.com', :pass, 'seller')");
        $insert_user->bindParam(':pass', $hashed_password);
        $insert_user->execute();
        $seller_id = $conn->lastInsertId();
    } else {
        $seller_id = $user['user_id'];
    }

    // Sample gigs with professional descriptions and placeholder images
    $gigs = [
        [
            'title' => 'Complete Brand Identity & UI Design System',
            'description' => 'I will create a professional brand identity from scratch. This includes premium vector logo designs, custom typography, business templates, letterheads, and a comprehensive Figma design system with modern aesthetics.',
            'price' => 120.00,
            'portfolio' => 'https://behance.net',
            'image' => 'https://images.unsplash.com/photo-1626785774573-4b799315345d?w=600&auto=format&fit=crop&q=60'
        ],
        [
            'title' => 'Custom PHP & SQLite Dynamic Web Application',
            'description' => 'Need custom backend development? I will build an optimized application using PHP and SQLite with clean architecture. Includes form handling, database design, server configuration, and comprehensive error management.',
            'price' => 250.00,
            'portfolio' => 'https://github.com',
            'image' => 'https://images.unsplash.com/photo-1555066931-4365d14bab8c?w=600&auto=format&fit=crop&q=60'
        ],
        [
            'title' => 'Full-Stack Portfolio Website for Creative Professionals',
            'description' => 'Get a custom portfolio website to showcase your work professionally. Built with responsive HTML, CSS variables, modern grids, and interactive JavaScript components for optimal user engagement.',
            'price' => 85.00,
            'portfolio' => 'https://behance.net',
            'image' => 'https://images.unsplash.com/photo-1507238691740-187a5b1d37b8?w=600&auto=format&fit=crop&q=60'
        ]
    ];

    // Insert sample gigs into database
    $insert_gig = $conn->prepare("INSERT INTO gigs (user_id, title, description, price, portfolio_link, image_path) VALUES (:uid, :title, :desc, :price, :portfolio, :img)");

    foreach ($gigs as $g) {
        $insert_gig->bindParam(':uid', $seller_id);
        $insert_gig->bindParam(':title', $g['title']);
        $insert_gig->bindParam(':desc', $g['description']);
        $insert_gig->bindParam(':price', $g['price']);
        $insert_gig->bindParam(':portfolio', $g['portfolio']);
        $insert_gig->bindParam(':img', $g['image']);
        $insert_gig->execute();
    }

    echo "<h3 style='color: #A78BC5; margin-top: 20px;'>✨ Sample Data Loaded Successfully:</h3>";
    echo "<p style='color: green; font-weight: bold;'>Sample gigs have been added to the database with professional images. Visit the homepage to see them displayed.</p>";

} catch (PDOException $e) {
    error_log("Data seeding error: " . $e->getMessage());
    die("<p style='color: red; font-weight: bold;'>Failed to seed sample data. Please check server logs.</p>");
}
?>