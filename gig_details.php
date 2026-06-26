<?php
include 'config.php';

// Check karenge ke URL mein Gig ID maujood hai ya nahi
if (!isset($_GET['id'])) {
    header("Location: index.php");
    exit();
}

$gig_id = intval($_GET['id']);
$message = "";

// ==== LOGIC 1: FETCH SINGLE GIG DETAILS ====
try {
    $sql = "SELECT gigs.*, users.username, users.email FROM gigs 
            JOIN users ON gigs.user_id = users.user_id 
            WHERE gigs.gig_id = :gig_id";
    $stmt = $conn->prepare($sql);
    $stmt->bindParam(':gig_id', $gig_id);
    $stmt->execute();
    $gig = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$gig) {
        die("Service nahi mili!");
    }
} catch (PDOException $e) {
    die("Error: " . $e->getMessage());
}

// ==== LOGIC 2: MOCK PAYMENT & ORDER INTEGRATION ====
if (isset($_POST['pay_now_btn'])) {
    // Agar login nahi hai toh checkout block karo
    if (!isset($_SESSION['user_id'])) {
        header("Location: login.php");
        exit();
    }

    $buyer_id = $_SESSION['user_id'];
    $amount = $gig['price'];

    // Secure checking: Seller khud apna gig buy na kare
    if ($buyer_id == $gig['user_id']) {
        $message = "<div class='alert alert-danger'>Aap apni hi service purchase nahi kar sakte!</div>";
    } else {
        try {
            // SQL Query: Place order with 'Paid' status simulating a successful payment gateway response
            $sql = "INSERT INTO orders (gig_id, buyer_id, amount, payment_status) VALUES (:gig_id, :buyer_id, :amount, 'Paid')";
            $stmt = $conn->prepare($sql);
            $stmt->bindParam(':gig_id', $gig_id);
            $stmt->bindParam(':buyer_id', $buyer_id);
            $stmt->bindParam(':amount', $amount);
            $stmt->execute();

            // Order complete hone par success screen par user dashboard update pass karo
            header("Location: order_success.php");
            exit();
        } catch (PDOException $e) {
            $message = "<div class='alert alert-danger'>Payment Processing Failed: " . $e->getMessage() . "</div>";
        }
    }
}

include 'header.php';
?>

<div class="row justify-content-center mt-3">
    <div class="col-md-12">
        <?php echo $message; ?>
    </div>
    
    <!-- Left Details Pane -->
    <div class="col-md-8 mb-4">
        <div class="card p-4 shadow-sm border-0 mb-4" style="border-radius: 12px;">
            <h2 class="fw-bold text-dark mb-2"><?php echo htmlspecialchars($gig['title']); ?></h2>
            <div class="d-flex align-items-center mb-3 text-muted">
                <span class="me-3">👤 Seller: <strong><?php echo htmlspecialchars($gig['username']); ?></strong></span>
                <span>✉️ Contact: <?php echo htmlspecialchars($gig['email']); ?></span>
            </div>
            <hr>
            <h5 class="fw-bold mb-3" style="color: #2d3748;">Service Description</h5>
            <p class="text-secondary" style="line-height: 1.7; white-space: pre-wrap;"><?php echo htmlspecialchars($gig['description']); ?></p>
        </div>
    </div>

    <!-- Right Sidebar: Pricing & Payment Checkout Card -->
    <div class="col-md-4">
        <div class="card p-4 shadow-sm border-0 sticky-top" style="border-radius: 12px; top: 20px;">
            <h5 class="fw-bold text-muted mb-2">Purchase Package</h5>
            <div class="d-flex justify-content-between align-items-center mb-4">
                <span class="fs-4 text-secondary">Total Price:</span>
                <span class="fw-bold fs-2 text-success" style="color: #10b981 !important;">$<?php echo number_format($gig['price'], 2); ?></span>
            </div>

            <form action="gig_details.php?id=<?php echo $gig['gig_id']; ?>" method="POST">
                <?php if (isset($_SESSION['user_id'])): ?>
                    <?php if ($_SESSION['role'] === 'buyer'): ?>
                        <!-- Credit Card Form Fields Mock Visual Layer to impress Sir -->
                        <div class="mb-3">
                            <label class="form-label font-monospace small text-uppercase text-muted">Dummy Card Number</label>
                            <input type="text" class="form-control" placeholder="4242 •••• •••• 4242" disabled value="4242 4242 4242 4242">
                        </div>
                        <button type="submit" name="pay_now_btn" class="btn btn-success-custom btn-lg w-100 fw-bold py-2 shadow-sm fs-5">💳 Order & Pay Now</button>
                    <?php else: ?>
                        <div class="alert alert-warning text-center small mb-0">Sellers cannot buy services. Log in as a Buyer to purchase.</div>
                    <?php endif; ?>
                <?php else: ?>
                    <a href="login.php" class="btn btn-dark btn-lg w-100 fw-bold py-2 shadow-sm fs-5">Log in to Order</a>
                <?php endif; ?>
            </form>
            <div class="text-center mt-3 text-muted small">
                🔒 Safe and Secure Instant Transaction System
            </div>
        </div>
    </div>
</div>

<?php include 'footer.php'; ?>