<?php
include 'config.php';

// Verify gig ID is provided in URL
if (!isset($_GET['id'])) {
    header("Location: index.php");
    exit();
}

$gig_id = intval($_GET['id']);

// Fetch single gig details from database
try {
    $sql = "SELECT gigs.*, users.username, users.email FROM gigs 
            JOIN users ON gigs.user_id = users.user_id 
            WHERE gigs.gig_id = :gig_id";
    $stmt = $conn->prepare($sql);
    $stmt->bindParam(':gig_id', $gig_id);
    $stmt->execute();
    $gig = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$gig) {
        die("Service not found.");
    }
} catch (PDOException $e) {
    error_log("Gig details fetch error: " . $e->getMessage());
    die("Error loading service details.");
}

// Generate CSRF token for order form
$csrf_token = generateCSRFToken();

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
            <div class="d-flex flex-wrap gap-2 mb-3">
                <span class="badge bg-bright-gray text-jacarta border"><?php echo htmlspecialchars($gig['category'] ?: 'Uncategorized'); ?></span>
                <span class="badge bg-light text-dark border"><?php echo htmlspecialchars($gig['subcategory'] ?: 'General'); ?></span>
                <span class="badge bg-light text-dark border"><?php echo htmlspecialchars($gig['service_type'] ?: 'Custom service'); ?></span>
                <span class="badge bg-light text-dark border"><?php echo htmlspecialchars($gig['package_type'] ?: 'Standard'); ?></span>
            </div>
            <hr>
            <h5 class="fw-bold mb-3 text-jacarta">Service Description</h5>
            <p class="text-secondary" style="line-height: 1.7; white-space: pre-wrap;"><?php echo htmlspecialchars($gig['description']); ?></p>

            <div class="row g-3 mt-3">
                <div class="col-md-4">
                    <div class="p-3 bg-bright-gray rounded-3 h-100">
                        <div class="small text-muted">Delivery Time</div>
                        <div class="fw-bold text-jacarta"><?php echo (int)($gig['delivery_time_days'] ?? 3); ?> day(s)</div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="p-3 bg-bright-gray rounded-3 h-100">
                        <div class="small text-muted">Revisions</div>
                        <div class="fw-bold text-jacarta"><?php echo (int)($gig['revisions'] ?? 2); ?></div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="p-3 bg-bright-gray rounded-3 h-100">
                        <div class="small text-muted">Source Files</div>
                        <div class="fw-bold text-jacarta"><?php echo !empty($gig['includes_source_files']) ? 'Included' : 'Not included'; ?></div>
                    </div>
                </div>
            </div>

            <?php if (!empty($gig['gig_tags'])): ?>
                <div class="mt-3">
                    <h6 class="fw-bold text-jacarta">Tags</h6>
                    <p class="text-muted mb-0"><?php echo htmlspecialchars($gig['gig_tags']); ?></p>
                </div>
            <?php endif; ?>

            <?php if (!empty($gig['highlights'])): ?>
                <div class="mt-3">
                    <h6 class="fw-bold text-jacarta">Highlights</h6>
                    <p class="text-muted mb-0" style="white-space: pre-wrap;"><?php echo htmlspecialchars($gig['highlights']); ?></p>
                </div>
            <?php endif; ?>

            <?php if (!empty($gig['requirements'])): ?>
                <div class="mt-3">
                    <h6 class="fw-bold text-jacarta">Buyer Requirements</h6>
                    <p class="text-muted mb-0" style="white-space: pre-wrap;"><?php echo htmlspecialchars($gig['requirements']); ?></p>
                </div>
            <?php endif; ?>

            <?php if (!empty($gig['faq'])): ?>
                <div class="mt-3">
                    <h6 class="fw-bold text-jacarta">FAQ / Notes</h6>
                    <p class="text-muted mb-0" style="white-space: pre-wrap;"><?php echo htmlspecialchars($gig['faq']); ?></p>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Right Sidebar: Pricing & Payment Checkout Card -->
    <div class="col-md-4">
        <div class="card p-4 shadow-sm border-0 sticky-top" style="border-radius: 12px; top: 20px;">
            <h5 class="fw-bold text-muted mb-2">Purchase Package</h5>
            <div class="d-flex justify-content-between align-items-center mb-4">
                <span class="fs-4 text-secondary">Total Price:</span>
                <span class="fw-bold fs-2 text-dark-blue-gray">$<?php echo number_format($gig['price'], 2); ?></span>
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