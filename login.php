<?php
include 'config.php';
$message = "";

// Process login form submission with CSRF protection
if (isset($_POST['login_btn'])) {
    // Validate CSRF token
    if (!isset($_POST['csrf_token']) || !validateCSRFToken($_POST['csrf_token'])) {
        $message = "<div class='alert alert-danger'>Security validation failed. Please try again.</div>";
    } else {
        $email = trim($_POST['email']);
        $password = $_POST['password'];

        // Validate input
        if (empty($email) || empty($password)) {
            $message = "<div class='alert alert-danger'>Email and password are required.</div>";
        } else {
            try {
                // Fetch user record from database
                $sql = "SELECT * FROM users WHERE email = :email";
                $stmt = $conn->prepare($sql);
                $stmt->bindParam(':email', $email);
                $stmt->execute();
                $user = $stmt->fetch(PDO::FETCH_ASSOC);

                // Verify password hash
                if ($user && password_verify($password, $user['password'])) {
                    $_SESSION['user_id'] = $user['user_id'];
                    $_SESSION['username'] = $user['username'];
                    $_SESSION['role'] = $user['role'];
                    header("Location: index.php");
                    exit();
                } else {
                    $message = "<div class='alert alert-danger'>Invalid email or password.</div>";
                }
            } catch(PDOException $e) { 
                error_log("Login error: " . $e->getMessage());
                $message = "<div class='alert alert-danger'>An error occurred. Please try again later.</div>"; 
            }
        }
    }
}

// Generate CSRF token for the form
$csrf_token = generateCSRFToken();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>VelvetDesk - Portal Authentication</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { background-color: #F2EEFF; min-height: 100vh; display: flex; align-items: center; justify-content: center; font-family: 'Segoe UI', sans-serif; }
        .card { border-radius: 20px; border: none; box-shadow: 0 10px 30px rgba(167, 139, 197, 0.2); width: 100%; max-width: 420px; }
        .btn-velvet { background-color: #A78BC5; color: white; border: none; font-weight:600; }
        .btn-velvet:hover { background-color: #C8A2C8; color:white; }
    </style>
</head>
<body>
<div class="card p-4 bg-white shadow-lg">
    <div class="text-center mb-4">
        <!-- Unified Image Logo Component -->
        <img src="assets/image-removebg-preview.png" alt="VelvetDesk Logo" style="height: 55px; width: auto; object-fit: contain;" class="mb-2">
        <h2 class="fw-bold" style="color: #A78BC5; margin-top: 5px;">VelvetDesk</h2>
        <p class="text-muted small">Sign in to your premium specialized ecosystem</p>
    </div>
    
    <?php echo $message; ?>
    
    <form action="login.php" method="POST">
        <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
        <div class="mb-3">
            <label class="form-label fw-semibold text-secondary">Email Address</label>
            <input type="email" name="email" class="form-control" placeholder="name@example.com" required>
        </div>
        <div class="mb-3">
            <label class="form-label fw-semibold text-secondary">Secure Password</label>
            <input type="password" name="password" class="form-control" placeholder="••••••••" required>
        </div>
        <button type="submit" name="login_btn" class="btn btn-velvet w-100 py-2.5 rounded-3">Authenticate Account</button>
    </form>
    <div class="text-center mt-4">
        <p class="mb-0 small text-muted">New to our network? <a href="register.php" style="color: #A78BC5;" class="fw-semibold text-decoration-none">Create Account</a></p>
    </div>
</div>
</body>
</html>