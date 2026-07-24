<?php
include 'config.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$current_user_id = $_SESSION['user_id'];
$user_role = $_SESSION['role'];

// Handle order placement by buyer
if (isset($_GET['action']) && $_GET['action'] === 'place_order' && isset($_GET['gig_id'])) {
    $gig_id = intval($_GET['gig_id']);
    
    try {
        // Retrieve gig seller information
        $stmt = $conn->prepare("SELECT user_id FROM gigs WHERE gig_id = :gig_id");
        $stmt->bindParam(':gig_id', $gig_id);
        $stmt->execute();
        $gig = $stmt->fetch(PDO::FETCH_ASSOC);
        
        // Verify gig exists and buyer is not the seller
        if ($gig && $gig['user_id'] != $current_user_id) {
            $seller_id = $gig['user_id'];

            $existing_order = $conn->prepare("SELECT order_id FROM orders WHERE gig_id = :gig_id AND buyer_id = :buyer_id LIMIT 1");
            $existing_order->bindParam(':gig_id', $gig_id);
            $existing_order->bindParam(':buyer_id', $current_user_id);
            $existing_order->execute();
            $order = $existing_order->fetch(PDO::FETCH_ASSOC);

            if ($order) {
                header("Location: chat.php?order_id=" . $order['order_id']);
                exit();
            }
            
            // Create order record
            $insert = $conn->prepare("INSERT INTO orders (gig_id, buyer_id, seller_id, order_status, conversation_started_at) VALUES (:gig_id, :buyer_id, :seller_id, 'Conversation Started', CURRENT_TIMESTAMP)");
            $insert->bindParam(':gig_id', $gig_id);
            $insert->bindParam(':buyer_id', $current_user_id);
            $insert->bindParam(':seller_id', $seller_id);
            $insert->execute();

            $new_order_id = $conn->lastInsertId();
            
            // Redirect to the conversation first so the seller can approve after discussion
            header("Location: chat.php?order_id=" . $new_order_id);
            exit();
        }
    } catch (PDOException $e) {
        error_log("Order placement error: " . $e->getMessage());
        die("An error occurred while placing the order.");
    }
}

