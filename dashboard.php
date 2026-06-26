<?php
include 'config.php';
include 'header.php';

// Check seller authorization
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'seller') {
    header("Location: login.php");
    exit();
}

$current_user_id = $_SESSION['user_id'];
$message = "";

// Handle gig publication request
if (isset($_POST['publish_gig_btn'])) {
    // Validate CSRF token
    if (!isset($_POST['csrf_token']) || !validateCSRFToken($_POST['csrf_token'])) {
        $message = "<div class='alert alert-danger'>Security validation failed. Please try again.</div>";
    } else {
        $title = trim($_POST['title']);
        $description = trim($_POST['description']);
        $price = floatval($_POST['price']);
        $portfolio_link = trim($_POST['portfolio_link']);
        
        // Validate inputs
        if (empty($title) || empty($description) || $price <= 0) {
            $message = "<div class='alert alert-danger'>Please fill in all required fields with valid values.</div>";
        } else {
            // Setup directory for uploads with secure permissions
            $target_dir = "assets/uploads/";
            if (!is_dir($target_dir)) { 
                mkdir($target_dir, 0755, true); 
            }
            
            $image_path = "assets/uploads/default-gig.png"; // Default fallback image
            
            // Handle image upload if provided
            if (isset($_FILES['gig_image']) && $_FILES['gig_image']['error'] == 0) {
                // Validate image file type
                $allowed_image_types = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
                $file_mime = mime_content_type($_FILES['gig_image']['tmp_name']);
                
                if (in_array($file_mime, $allowed_image_types)) {
                    $file_extension = pathinfo($_FILES["gig_image"]["name"], PATHINFO_EXTENSION);
                    $file_name = time() . "_" . md5(uniqid()) . "." . preg_replace('/[^a-zA-Z0-9]/', '', $file_extension);
                    $target_file = $target_dir . $file_name;
                    
                    if (move_uploaded_file($_FILES["gig_image"]["tmp_name"], $target_file)) {
                        chmod($target_file, 0644);
                        $image_path = $target_file;
                    }
                }
            }

            try {
                // Insert gig into database
                $insert_sql = "INSERT INTO gigs (user_id, title, description, price, image_path, portfolio_link) 
                               VALUES (:user_id, :title, :description, :price, :image_path, :portfolio_link)";
                $stmt = $conn->prepare($insert_sql);
                $stmt->bindParam(':user_id', $current_user_id);
                $stmt->bindParam(':title', $title);
                $stmt->bindParam(':description', $description);
                $stmt->bindParam(':price', $price);
                $stmt->bindParam(':image_path', $image_path);
                $stmt->bindParam(':portfolio_link', $portfolio_link);
                $stmt->execute();
                $message = "<div class='alert alert-success'>Service published successfully!</div>";
            } catch (PDOException $e) {
                error_log("Gig publication error: " . $e->getMessage());
                $message = "<div class='alert alert-danger'>Failed to publish service. Please try again.</div>";
            }
        }
    }
}

// Fetch seller's gigs and incoming orders
try {
    // Get all gigs published by this seller
    $gigs_sql = "SELECT * FROM gigs WHERE user_id = :user_id ORDER BY gig_id DESC";
    $gigs_stmt = $conn->prepare($gigs_sql);
    $gigs_stmt->bindParam(':user_id', $current_user_id);
    $gigs_stmt->execute();
    $my_gigs = $gigs_stmt->fetchAll(PDO::FETCH_ASSOC);

    // Get all incoming orders for this seller
    $orders_sql = "SELECT orders.*, gigs.title, users.username AS buyer_name FROM orders 
                   JOIN gigs ON orders.gig_id = gigs.gig_id 
                   JOIN users ON orders.buyer_id = users.user_id 
                   WHERE orders.seller_id = :user_id ORDER BY orders.order_id DESC";
    $orders_stmt = $conn->prepare($orders_sql);
    $orders_stmt->bindParam(':user_id', $current_user_id);
    $orders_stmt->execute();
    $received_orders = $orders_stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    error_log("Dashboard data fetch error: " . $e->getMessage());
    echo "<div class='alert alert-danger'>Error loading dashboard data. Please try again.</div>";
    $my_gigs = [];
    $received_orders = [];
}

// Generate CSRF token for forms
$csrf_token = generateCSRFToken();
?>

