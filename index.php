<?php
include 'config.php';
include 'header.php';

try {
    $featured_sql = "SELECT gigs.*, users.username, users.platform_balance
                     FROM gigs
                     JOIN users ON gigs.user_id = users.user_id
                     ORDER BY users.platform_balance DESC, gigs.gig_id DESC
                     LIMIT 6";
    $featured_stmt = $conn->prepare($featured_sql);
    $featured_stmt->execute();
    $featured_gigs = $featured_stmt->fetchAll(PDO::FETCH_ASSOC);

    $freelancer_sql = "SELECT user_id, username, platform_balance
                       FROM users
                       WHERE role = 'seller'
                       ORDER BY platform_balance DESC, user_id DESC
                       LIMIT 6";
    $freelancer_stmt = $conn->prepare($freelancer_sql);
    $freelancer_stmt->execute();
    $top_freelancers = $freelancer_stmt->fetchAll(PDO::FETCH_ASSOC);

    $stats_sql = "SELECT
                    (SELECT COUNT(*) FROM users WHERE role = 'buyer') AS buyers_count,
                    (SELECT COUNT(*) FROM users WHERE role = 'seller') AS sellers_count,
                    (SELECT COUNT(*) FROM orders WHERE order_status = 'Completed') AS completed_orders";
    $stats_stmt = $conn->prepare($stats_sql);
    $stats_stmt->execute();
    $stats = $stats_stmt->fetch(PDO::FETCH_ASSOC);

    $reviews_sql = "SELECT users.username, COUNT(orders.order_id) AS completed_jobs
                    FROM orders
                    JOIN users ON orders.seller_id = users.user_id
                    WHERE orders.order_status = 'Completed'
                    GROUP BY users.user_id
                    ORDER BY completed_jobs DESC
                    LIMIT 3";
    $reviews_stmt = $conn->prepare($reviews_sql);
    $reviews_stmt->execute();
    $review_sources = $reviews_stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    error_log("Homepage fetch error: " . $e->getMessage());
    echo "<div class='alert alert-danger'>Error loading homepage data. Please try again later.</div>";
    $featured_gigs = [];
    $top_freelancers = [];
    $stats = ['buyers_count' => 0, 'sellers_count' => 0, 'completed_orders' => 0];
    $review_sources = [];
}

if (empty($review_sources)) {
    $review_sources = [
        ['username' => 'CreativeSeller', 'completed_jobs' => 0],
        ['username' => 'WebCraftStudio', 'completed_jobs' => 0],
        ['username' => 'BrandPilot', 'completed_jobs' => 0],
    ];
}

function balanceToRating($balance) {
    $rating = 3.5 + ($balance / 2000);
    if ($rating > 5) {
        $rating = 5;
    }
    return number_format($rating, 1);
}
?>