// Handle seller file delivery upload with security validation
if (isset($_POST['upload_delivery_btn'])) {
    if (!isset($_POST['csrf_token']) || !validateCSRFToken($_POST['csrf_token'])) {
        header("Location: dashboard.php?error=Security validation failed.");
        exit();
    }

    $order_id = intval($_POST['order_id']);
    $target_dir = "assets/deliveries/";
    
    // Create directory if it doesn't exist with secure permissions
    if (!is_dir($target_dir)) { 
        mkdir($target_dir, 0755, true); 
    }
    
    // Validate file upload
    if (isset($_FILES['delivery_file']) && $_FILES['delivery_file']['error'] == 0) {
        // Allowed file types for delivery
        $allowed_types = ['application/pdf', 'application/zip', 'application/x-rar-compressed', 
                         'image/jpeg', 'image/png', 'image/gif', 'text/plain', 
                         'application/msword', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document'];
        
        $file_mime = mime_content_type($_FILES['delivery_file']['tmp_name']);
        $file_size = $_FILES['delivery_file']['size'];
        $max_file_size = 50 * 1024 * 1024; // 50MB limit
        
        // Validate file type and size
        if (!in_array($file_mime, $allowed_types)) {
            $_SESSION['error'] = "Invalid file type. Allowed: PDF, ZIP, Images, Word, and Text files.";
            header("Location: dashboard.php?error=Invalid file type.");
            exit();
        }
        
        if ($file_size > $max_file_size) {
            $_SESSION['error'] = "File size exceeds 50MB limit.";
            header("Location: dashboard.php?error=File too large.");
            exit();
        }
        
        // Generate safe file name
        $file_extension = pathinfo($_FILES["delivery_file"]["name"], PATHINFO_EXTENSION);
        $file_name = time() . "_" . md5(uniqid()) . "." . preg_replace('/[^a-zA-Z0-9]/', '', $file_extension);
        $target_file = $target_dir . $file_name;
        
        // Move uploaded file
        if (move_uploaded_file($_FILES["delivery_file"]["tmp_name"], $target_file)) {
            // Set secure file permissions
            chmod($target_file, 0644);
            
            // Update order status with file path
            $update = $conn->prepare("UPDATE orders SET order_status = 'Delivered', delivery_file_path = :file_path WHERE order_id = :order_id AND seller_id = :seller_id AND order_status IN ('Confirmed', 'In Progress', 'Delivered')");
            $update->bindParam(':file_path', $target_file);
            $update->bindParam(':order_id', $order_id);
            $update->bindParam(':seller_id', $current_user_id);
            $update->execute();
            
            header("Location: dashboard.php?success=Delivery file uploaded successfully.");
            exit();
        } else {
            $_SESSION['error'] = "Failed to upload file.";
            header("Location: dashboard.php?error=Upload failed.");
            exit();
        }
    } else {
        $_SESSION['error'] = "No file provided.";
        header("Location: dashboard.php?error=No file.");
        exit();
    }
}

// Handle seller confirmation after the chat discussion is complete
if (isset($_POST['confirm_order_btn'])) {
    if (!isset($_POST['csrf_token']) || !validateCSRFToken($_POST['csrf_token'])) {
        header("Location: index.php?error=Security validation failed.");
        exit();
    }

    $order_id = intval($_POST['order_id']);

    try {
        $update = $conn->prepare("UPDATE orders SET order_status = 'In Progress', order_placed_at = COALESCE(order_placed_at, CURRENT_TIMESTAMP), confirmed_at = CURRENT_TIMESTAMP WHERE order_id = :order_id AND seller_id = :seller_id");
        $update->bindParam(':order_id', $order_id);
        $update->bindParam(':seller_id', $current_user_id);
        $update->execute();

        header("Location: chat.php?order_id=" . $order_id . "&success=Order confirmed successfully.");
        exit();
    } catch (PDOException $e) {
        error_log("Order confirmation error: " . $e->getMessage());
        header("Location: chat.php?order_id=" . $order_id . "&error=Failed to confirm order.");
        exit();
    }
}

// Handle seller closing the order after uploading the delivery assets
if (isset($_POST['close_order_btn'])) {
    if (!isset($_POST['csrf_token']) || !validateCSRFToken($_POST['csrf_token'])) {
        header("Location: dashboard.php?error=Security validation failed.");
        exit();
    }

    $order_id = intval($_POST['order_id']);

    try {
        $update = $conn->prepare("UPDATE orders SET order_status = 'Completed' WHERE order_id = :order_id AND seller_id = :seller_id AND order_status = 'Delivered'");
        $update->bindParam(':order_id', $order_id);
        $update->bindParam(':seller_id', $current_user_id);
        $update->execute();

        header("Location: chat.php?order_id=" . $order_id . "&success=Order closed successfully.");
        exit();
    } catch (PDOException $e) {
        error_log("Order close error: " . $e->getMessage());
        header("Location: chat.php?order_id=" . $order_id . "&error=Failed to close order.");
        exit();
    }
}

// Handle buyer reviews for completed orders
if (isset($_POST['submit_review_btn'])) {
    if (!isset($_POST['csrf_token']) || !validateCSRFToken($_POST['csrf_token'])) {
        header("Location: buyer_dashboard.php?error=Security validation failed.");
        exit();
    }

    $order_id = intval($_POST['order_id']);
    $rating = intval($_POST['rating']);
    $review_text = trim($_POST['review_text'] ?? '');

    if ($rating < 1 || $rating > 5 || $review_text === '') {
        header("Location: buyer_dashboard.php?error=Please provide a rating and review.");
        exit();
    }

    try {
        $order_stmt = $conn->prepare("SELECT seller_id FROM orders WHERE order_id = :order_id AND buyer_id = :buyer_id AND order_status = 'Completed'");
        $order_stmt->bindParam(':order_id', $order_id);
        $order_stmt->bindParam(':buyer_id', $current_user_id);
        $order_stmt->execute();
        $order = $order_stmt->fetch(PDO::FETCH_ASSOC);

        if (!$order) {
            header("Location: buyer_dashboard.php?error=You can only review completed orders.");
            exit();
        }

        $review = $conn->prepare("INSERT INTO reviews (order_id, reviewer_id, seller_id, rating, review_text) VALUES (:order_id, :reviewer_id, :seller_id, :rating, :review_text) ON CONFLICT(order_id) DO UPDATE SET rating = excluded.rating, review_text = excluded.review_text, created_at = CURRENT_TIMESTAMP");
        $review->bindParam(':order_id', $order_id);
        $review->bindParam(':reviewer_id', $current_user_id);
        $review->bindParam(':seller_id', $order['seller_id']);
        $review->bindParam(':rating', $rating);
        $review->bindParam(':review_text', $review_text);
        $review->execute();

        header("Location: buyer_dashboard.php?success=Review submitted successfully.");
        exit();
    } catch (PDOException $e) {
        error_log("Review submission error: " . $e->getMessage());
        header("Location: buyer_dashboard.php?error=Unable to save your review.");
        exit();
    }
}

// Redirect to homepage if no valid action
header("Location: index.php");
exit();
?>