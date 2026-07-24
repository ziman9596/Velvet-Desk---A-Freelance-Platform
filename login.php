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
        $selected_role = $_POST['role'] ?? '';

        // Validate input
        if (empty($email) || empty($password) || empty($selected_role)) {
            $message = "<div class='alert alert-danger'>Email, password, and role are required.</div>";
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
                    if ($user['role'] !== $selected_role) {
                        $message = "<div class='alert alert-danger'>Selected role does not match this account.</div>";
                    } else {
                    $_SESSION['user_id'] = $user['user_id'];
                    $_SESSION['username'] = $user['username'];
                    $_SESSION['role'] = $user['role'];
                    if ($user['role'] === 'seller') {
                        header("Location: dashboard.php");
                    } else {
                        header("Location: buyer_dashboard.php");
                    }
                    exit();
                    }
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
    <title>VelvetDesk - Sign In</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        :root {
            --jacarta: #3D2F5C;
            --dark-blue-gray: #6F5E93;
            --wisteria: #A895CD;
            --bright-gray: #E4E0F2;
            --black-coffee: #31212B;
        }

        body { background: radial-gradient(circle at 15% 20%, rgba(168, 149, 205, 0.35), rgba(228, 224, 242, 0.9) 35%, #ffffff 70%); min-height: 100vh; display: flex; align-items: center; justify-content: center; font-family: 'Georgia', 'Times New Roman', serif; color: var(--black-coffee); }
        .card { border-radius: 18px; border: 1px solid rgba(111, 94, 147, 0.18); box-shadow: 0 14px 35px rgba(61, 47, 92, 0.12); width: 100%; max-width: 450px; }
        .btn-velvet { background-color: var(--dark-blue-gray); color: white; border: none; font-weight:600; }
        .btn-velvet:hover { background-color: var(--wisteria); color: var(--jacarta); }
        .form-control:focus, .form-select:focus { border-color: var(--wisteria); box-shadow: 0 0 0 0.25rem rgba(168, 149, 205, 0.2); }
    </style>
</head>
<body>
<div class="card p-4 bg-white shadow-lg">
    <div class="text-center mb-4">
        <!-- Unified Image Logo Component -->
        <img src="assets/image-removebg-preview.png" alt="VelvetDesk Logo" style="height: 55px; width: auto; object-fit: contain;" class="mb-2">
        <h2 class="fw-bold text-jacarta" style="margin-top: 5px;">VelvetDesk</h2>
        <p class="text-muted small">Choose your role and enter your account</p>
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
        <div class="mb-3">
            <label class="form-label fw-semibold text-secondary">I am signing in as</label>
            <select name="role" class="form-select" required>
                <option value="" disabled selected>Select role</option>
                <option value="buyer">Buyer</option>
                <option value="seller">Freelancer</option>
            </select>
        </div>
        <button type="submit" name="login_btn" class="btn btn-velvet w-100 py-2.5 rounded-3">Authenticate Account</button>
    </form>
    <div class="text-center mt-4">
        <p class="mb-0 small text-muted">New to our network? <a href="register.php" class="fw-semibold text-decoration-none text-wisteria">Create Account</a></p>
    </div>
</div>
</body>
</html>