<style>
    .hero-shell {
        background: linear-gradient(120deg, #f8e8c8 0%, #f4dcc3 50%, #e9d2ae 100%);
        border-radius: 22px;
        border: 1px solid #e1c8a0;
        padding: 42px 28px;
        box-shadow: 0 12px 35px rgba(125, 95, 47, 0.14);
        margin-bottom: 34px;
        position: relative;
        overflow: hidden;
    }

    .hero-shell::after {
        content: '';
        position: absolute;
        right: -120px;
        top: -90px;
        width: 300px;
        height: 300px;
        border-radius: 50%;
        background: rgba(255, 255, 255, 0.28);
    }

    .metric-card {
        border-radius: 14px;
        border: 1px solid #ead6b4;
        background: #fff9ee;
        text-align: center;
        padding: 16px;
        height: 100%;
    }

    .section-title {
        color: #65481f;
        font-weight: 700;
    }

    .profile-card {
        border: 1px solid #ead6b4;
        border-radius: 14px;
        background: #fff;
        padding: 16px;
        height: 100%;
    }

    .review-card {
        border-radius: 16px;
        border: 1px solid #e8d4b2;
        background: linear-gradient(180deg, #fff9ed, #fff4df);
        padding: 18px;
        height: 100%;
    }

    .about-wrap {
        border-radius: 18px;
        background: #fffef9;
        border: 1px solid #ecdab9;
        padding: 26px;
    }

    .contact-strip {
        border-radius: 18px;
        background: #7d5f2f;
        color: #fff8eb;
        padding: 26px;
    }
</style>

<section class="hero-shell">
    <div class="row align-items-center">
        <div class="col-lg-7">
            <h1 class="display-5 fw-bold mb-3" style="color: #5e441e;">Build Faster With Trusted Freelancers</h1>
            <p class="lead" style="color: #6f5428;">A single marketplace for buyers and freelancers. Discover curated gigs, compare verified profiles, and start projects in minutes.</p>
            <div class="d-flex gap-2 flex-wrap mt-3">
                <a href="#featured-gigs" class="btn btn-velvet px-4">Explore Gigs</a>
                <?php if (isset($_SESSION['user_id'])): ?>
                    <?php if ($_SESSION['role'] === 'seller'): ?>
                        <a href="dashboard.php" class="btn btn-outline-velvet px-4">Open Seller Dashboard</a>
                    <?php else: ?>
                        <a href="buyer_dashboard.php" class="btn btn-outline-velvet px-4">Open Buyer Dashboard</a>
                    <?php endif; ?>
                <?php else: ?>
                    <a href="login.php" class="btn btn-outline-velvet px-4">Login By Role</a>
                <?php endif; ?>
            </div>
        </div>
        <div class="col-lg-5 mt-4 mt-lg-0">
            <div class="row g-2">
                <div class="col-4">
                    <div class="metric-card">
                        <div class="fw-bold fs-4" style="color:#7d5f2f;"><?php echo (int)$stats['buyers_count']; ?></div>
                        <small class="text-muted">Buyers</small>
                    </div>
                </div>
                <div class="col-4">
                    <div class="metric-card">
                        <div class="fw-bold fs-4" style="color:#7d5f2f;"><?php echo (int)$stats['sellers_count']; ?></div>
                        <small class="text-muted">Freelancers</small>
                    </div>
                </div>
                <div class="col-4">
                    <div class="metric-card">
                        <div class="fw-bold fs-4" style="color:#7d5f2f;"><?php echo (int)$stats['completed_orders']; ?></div>
                        <small class="text-muted">Completed</small>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<section class="mb-5" id="featured-freelancers">
    <div class="d-flex justify-content-between align-items-end mb-3">
        <h2 class="section-title mb-0">Top Freelancers</h2>
        <small class="text-muted">Ranked by platform balance</small>
    </div>
    <div class="row row-cols-1 row-cols-md-2 row-cols-lg-3 g-3">
        <?php if (empty($top_freelancers)): ?>
            <div class="col-12">
                <div class="alert alert-light border">No freelancer profiles available yet.</div>
            </div>
        <?php else: ?>
            <?php foreach ($top_freelancers as $freelancer): ?>
                <div class="col">
                    <div class="profile-card">
                        <div class="d-flex justify-content-between align-items-center">
                            <h5 class="mb-0"><?php echo htmlspecialchars($freelancer['username']); ?></h5>
                            <span class="badge text-bg-light border">Seller</span>
                        </div>
                        <div class="mt-2 text-muted small">Estimated rating</div>
                        <div class="fw-semibold" style="color:#7d5f2f;">
                            <?php echo balanceToRating((float)$freelancer['platform_balance']); ?>/5.0
                            <span class="small text-muted">(balance-based)</span>
                        </div>
                        <div class="mt-2 small text-muted">Platform balance</div>
                        <div class="fw-bold" style="color:#65481f;">$<?php echo number_format((float)$freelancer['platform_balance'], 2); ?></div>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</section>

<section class="mb-5" id="featured-gigs">
    <h2 class="section-title mb-3">Hero Gigs</h2>
    <div class="row row-cols-1 row-cols-md-2 row-cols-lg-3 g-4">
        <?php if (empty($featured_gigs)): ?>
            <div class="col-12 text-center py-4">
                <p class="text-muted fs-5">No gigs available yet.</p>
            </div>
        <?php else: ?>
            <?php foreach ($featured_gigs as $gig): ?>
                <div class="col">
                    <div class="card card-gig h-100">
                        <div class="gig-img-container">
                            <img src="<?php echo htmlspecialchars($gig['image_path']); ?>" class="gig-img" alt="Service preview">
                        </div>

                        <div class="card-body d-flex flex-column">
                            <div class="d-flex align-items-center justify-content-between mb-2">
                                <span class="badge bg-light text-dark border p-2 small">
                                    @<?php echo htmlspecialchars($gig['username']); ?>
                                </span>
                                <span class="small text-muted"><?php echo balanceToRating((float)$gig['platform_balance']); ?>/5.0</span>
                            </div>
                            <h5 class="card-title fw-bold text-dark mb-2"><?php echo htmlspecialchars($gig['title']); ?></h5>
                            <p class="card-text text-muted small flex-grow-1">
                                <?php echo htmlspecialchars(substr($gig['description'], 0, 120)) . (strlen($gig['description']) > 120 ? '...' : ''); ?>
                            </p>

                            <div class="d-flex justify-content-between align-items-center mt-3 pt-3 border-top">
                                <span class="text-muted small fw-semibold">Starting at</span>
                                <span class="fs-5 fw-bold" style="color: #7d5f2f;">$<?php echo number_format($gig['price'], 2); ?></span>
                            </div>
                        </div>

                        <div class="card-footer bg-transparent border-0 pt-0 pb-3">
                            <?php if (isset($_SESSION['user_id'])): ?>
                                <?php if ($_SESSION['user_id'] == $gig['user_id']): ?>
                                    <button class="btn btn-sm btn-secondary w-100 py-2 fw-semibold" disabled>Your own gig</button>
                                <?php else: ?>
                                    <a href="orders.php?action=place_order&gig_id=<?php echo $gig['gig_id']; ?>"
                                       class="btn btn-sm btn-outline-velvet w-100 py-2 fw-semibold"
                                       onclick="return confirm('Place this order now?');">
                                       Order Service
                                    </a>
                                <?php endif; ?>
                            <?php else: ?>
                                <a href="login.php" class="btn btn-sm btn-outline-velvet w-100 py-2 fw-semibold">Login to Order</a>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</section>

<section class="mb-5" id="reviews">
    <h2 class="section-title mb-3">What Buyers Say</h2>
    <div class="row row-cols-1 row-cols-md-3 g-3">
        <?php foreach ($review_sources as $review): ?>
            <div class="col">
                <div class="review-card">
                    <p class="mb-2" style="color:#5e441e;">"Fast communication, clear delivery, and professional quality. I would hire this freelancer again for new work."</p>
                    <div class="small text-muted">Buyer review for @<?php echo htmlspecialchars($review['username']); ?></div>
                    <div class="small mt-1" style="color:#7d5f2f;">Completed jobs: <?php echo (int)$review['completed_jobs']; ?></div>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
</section>

<section class="mb-5" id="about-us">
    <div class="about-wrap">
        <h2 class="section-title">About Us</h2>
        <p class="mb-2 text-muted">VelvetDesk is a two-sided freelance marketplace for students, creators, and builders who need quality work without the noise.</p>
        <p class="mb-2 text-muted">Buyers can discover trusted gigs and place orders quickly. Freelancers can publish services, manage deliveries, and build their profile strength through successful completed work.</p>
        <p class="mb-0 text-muted">The platform is intentionally simple: one public home, role-based login, and dedicated dashboards after sign in.</p>
    </div>
</section>

<section class="mb-4" id="contact-us">
    <div class="contact-strip d-flex flex-column flex-lg-row justify-content-between align-items-lg-center gap-3">
        <div>
            <h3 class="mb-1">Need Help Before You Start?</h3>
            <p class="mb-0">Talk to our team about posting services, placing orders, or account setup.</p>
        </div>
        <div class="d-flex gap-2">
            <a href="contact.php" class="btn btn-light fw-semibold">Open Contact Page</a>
            <a href="register.php" class="btn btn-outline-light fw-semibold">Create Account</a>
        </div>
    </div>
</section>

<?php include 'footer.php'; ?>