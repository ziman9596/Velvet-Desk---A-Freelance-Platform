<?php
include 'config.php'; // Database connection aur session start krna
$message = ""; // Handling error aur success responses ka stack container

if (isset($_POST['register_btn'])) {
    $username = trim($_POST['username']);
    $email = trim($_POST['email']);
    $password = $_POST['password'];
    $role = $_POST['role'];

    // Validation: Check empty inputs
    if (empty($username) || empty($email) || empty($password) || empty($role)) {
        $message = "<div class='alert alert-danger'>All registration fields are mandatory.</div>";
    } else {
        // Secure password processing workflow
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);

        try {
            // Prepared statement instantiation against SQL injection vectors
            $sql = "INSERT INTO users (username, email, password, role) VALUES (:username, :email, :password, :role)";
            $stmt = $conn->prepare($sql);
            
            $stmt->bindParam(':username', $username);
            $stmt->bindParam(':email', $email);
            $stmt->bindParam(':password', $hashed_password);
            $stmt->bindParam(':role', $role);
            
            $stmt->execute();
            
            $message = "<div class='alert alert-success'>Account registered successfully! You can now <a href='login.php' class='fw-bold text-decoration-none'>Login here</a>.</div>";
        } catch (PDOException $e) {
            // Constraint check bypass routing rules
            $message = "<div class='alert alert-danger'>Registration Failed: The provided email address is already registered.</div>";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>VelvetDesk - Create Account</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { background-color: #F2EEFF; min-height: 100vh; display: flex; align-items: center; justify-content: center; font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; }
        .card { border-radius: 20px; border: none; box-shadow: 0 10px 30px rgba(167, 139, 197, 0.2); width: 100%; max-width: 450px; }
        .btn-velvet { background-color: #A78BC5; color: white; border: none; font-weight: 600; transition: 0.3s; }
        .btn-velvet:hover { background-color: #C8A2C8; color: white; transform: translateY(-1px); }
        .form-control:focus, .form-select:focus { border-color: #A78BC5; box-shadow: 0 0 0 0.25rem rgba(167, 139, 197, 0.25); }
    </style>
</head>
<body>

<div class="card p-4 bg-white m-3">
    <div class="text-center mb-4">
        <!-- Logo Image Reference Integration Block -->
        <img src="assets/image-removebg-preview.png" alt="VelvetDesk Logo" style="height: 55px; width: auto; object-fit: contain;" class="mb-2">
        <h2 class="fw-bold" style="color: #A78BC5; margin-top: 5px;">VelvetDesk</h2>
        <p class="text-muted small">Join our premium decentralized workspace network</p>
    </div>
    
    <?php echo $message; ?>

    <form action="register.php" method="POST">
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
            <input type="password" name="password" class="form-control" placeholder="••••••••" required>
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
        <p class="mb-0 small text-muted">Already have an account? <a href="login.php" style="color: #A78BC5;" class="fw-semibold text-decoration-none">Sign In</a></p>
    </div>
</div>

</body>
</html>