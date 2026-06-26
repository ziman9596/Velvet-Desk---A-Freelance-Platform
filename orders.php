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
            
            // Create order record
            $insert = $conn->prepare("INSERT INTO orders (gig_id, buyer_id, seller_id) VALUES (:gig_id, :buyer_id, :seller_id)");
            $insert->bindParam(':gig_id', $gig_id);
            $insert->bindParam(':buyer_id', $current_user_id);
            $insert->bindParam(':seller_id', $seller_id);
            $insert->execute();
            
            // Redirect to buyer dashboard
            header("Location: buyer_dashboard.php?success=Order placed successfully.");
            exit();
        }
    } catch (PDOException $e) {
        error_log("Order placement error: " . $e->getMessage());
        die("An error occurred while placing the order.");
    }
}

// Handle seller file delivery upload with security validation
if (isset($_POST['upload_delivery_btn'])) {
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
            $update = $conn->prepare("UPDATE orders SET order_status = 'Delivered', delivery_file_path = :file_path WHERE order_id = :order_id AND seller_id = :seller_id");
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

// Handle buyer approval to complete order
if (isset($_GET['action']) && $_GET['action'] === 'approve' && isset($_GET['order_id'])) {
    $order_id = intval($_GET['order_id']);
    
    try {
        // Update order status to completed after buyer approval
        $update = $conn->prepare("UPDATE orders SET order_status = 'Completed' WHERE order_id = :order_id AND buyer_id = :buyer_id");
        $update->bindParam(':order_id', $order_id);
        $update->bindParam(':buyer_id', $current_user_id);
        $update->execute();
        
        header("Location: buyer_dashboard.php?success=Order completed successfully.");
        exit();
    } catch (PDOException $e) {
        error_log("Order approval error: " . $e->getMessage());
        header("Location: buyer_dashboard.php?error=Failed to complete order.");
        exit();
    }
}

// Redirect to homepage if no valid action
header("Location: index.php");
exit();
?>