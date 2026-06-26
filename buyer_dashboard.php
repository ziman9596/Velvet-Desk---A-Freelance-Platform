<?php
include 'config.php';
include 'header.php';

// Check if user is authenticated and has buyer role
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'buyer') {
    header("Location: login.php");
    exit();
}

$current_user_id = $_SESSION['user_id'];

// Fetch all orders placed by the current buyer
$sql = "SELECT orders.*, gigs.title, users.username AS seller_name FROM orders 
        JOIN gigs ON orders.gig_id = gigs.gig_id 
        JOIN users ON orders.seller_id = users.user_id 
        WHERE orders.buyer_id = :user_id ORDER BY orders.order_id DESC";

$stmt = $conn->prepare($sql);
$stmt->bindParam(':user_id', $current_user_id);
$stmt->execute();
$my_orders = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<div class="card p-4 shadow-sm border-0 rounded-4 bg-white mt-3">
    <h3 class="fw-bold mb-1" style="color: #A78BC5;">🛍️ Customer Procurement Panel</h3>
    <p class="text-muted small">Track your active order pathways, access deliverables, and manage system confirmations.</p>
    <hr>

    <?php if (isset($_GET['success'])): ?>
        <div class="alert alert-success"><?php echo htmlspecialchars($_GET['success']); ?></div>
    <?php endif; ?>

    <?php if (empty($my_orders)): ?>
        <p class="text-center text-muted py-4">You haven't placed any system orders yet. <a href="index.php">Browse services</a></p>
    <?php else: ?>
        <div class="table-responsive">
            <table class="table align-middle">
                <thead class="table-light">
                    <tr>
                        <th>Order ID</th>
                        <th>Purchased Service</th>
                        <th>Assigned Seller</th>
                        <th>Pipeline Status</th>
                        <th class="text-center">Operations Console</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($my_orders as $order): ?>
                        <tr>
                            <td>#<?php echo $order['order_id']; ?></td>
                            <td class="fw-bold text-dark"><?php echo htmlspecialchars($order['title']); ?></td>
                            <td>@<?php echo htmlspecialchars($order['seller_name']); ?></td>
                            <td>
                                <?php if ($order['order_status'] === 'In Progress'): ?>
                                    <span class="badge bg-warning text-dark px-2.5 py-1.5">In Progress</span>
                                <?php elseif ($order['order_status'] === 'Delivered'): ?>
                                    <span class="badge bg-info text-white px-2.5 py-1.5">Delivered (Pending)</span>
                                <?php else: ?>
                                    <span class="badge bg-success text-white px-2.5 py-1.5">Completed</span>
                                <?php endif; ?>
                            </td>
                            <td class="text-center">
                                <?php if ($order['order_status'] === 'Delivered'): ?>
                                    <div class="d-flex justify-content-center gap-2">
                                        <a href="<?php echo $order['delivery_file_path']; ?>" download class="btn btn-sm btn-outline-dark px-2.5">Download File</a>
                                        <a href="orders.php?action=approve&order_id=<?php echo $order['order_id']; ?>" class="btn btn-sm btn-success px-2.5">Approve & Close</a>
                                    </div>
                                <?php elseif ($order['order_status'] === 'Completed'): ?>
                                    <a href="<?php echo $order['delivery_file_path']; ?>" download class="btn btn-sm btn-outline-secondary px-3">Download Asset Archive</a>
                                <?php else: ?>
                                    <span class="text-muted small">Waiting for Seller upload...</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</div>

<?php include 'footer.php'; ?>