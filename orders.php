<?php
include 'config.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$current_user_id = $_SESSION['user_id'];
$user_role = $_SESSION['role'];

// Action Block: Order Placement Pipeline
if (isset($_GET['action']) && $_GET['action'] === 'place_order' && isset($_GET['gig_id'])) {
    $gig_id = intval($_GET['gig_id']);
    
    try {
        // Target metadata extraction
        $stmt = $conn->prepare("SELECT user_id FROM gigs WHERE gig_id = :gig_id");
        $stmt->bindParam(':gig_id', $gig_id);
        $stmt->execute();
        $gig = $stmt->fetch(PDO::FETCH_ASSOC);
        
        // Logical verification: Gig exists and buyer is not the seller
        if ($gig && $gig['user_id'] != $current_user_id) {
            $seller_id = $gig['user_id'];
            
            $insert = $conn->prepare("INSERT INTO orders (gig_id, buyer_id, seller_id) VALUES (:gig_id, :buyer_id, :seller_id)");
            $insert->bindParam(':gig_id', $gig_id);
            $insert->bindParam(':buyer_id', $current_user_id);
            $insert->bindParam(':seller_id', $seller_id);
            $insert->execute();
            
            // Redirect straight to buyer panel without screen freezing
            header("Location: buyer_dashboard.php?success=Order initialized successfully.");
            exit();
        }
    } catch (PDOException $e) {
        die("Pipeline Route Error: " . htmlspecialchars($e->getMessage()));
    }
}

// Action Block: Seller File Delivery Upload
if (isset($_POST['upload_delivery_btn'])) {
    $order_id = intval($_POST['order_id']);
    $target_dir = "assets/deliveries/";
    if (!is_dir($target_dir)) { mkdir($target_dir, 0777, true); }
    
    if (isset($_FILES['delivery_file']) && $_FILES['delivery_file']['error'] == 0) {
        $file_name = time() . "_" . basename($_FILES["delivery_file"]["name"]);
        $target_file = $target_dir . $file_name;
        
        if (move_uploaded_file($_FILES["delivery_file"]["tmp_name"], $target_file)) {
            $update = $conn->prepare("UPDATE orders SET order_status = 'Delivered', delivery_file_path = :file_path WHERE order_id = :order_id AND seller_id = :seller_id");
            $update->bindParam(':file_path', $target_file);
            $update->bindParam(':order_id', $order_id);
            $update->bindParam(':seller_id', $current_user_id);
            $update->execute();
            
            header("Location: dashboard.php?success=Deliverable payload transmitted.");
            exit();
        }
    }
}

// Action Block: Buyer Final Approval
if (isset($_GET['action']) && $_GET['action'] === 'approve' && isset($_GET['order_id'])) {
    $order_id = intval($_GET['order_id']);
    
    $update = $conn->prepare("UPDATE orders SET order_status = 'Completed' WHERE order_id = :order_id AND buyer_id = :buyer_id");
    $update->bindParam(':order_id', $order_id);
    $update->bindParam(':buyer_id', $current_user_id);
    $update->execute();
    
    header("Location: buyer_dashboard.php?success=Order archive finalized securely.");
    exit();
}

// Fallback safety route
header("Location: index.php");
exit();
?>