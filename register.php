<?php
include 'config.php';
$message = "";

// Process registration form with CSRF protection
if (isset($_POST['register_btn'])) {
    // Validate CSRF token
    if (!isset($_POST['csrf_token']) || !validateCSRFToken($_POST['csrf_token'])) {
        $message = "<div class='alert alert-danger'>Security validation failed. Please try again.</div>";
    } else {
        $username = trim($_POST['username']);
        $email = trim($_POST['email']);
        $password = $_POST['password'];
        $confirm_password = $_POST['confirm_password'];
        $role = $_POST['role'];

        // Input validation
        if (empty($username) || empty($email) || empty($password) || empty($role)) {
            $message = "<div class='alert alert-danger'>All registration fields are required.</div>";
        } elseif (strlen($password) < 6) {
            $message = "<div class='alert alert-danger'>Password must be at least 8 characters long.</div>";
        } elseif (!preg_match('/^(?=.*[A-Za-z])(?=.*\d)(?=.*[^A-Za-z\d]).{8,}$/', $password)) {
            $message = "<div class='alert alert-danger'>Password must include letters, numbers, and a special character.</div>";
        } elseif ($password !== $confirm_password) {
            $message = "<div class='alert alert-danger'>Passwords do not match.</div>";
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $message = "<div class='alert alert-danger'>Invalid email address format.</div>";
        } else {
            // Hash password securely
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);

            try {
                // Insert new user with prepared statement to prevent SQL injection
                $sql = "INSERT INTO users (username, email, password, role) VALUES (:username, :email, :password, :role)";
                $stmt = $conn->prepare($sql);
                
                $stmt->bindParam(':username', $username);
                $stmt->bindParam(':email', $email);
                $stmt->bindParam(':password', $hashed_password);
                $stmt->bindParam(':role', $role);
                
                $stmt->execute();
                
                $message = "<div class='alert alert-success'>Account registered successfully! You can now <a href='login.php' class='fw-bold text-decoration-none'>Login here</a>.</div>";
            } catch (PDOException $e) {
                // Check if it's a duplicate email error
                error_log("Registration error: " . $e->getMessage());
                $message = "<div class='alert alert-danger'>Registration failed. This email may already be registered.</div>";
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
    <title>VelvetDesk - Create Account</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        :root {
            --jacarta: #3D2F5C;
            --dark-blue-gray: #6F5E93;
            --wisteria: #A895CD;
            --bright-gray: #E4E0F2;
            --black-coffee: #31212B;
        }

        body { background: linear-gradient(180deg, rgba(228, 224, 242, 0.92), #ffffff); min-height: 100vh; display: flex; align-items: center; justify-content: center; font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; color: var(--black-coffee); }
        .card { border-radius: 20px; border: 1px solid rgba(111, 94, 147, 0.16); box-shadow: 0 10px 30px rgba(61, 47, 92, 0.18); width: 100%; max-width: 450px; }
        .btn-velvet { background-color: var(--dark-blue-gray); color: white; border: none; font-weight: 600; transition: 0.3s; }
        .btn-velvet:hover { background-color: var(--wisteria); color: var(--jacarta); transform: translateY(-1px); }
        .form-control:focus, .form-select:focus { border-color: var(--wisteria); box-shadow: 0 0 0 0.25rem rgba(168, 149, 205, 0.25); }
    </style>
</head>
<body>

<div class="card p-4 bg-white m-3">
    <div class="text-center mb-4">
        <!-- Logo Image Reference Integration Block -->
        <img src="assets/image-removebg-preview.png" alt="VelvetDesk Logo" style="height: 55px; width: auto; object-fit: contain;" class="mb-2">
        <h2 class="fw-bold text-jacarta" style="margin-top: 5px;">VelvetDesk</h2>
        <p class="text-muted small">Join our premium decentralized workspace network</p>
    </div>
    
    <?php echo $message; ?>

    <form action="register.php" method="POST">
        <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
        <div class="mb-3">
            <label class="form-label fw-semibold text-secondary">Username</label>
            <input type="text" name="username" class="form-control" placeholder="e.g., imanzainab" required>
        </div>
        <div class="mb-3">
            <label class="form-label fw-semibold text-secondary">Email Address</label>
            <input type="email" name="email" class="form-control" placeholder="name@example.com" required>
        </div>
        <div class="mb-3">
            <label class="form-label fw-semibold text-secondary">Password</label>
            <input type="password" name="password" class="form-control" placeholder="••••••••" required minlength="8" pattern="(?=.*[A-Za-z])(?=.*\d)(?=.*[^A-Za-z\d]).{8,}" title="Use at least 8 characters with letters, numbers, and a special character.">
        </div>
        <div class="mb-3">
            <label class="form-label fw-semibold text-secondary">Confirm Password</label>
            <input type="password" name="confirm_password" class="form-control" placeholder="••••••••" required>
        </div>
        <div class="mb-3">
            <label class="form-label fw-semibold text-secondary">Account Type</label>
            <select name="role" class="form-select" required>
                <option value="" disabled selected>Select your system role...</option>
                <option value="buyer">Client (Buy Services)</option>
                <option value="seller">Freelancer (Offer Services)</option>
            </select>
        </div>
        <button type="submit" name="register_btn" class="btn btn-velvet w-100 py-2.5 rounded-3 mt-2">Get Started</button>
    </form>
    
    <div class="text-center mt-4">
        <p class="mb-0 small text-muted">Already have an account? <a href="login.php" class="fw-semibold text-decoration-none text-wisteria">Sign In</a></p>
    </div>
</div>

</body>
</html>