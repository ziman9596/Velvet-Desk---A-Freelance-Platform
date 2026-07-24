<?php
include 'config.php';
include 'header.php';

// Check if user is authenticated and has buyer role
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'buyer') {
    header("Location: login.php");
    exit();
}

$current_user_id = $_SESSION['user_id'];
$csrf_token = generateCSRFToken();

// Fetch all orders placed by the current buyer
$sql = "SELECT orders.*, gigs.title, users.username AS seller_name FROM orders 
        JOIN gigs ON orders.gig_id = gigs.gig_id 
        JOIN users ON orders.seller_id = users.user_id 
        WHERE orders.buyer_id = :user_id ORDER BY orders.order_id DESC";

$stmt = $conn->prepare($sql);
$stmt->bindParam(':user_id', $current_user_id);
$stmt->execute();
$my_orders = $stmt->fetchAll(PDO::FETCH_ASSOC);

$reviews_stmt = $conn->prepare("SELECT order_id, rating, review_text FROM reviews WHERE reviewer_id = :user_id");
$reviews_stmt->bindParam(':user_id', $current_user_id);
$reviews_stmt->execute();
$my_reviews = [];
foreach ($reviews_stmt->fetchAll(PDO::FETCH_ASSOC) as $review) {
    $my_reviews[(int)$review['order_id']] = $review;
}
?>

<div class="card p-4 shadow-sm border-0 rounded-4 bg-white mt-3">
    <h3 class="fw-bold mb-1 text-jacarta">Buyer Dashboard</h3>
    <p class="text-muted small">Track active orders, download deliveries, and complete finished work.</p>
    <hr>

    <?php if (isset($_GET['success'])): ?>
        <div class="alert alert-success"><?php echo htmlspecialchars($_GET['success']); ?></div>
    <?php endif; ?>

    <?php if (empty($my_orders)): ?>
        <p class="text-center text-muted py-4">You have not placed any orders yet. <a href="index.php#featured-gigs">Browse gigs</a></p>
    <?php else: ?>
        <div class="table-responsive">
            <table class="table align-middle">
                <thead class="table-light">
                    <tr>
                        <th>Order ID</th>
                        <th>Service</th>
                        <th>Freelancer</th>
                        <th>Status</th>
                        <th class="text-center">Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($my_orders as $order): ?>
                        <tr>
                            <td>#<?php echo $order['order_id']; ?></td>
                            <td class="fw-bold text-dark"><?php echo htmlspecialchars($order['title']); ?></td>
                            <td>@<?php echo htmlspecialchars($order['seller_name']); ?></td>
                            <td>
                                <?php if ($order['order_status'] === 'Conversation Started'): ?>
                                    <span class="badge bg-dark-blue-gray text-white px-2.5 py-1.5">Conversation Started</span>
                                <?php elseif ($order['order_status'] === 'Confirmed'): ?>
                                    <span class="badge bg-primary text-white px-2.5 py-1.5">Confirmed</span>
                                <?php elseif ($order['order_status'] === 'In Progress'): ?>
                                    <span class="badge bg-warning text-dark px-2.5 py-1.5">In Progress</span>
                                <?php elseif ($order['order_status'] === 'Delivered'): ?>
                                    <span class="badge bg-info text-white px-2.5 py-1.5">Delivered</span>
                                <?php else: ?>
                                    <span class="badge bg-success text-white px-2.5 py-1.5">Completed</span>
                                <?php endif; ?>
                            </td>
                            <td class="text-center">
                                <div class="d-flex flex-column gap-2 align-items-center">
                                    <a href="chat.php?order_id=<?php echo $order['order_id']; ?>" class="btn btn-sm btn-outline-dark px-3">Open Chat</a>
                                    <?php if ($order['order_status'] === 'Delivered'): ?>
                                        <div class="d-flex justify-content-center gap-2 flex-wrap">
                                            <a href="<?php echo $order['delivery_file_path']; ?>" download class="btn btn-sm btn-outline-dark px-2.5">Download File</a>
                                            <span class="text-muted small align-self-center">Waiting for freelancer to close</span>
                                        </div>
                                    <?php elseif ($order['order_status'] === 'Completed'): ?>
                                        <a href="<?php echo $order['delivery_file_path']; ?>" download class="btn btn-sm btn-outline-secondary px-3">Download Asset Archive</a>
                                        <?php if (!isset($my_reviews[(int)$order['order_id']])): ?>
                                            <form action="orders.php" method="POST" class="w-100 text-start mt-1">
                                                <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
                                                <input type="hidden" name="order_id" value="<?php echo $order['order_id']; ?>">
                                                <select name="rating" class="form-select form-select-sm mb-1" required>
                                                    <option value="5">5 - Excellent</option>
                                                    <option value="4">4 - Very good</option>
                                                    <option value="3">3 - Good</option>
                                                    <option value="2">2 - Fair</option>
                                                    <option value="1">1 - Poor</option>
                                                </select>
                                                <textarea name="review_text" class="form-control form-control-sm mb-1" rows="2" placeholder="Write a short review" required></textarea>
                                                <button type="submit" name="submit_review_btn" class="btn btn-sm btn-velvet w-100">Submit Review</button>
                                            </form>
                                        <?php else: ?>
                                            <span class="small text-muted">Reviewed: <?php echo (int)$my_reviews[(int)$order['order_id']]['rating']; ?>/5</span>
                                        <?php endif; ?>
                                    <?php else: ?>
                                        <span class="text-muted small">Continue the discussion in chat.</span>
                                    <?php endif; ?>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</div>

<?php include 'footer.php'; ?>