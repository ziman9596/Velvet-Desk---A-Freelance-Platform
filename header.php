<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>VelvetDesk - Freelance Marketplace</title>
    <!-- Bootstrap CSS for responsive design and UI components -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        :root {
            --jacarta: #3D2F5C;
            --dark-blue-gray: #6F5E93;
            --wisteria: #A895CD;
            --bright-gray: #E4E0F2;
            --black-coffee: #31212B;
            --jacarta-rgb: 61, 47, 92;
            --dark-blue-gray-rgb: 111, 94, 147;
            --wisteria-rgb: 168, 149, 205;
            --bright-gray-rgb: 228, 224, 242;
            --black-coffee-rgb: 49, 33, 43;
        }

        body { background: linear-gradient(180deg, rgba(var(--bright-gray-rgb), 0.96), #ffffff); min-height: 100vh; display: flex; flex-direction: column; font-family: 'Georgia', 'Times New Roman', serif; color: var(--black-coffee); }
        
        .navbar { background-color: var(--jacarta) !important; border-bottom: 3px solid var(--wisteria); }
        .navbar-brand { color: #FFFFFF !important; font-weight: 700; letter-spacing: -0.5px; }
        .nav-link { color: rgba(255, 255, 255, 0.9) !important; font-weight: 500; }
        .nav-link:hover { color: var(--bright-gray) !important; }
        
        .footer { background: linear-gradient(135deg, var(--jacarta), var(--black-coffee)); color: #FFFFFF; margin-top: auto; padding: 20px 0; border-top: 2px solid var(--wisteria); }
        
        .btn-velvet { background-color: var(--dark-blue-gray); color: white; border: none; font-weight: 600; border-radius: 8px; transition: 0.3s; }
        .btn-velvet:hover { background-color: var(--wisteria); color: var(--jacarta); transform: translateY(-1px); }
        .btn-outline-velvet { border: 2px solid var(--dark-blue-gray); color: var(--dark-blue-gray); font-weight: 600; border-radius: 8px; transition: 0.3s; }
        .btn-outline-velvet:hover { background-color: var(--dark-blue-gray); color: white; }
        
        .card-gig { border-radius: 16px; border: 1px solid rgba(var(--dark-blue-gray-rgb), 0.18); box-shadow: 0 4px 15px rgba(var(--jacarta-rgb), 0.14); background: #FFFFFF; transition: 0.3s; overflow: hidden; }
        .card-gig:hover { transform: translateY(-5px); box-shadow: 0 8px 25px rgba(var(--jacarta-rgb), 0.22); }
        .gig-img-container { height: 180px; overflow: hidden; background: linear-gradient(135deg, rgba(var(--bright-gray-rgb), 1), rgba(var(--wisteria-rgb), 0.35)); position: relative; }
        .gig-img { width: 100%; height: 100%; object-fit: cover; }

        .text-jacarta { color: var(--jacarta) !important; }
        .text-dark-blue-gray { color: var(--dark-blue-gray) !important; }
        .text-wisteria { color: var(--wisteria) !important; }
        .text-black-coffee { color: var(--black-coffee) !important; }
        .bg-bright-gray { background-color: var(--bright-gray) !important; }
        .bg-jacarta { background-color: var(--jacarta) !important; }
        .bg-dark-blue-gray { background-color: var(--dark-blue-gray) !important; }
        .bg-wisteria { background-color: var(--wisteria) !important; }
        .border-wisteria { border-color: var(--wisteria) !important; }
        .shadow-theme { box-shadow: 0 10px 30px rgba(var(--jacarta-rgb), 0.12) !important; }
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
                <li class="nav-item ms-lg-3 me-lg-2 my-2 my-lg-0">
                    <form action="index.php" method="GET" class="d-flex">
                        <input type="search" name="search" value="<?php echo htmlspecialchars($_GET['search'] ?? ''); ?>" class="form-control form-control-sm me-2" placeholder="Search work or freelancers" style="min-width: 210px;">
                        <button type="submit" class="btn btn-sm btn-outline-light">Search</button>
                    </form>
                </li>
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
                    
                    <li class="nav-item ms-lg-2 my-2 my-lg-0">
                        <a class="btn btn-sm btn-dark text-white px-3 py-2 d-inline-flex align-items-center justify-content-center" href="logout.php" style="white-space: nowrap;">Logout</a>
                    </li>
                <?php else: ?>
                    <li class="nav-item"><a class="nav-link" href="login.php">Login</a></li>
                    <li class="nav-item"><a class="nav-link btn btn-sm btn-velvet text-white px-3" href="register.php">Join</a></li>
                <?php endif; ?>
            </ul>
        </div>
    </div>
</nav>

<div class="container flex-grow-1">