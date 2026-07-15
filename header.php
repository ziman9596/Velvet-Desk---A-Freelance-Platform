<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>VelvetDesk - Freelance Marketplace</title>
    <!-- Bootstrap CSS for responsive design and UI components -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { background-color: #f7f2e7; min-height: 100vh; display: flex; flex-direction: column; font-family: 'Georgia', 'Times New Roman', serif; }
        
        /* Lilac color palette styling for brand consistency */
        .navbar { background-color: #7d5f2f !important; border-bottom: 3px solid #b69459; }
        .navbar-brand { color: #FFFFFF !important; font-weight: 700; letter-spacing: -0.5px; }
        .nav-link { color: #fff8eb !important; font-weight: 500; }
        .nav-link:hover { color: #f2d7a2 !important; }
        
        .footer { background-color: #9d7a40; color: #FFFFFF; margin-top: auto; padding: 20px 0; border-top: 2px solid #7d5f2f; }
        
        /* Button and card component styling */
        .btn-velvet { background-color: #7d5f2f; color: white; border: none; font-weight: 600; border-radius: 8px; transition: 0.3s; }
        .btn-velvet:hover { background-color: #9d7a40; color: white; transform: translateY(-1px); }
        .btn-outline-velvet { border: 2px solid #7d5f2f; color: #7d5f2f; font-weight: 600; border-radius: 8px; transition: 0.3s; }
        .btn-outline-velvet:hover { background-color: #7d5f2f; color: white; }
        
        .card-gig { border-radius: 16px; border: none; box-shadow: 0 4px 15px rgba(125, 95, 47, 0.14); background: #FFFFFF; transition: 0.3s; overflow: hidden; }
        .card-gig:hover { transform: translateY(-5px); box-shadow: 0 8px 25px rgba(125, 95, 47, 0.22); }
        .gig-img-container { height: 180px; overflow: hidden; background-color: #efe2cb; position: relative; }
        .gig-img { width: 100%; height: 100%; object-fit: cover; }
    </style>
</head>
<body>

<nav class="navbar navbar-expand-lg navbar-dark mb-4 shadow-sm">
    <div class="container">
        <!-- Brand logo and site name -->
        <a class="navbar-brand d-flex align-items-center fs-4" href="index.php">
            <img src="assets/image-removebg-preview.png" alt="VelvetDesk Logo" style="height: 35px; width: auto; margin-right: 12px; object-fit: contain;">
            VelvetDesk
        </a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="navbarNav">
            <ul class="navbar-nav ms-auto align-items-center">
                <li class="nav-item"><a class="nav-link" href="index.php">Home</a></li>
                <li class="nav-item"><a class="nav-link" href="index.php#featured-gigs">Gigs</a></li>
                <li class="nav-item"><a class="nav-link" href="index.php#reviews">Reviews</a></li>
                <li class="nav-item"><a class="nav-link" href="index.php#about-us">About</a></li>
                <li class="nav-item"><a class="nav-link" href="contact.php">Contact</a></li>
                <?php if (isset($_SESSION['user_id'])): ?>
                    <li class="nav-item">
                        <span class="nav-link text-light me-2">Welcome, <strong><?php echo htmlspecialchars($_SESSION['username']); ?></strong></span>
                    </li>
                    
                    <!-- Dynamic Dashboard Navigation Controls based on Role Matrix -->
                    <?php if ($_SESSION['role'] === 'seller'): ?>
                        <li class="nav-item"><a class="nav-link btn btn-sm btn-velvet text-white px-3 me-2" href="dashboard.php">Seller Dashboard</a></li>
                    <?php else: ?>
                        <li class="nav-item"><a class="nav-link btn btn-sm btn-velvet text-white px-3 me-2" href="buyer_dashboard.php">My Orders Panel</a></li>
                    <?php endif; ?>
                    
                    <li class="nav-item"><a class="nav-link text-white-50 btn btn-sm btn-dark px-3" href="logout.php">Logout</a></li>
                <?php else: ?>
                    <li class="nav-item"><a class="nav-link" href="login.php">Login</a></li>
                    <li class="nav-item"><a class="nav-link btn btn-sm btn-velvet text-white px-3" href="register.php">Join</a></li>
                <?php endif; ?>
            </ul>
        </div>
    </div>
</nav>

<div class="container flex-grow-1">