<div class="row mt-3">
    <div class="col-12"><?php echo $message; ?></div>
    
    <!-- LEFT PANEL CONSOLE: Setup New Gig Listings form infrastructure -->
    <div class="col-lg-4 mb-4">
        <div class="card p-4 border-0 shadow-sm rounded-4 bg-white">
            <h4 class="fw-bold mb-1" style="color: #A78BC5;">⚙️ Launch Service Parameters</h4>
            <p class="text-muted small">Input parameters to mount real-time structural offerings on the grid platform views.</p>
            <hr>
            <form action="dashboard.php" method="POST" enctype="multipart/form-data">
                <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
                <div class="mb-3">
                    <label class="form-label small fw-semibold text-secondary">Service Campaign Title</label>
                    <input type="text" name="title" class="form-control" placeholder="e.g., UI Web Branding Development" required>
                </div>
                <div class="mb-3">
                    <label class="form-label small fw-semibold text-secondary">Operational Scope Narrative (Description)</label>
                    <textarea name="description" class="form-control" rows="4" placeholder="Detail specifications, stacks, technologies and project limitations..." required></textarea>
                </div>
                <div class="mb-3">
                    <label class="form-label small fw-semibold text-secondary">Project Unit Pricing (USD)</label>
                    <input type="number" step="0.01" name="price" class="form-control" placeholder="50.00" required>
                </div>
                <div class="mb-3">
                    <label class="form-label small fw-semibold text-secondary">External Project Blueprint URL (Behance / GitHub)</label>
                    <input type="url" name="portfolio_link" class="form-control" placeholder="https://behance.net/design-spec">
                </div>
                <div class="mb-3">
                    <label class="form-label small fw-semibold text-secondary">Visual Marketing Banner Card Image</label>
                    <input type="file" name="gig_image" class="form-control" accept="image/*">
                </div>
                <button type="submit" name="publish_gig_btn" class="btn btn-velvet w-100 py-2.5 mt-2 shadow-sm">Deploy Structural Offering</button>
            </form>
        </div>
    </div>

    <!-- RIGHT PANEL CONSOLE: Dynamic Lists Displays -->
    <div class="col-lg-8">
        <!-- TOP ROW SUBSECTION: Core Services Directory Published -->
        <div class="card p-4 border-0 shadow-sm rounded-4 bg-white mb-4">
            <h4 class="fw-bold mb-1" style="color: #A78BC5;">📂 Your Live Service Catalogs</h4>
            <p class="text-muted small">Overview analytics of active records published under your profile index tracking node.</p>
            <hr>
            <?php if (empty($my_gigs)): ?>
                <p class="text-center text-muted py-4 small">No tracking points initialized on the public server cluster matrix yet.</p>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table align-middle">
                        <thead class="table-light">
                            <tr>
                                <th>Catalog Preview</th>
                                <th>Specification Info</th>
                                <th>Flat Rate</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($my_gigs as $g): ?>
                                <tr>
                                    <td>
                                        <img src="<?php echo htmlspecialchars($g['image_path']); ?>" style="width: 65px; height: 45px; object-fit: cover; border-radius: 6px;" class="border">
                                    </td>
                                    <td>
                                        <div class="fw-bold text-dark small"><?php echo htmlspecialchars($g['title']); ?></div>
                                        <span class="text-muted" style="font-size: 11px;">ID Parameter: #<?php echo $g['gig_id']; ?></span>
                                    </td>
                                    <td class="fw-bold text-secondary small">$<?php echo number_format($g['price'], 2); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>

        <!-- BOTTOM ROW SUBSECTION: Orders Delivery and File Transfer Controller Platform -->
        <div class="card p-4 shadow-sm border-0 rounded-4 bg-white">
            <h4 class="fw-bold mb-1" style="color: #A78BC5;">💼 Received Project Orders Pipeline</h4>
            <p class="text-muted small">Monitor operational assignments allocated to your node and upload client deliverables archive links.</p>
            <hr>
            <?php if (empty($received_orders)): ?>
                <p class="text-center text-muted py-4 small">No transactional tasks requested from consumer indexes yet.</p>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table align-middle">
                        <thead class="table-light">
                            <tr>
                                <th>Order ID</th>
                                <th>Target Service Specifications</th>
                                <th>Client Profile</th>
                                <th>Workflow Status</th>
                                <th class="text-center">Action Framework Console</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($received_orders as $order): ?>
                                <tr>
                                    <td>#<?php echo $order['order_id']; ?></td>
                                    <td class="fw-bold text-dark small"><?php echo htmlspecialchars($order['title']); ?></td>
                                    <td class="small">@<?php echo htmlspecialchars($order['buyer_name']); ?></td>
                                    <td>
                                        <?php if ($order['order_status'] === 'In Progress'): ?>
                                            <span class="badge bg-warning text-dark p-2">In Progress</span>
                                        <?php elseif ($order['order_status'] === 'Delivered'): ?>
                                            <span class="badge bg-info text-white p-2">Delivered (Pending)</span>
                                        <?php else: ?>
                                            <span class="badge bg-success text-white p-2">Completed Securely</span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="text-center">
                                        <?php if ($order['order_status'] === 'In Progress'): ?>
                                            <!-- Structural processing file upload payload wrapper form -->
                                            <form action="orders.php" method="POST" enctype="multipart/form-data" class="d-flex align-items-center justify-content-center gap-1">
                                                <input type="hidden" name="order_id" value="<?php echo $order['order_id']; ?>">
                                                <input type="file" name="delivery_file" class="form-control form-control-sm" required style="width: 150px; font-size:11px;">
                                                <button type="submit" name="upload_delivery_btn" class="btn btn-xs btn-velvet px-2 py-1 small" style="font-size:11px;">Submit</button>
                                            </form>
                                        <?php else: ?>
                                            <span class="text-muted small" style="font-size: 11px;">Pipeline Archived (Closed)</span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php include 'footer.php'; ?>