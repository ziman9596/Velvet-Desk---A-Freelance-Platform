<?php
include 'config.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$order_id = intval($_GET['order_id'] ?? 0);
if ($order_id <= 0) {
    header("Location: index.php");
    exit();
}

$current_user_id = $_SESSION['user_id'];
$current_role = $_SESSION['role'];
$message = '';

try {
    $order_stmt = $conn->prepare("SELECT orders.*, gigs.title, gigs.description, gigs.price, buyer.username AS buyer_name, seller.username AS seller_name
                                  FROM orders
                                  JOIN gigs ON orders.gig_id = gigs.gig_id
                                  JOIN users AS buyer ON orders.buyer_id = buyer.user_id
                                  JOIN users AS seller ON orders.seller_id = seller.user_id
                                  WHERE orders.order_id = :order_id");
    $order_stmt->bindParam(':order_id', $order_id);
    $order_stmt->execute();
    $order = $order_stmt->fetch(PDO::FETCH_ASSOC);

    if (!$order) {
        header("Location: index.php");
        exit();
    }

    if ($current_user_id != $order['buyer_id'] && $current_user_id != $order['seller_id']) {
        header("Location: index.php");
        exit();
    }

    if (isset($_POST['send_message_btn'])) {
        if (!isset($_POST['csrf_token']) || !validateCSRFToken($_POST['csrf_token'])) {
            $message = "<div class='alert alert-danger'>Security validation failed. Please try again.</div>";
        } else {
            $message_text = trim($_POST['message_text'] ?? '');
            if ($message_text === '') {
                $message = "<div class='alert alert-danger'>Please type a message before sending.</div>";
            } else {
                $insert = $conn->prepare("INSERT INTO order_messages (order_id, sender_id, message_text) VALUES (:order_id, :sender_id, :message_text)");
                $insert->bindParam(':order_id', $order_id);
                $insert->bindParam(':sender_id', $current_user_id);
                $insert->bindParam(':message_text', $message_text);
                $insert->execute();
                header("Location: chat.php?order_id=" . $order_id);
                exit();
            }
        }
    }

    $messages_stmt = $conn->prepare("SELECT order_messages.*, users.username, users.role
                                      FROM order_messages
                                      JOIN users ON order_messages.sender_id = users.user_id
                                      WHERE order_messages.order_id = :order_id
                                      ORDER BY order_messages.created_at ASC, order_messages.message_id ASC");
    $messages_stmt->bindParam(':order_id', $order_id);
    $messages_stmt->execute();
    $chat_messages = $messages_stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    error_log('Chat page error: ' . $e->getMessage());
    die('Unable to load the conversation right now.');
}

$csrf_token = generateCSRFToken();
include 'header.php';
?>

<div class="row mt-3 g-4">
    <div class="col-lg-8">
        <?php echo $message; ?>

        <div class="card border-0 shadow-sm rounded-4 mb-4">
            <div class="card-body p-4">
                <div class="d-flex justify-content-between align-items-start flex-wrap gap-2">
                    <div>
                        <h3 class="fw-bold mb-1 text-jacarta"><?php echo htmlspecialchars($order['title']); ?></h3>
                        <p class="text-muted mb-0">Buyer: @<?php echo htmlspecialchars($order['buyer_name']); ?> | Freelancer: @<?php echo htmlspecialchars($order['seller_name']); ?></p>
                    </div>
                    <span class="badge bg-dark-blue-gray px-3 py-2">Status: <?php echo htmlspecialchars($order['order_status']); ?></span>
                </div>
                <hr>
                <p class="text-muted mb-0"><?php echo htmlspecialchars($order['description']); ?></p>
            </div>
        </div>

        <div class="card border-0 shadow-sm rounded-4">
            <div class="card-body p-4">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h4 class="fw-bold mb-0 text-jacarta">Conversation</h4>
                    <small class="text-muted">Messages are stored in the database as part of the order thread.</small>
                </div>

                <?php if (isset($_GET['success'])): ?>
                    <div class="alert alert-success py-2"><?php echo htmlspecialchars($_GET['success']); ?></div>
                <?php endif; ?>

                <?php if (isset($_GET['error'])): ?>
                    <div class="alert alert-danger py-2"><?php echo htmlspecialchars($_GET['error']); ?></div>
                <?php endif; ?>

                <div class="chat-stream bg-bright-gray rounded-4 p-3 mb-3" style="max-height: 420px; overflow-y: auto;">
                    <?php if (empty($chat_messages)): ?>
                        <div class="text-center text-muted py-4">No messages yet. Start the conversation.</div>
                    <?php else: ?>
                        <?php foreach ($chat_messages as $chat_message): ?>
                            <div class="mb-3 d-flex <?php echo $chat_message['sender_id'] == $current_user_id ? 'justify-content-end' : 'justify-content-start'; ?>">
                                <div class="p-3 rounded-4 shadow-sm <?php echo $chat_message['sender_id'] == $current_user_id ? 'bg-jacarta text-white' : 'bg-white'; ?>" style="max-width: 78%;">
                                    <div class="d-flex justify-content-between gap-3 mb-1 small <?php echo $chat_message['sender_id'] == $current_user_id ? 'text-white-50' : 'text-muted'; ?>">
                                        <strong>@<?php echo htmlspecialchars($chat_message['username']); ?></strong>
                                        <span><?php echo htmlspecialchars($chat_message['created_at']); ?></span>
                                    </div>
                                    <div style="white-space: pre-wrap;"><?php echo htmlspecialchars($chat_message['message_text']); ?></div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>

                <form action="chat.php?order_id=<?php echo $order_id; ?>" method="POST">
                    <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
                    <label class="form-label fw-semibold text-secondary">Send a message</label>
                    <textarea name="message_text" class="form-control mb-3" rows="4" placeholder="Discuss the scope, delivery, and next steps..."></textarea>
                    <button type="submit" name="send_message_btn" class="btn btn-velvet px-4">Send Message</button>
                </form>
            </div>
        </div>
    </div>

    <div class="col-lg-4">
        <div class="card border-0 shadow-sm rounded-4 mb-4">
            <div class="card-body p-4">
                <h5 class="fw-bold text-jacarta mb-3">Order Details</h5>
                <div class="small text-muted mb-2">Order ID: #<?php echo (int)$order['order_id']; ?></div>
                <div class="small text-muted mb-2">Price: $<?php echo number_format((float)$order['price'], 2); ?></div>
                <div class="small text-muted mb-2">Conversation started: <?php echo htmlspecialchars($order['conversation_started_at'] ?? 'Pending'); ?></div>
                <div class="small text-muted mb-2">Order placed: <?php echo htmlspecialchars($order['order_placed_at'] ?? 'Not yet confirmed'); ?></div>
                <div class="small text-muted">Confirmed at: <?php echo htmlspecialchars($order['confirmed_at'] ?? 'Not yet confirmed'); ?></div>
            </div>
        </div>

        <div class="card border-0 shadow-sm rounded-4">
            <div class="card-body p-4">
                <h5 class="fw-bold text-jacarta mb-3">Next Step</h5>
                <?php if ($current_role === 'seller' && $order['order_status'] === 'Conversation Started'): ?>
                    <p class="text-muted small">Confirm the order once the discussion is complete.</p>
                    <form action="orders.php" method="POST">
                        <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
                        <input type="hidden" name="order_id" value="<?php echo (int)$order['order_id']; ?>">
                        <button type="submit" name="confirm_order_btn" class="btn btn-velvet w-100">Confirm Order</button>
                    </form>
                <?php elseif ($current_role === 'seller' && ($order['order_status'] === 'Confirmed' || $order['order_status'] === 'In Progress')): ?>
                    <p class="text-muted small">The order is confirmed. Upload the finished assets when the work is ready.</p>
                    <form action="orders.php" method="POST" enctype="multipart/form-data">
                        <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
                        <input type="hidden" name="order_id" value="<?php echo (int)$order['order_id']; ?>">
                        <input type="file" name="delivery_file" class="form-control form-control-sm mb-2" required>
                        <button type="submit" name="upload_delivery_btn" class="btn btn-velvet w-100">Upload Assets</button>
                    </form>
                <?php elseif ($current_role === 'seller' && $order['order_status'] === 'Delivered'): ?>
                    <p class="text-muted small">Close the order after the delivery assets have been uploaded.</p>
                    <form action="orders.php" method="POST">
                        <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
                        <input type="hidden" name="order_id" value="<?php echo (int)$order['order_id']; ?>">
                        <button type="submit" name="close_order_btn" class="btn btn-success w-100">Close Order</button>
                    </form>
                <?php elseif ($order['order_status'] === 'Completed'): ?>
                    <p class="text-muted small mb-0">This order is complete. You can still keep the conversation history here.</p>
                <?php else: ?>
                    <p class="text-muted small mb-0"><?php echo $current_role === 'buyer' ? 'Keep discussing the work here. Your freelancer will move the order forward.' : 'Keep discussing the work here until both sides are ready to move forward.'; ?></p>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<?php include 'footer.php'; ?>
