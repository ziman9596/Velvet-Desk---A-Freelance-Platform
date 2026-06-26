<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

include 'config.php';

try {
    // Check if system profile anchor exists
    $user_check = $conn->query("SELECT user_id FROM users LIMIT 1");
    $user = $user_check->fetch(PDO::FETCH_ASSOC);

    if (!$user) {
        $hashed_password = password_hash("test1234", PASSWORD_BCRYPT);
        $insert_user = $conn->prepare("INSERT INTO users (username, email, password, role) VALUES ('creative_dev', 'seller@velvetdesk.com', :pass, 'seller')");
        $insert_user->bindParam(':pass', $hashed_password);
        $insert_user->execute();
        $seller_id = $conn->lastInsertId();
    } else {
        $seller_id = $user['user_id'];
    }

    // Beautiful stock placeholder illustration assets mapped for design and code
    $gigs = [
        [
            'title' => 'Complete Brand Identity & UI Design System',
            'description' => 'I will construct a high-end corporate brand identity from scratch. This includes premium vector logo designs, custom typography setups, business assets templates, letterheads, and a responsive Figma layout blueprint mapped to soft-palette aesthetics.',
            'price' => 120.00,
            'portfolio' => 'https://behance.net',
            'image' => 'https://images.unsplash.com/photo-1626785774573-4b799315345d?w=600&auto=format&fit=crop&q=60' // Beautiful vector brand identity abstract mockup
        ],
        [
            'title' => 'Custom PHP & SQLite Dynamic Web Application',
            'description' => 'Need an efficient backend infrastructure? I will develop an optimized clean architecture using vanilla PHP and relational SQLite configurations. Includes form management engines, relational database mapping, tracking pipelines, and comprehensive error-handling layers.',
            'price' => 250.00,
            'portfolio' => 'https://github.com',
            'image' => 'https://images.unsplash.com/photo-1555066931-4365d14bab8c?w=600&auto=format&fit=crop&q=60' // High-end clean dark IDE code setup
        ],
        [
            'title' => 'Full-Stack Portfolio Website for Creative Designers',
            'description' => 'Get a gorgeous, fully customized digital showcase to pitch your creative services. Built using responsive semantic HTML, custom CSS variables, layout grids, and interactive JavaScript UI components to keep users engaged.',
            'price' => 85.00,
            'portfolio' => 'https://behance.net',
            'image' => 'https://images.unsplash.com/photo-1507238691740-187a5b1d37b8?w=600&auto=format&fit=crop&q=60' // Web UI/UX workspace layout preview
        ]
    ];

    // Execution block
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

    echo "<h3 style='color: #A78BC5; margin-top: 20px;'>✨ Seed Content with Images Loaded:</h3>";
    echo "<p style='color: green; font-weight: bold;'>Zabardast! Ab images ke links ke saath database inject ho gaya hai. Homepage open karke dekhein, look ek dum professional lagegi!</p>";

} catch (PDOException $e) {
    die("<p style='color: red; font-weight: bold;'>Data Seeding Failed: " . htmlspecialchars($e->getMessage()) . "</p>");
}
?>