<?php
include 'config.php';
include 'header.php';

$search_term = trim($_GET['search'] ?? '');

try {
    $gigs_stmt = $conn->prepare("SELECT gigs.*, users.username
                                 FROM gigs
                                 JOIN users ON gigs.user_id = users.user_id
                                 WHERE users.role = 'seller'");
    $gigs_stmt->execute();
    $all_gigs = $gigs_stmt->fetchAll(PDO::FETCH_ASSOC);

    $sellers_stmt = $conn->prepare("SELECT user_id, username
                                    FROM users
                                    WHERE role = 'seller'");
    $sellers_stmt->execute();
    $all_sellers = $sellers_stmt->fetchAll(PDO::FETCH_ASSOC);

    $completed_stmt = $conn->prepare("SELECT seller_id, COUNT(*) AS completed_projects
                                      FROM orders
                                      WHERE order_status = 'Completed'
                                      GROUP BY seller_id");
    $completed_stmt->execute();
    $completed_map = [];
    foreach ($completed_stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
        $completed_map[(int)$row['seller_id']] = (int)$row['completed_projects'];
    }

    $reviews_stmt = $conn->prepare("SELECT seller_id, AVG(rating) AS average_review, COUNT(*) AS review_count
                                    FROM reviews
                                    GROUP BY seller_id");
    $reviews_stmt->execute();
    $review_map = [];
    foreach ($reviews_stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
        $review_map[(int)$row['seller_id']] = [
            'average_review' => (float)$row['average_review'],
            'review_count' => (int)$row['review_count'],
        ];
    }

    $featured_gigs = [];
    foreach ($all_gigs as $gig) {
        $seller_id = (int)$gig['user_id'];
        $completed_projects = $completed_map[$seller_id] ?? 0;
        $average_review = $review_map[$seller_id]['average_review'] ?? 0;
        $gig['completed_projects'] = $completed_projects;
        $gig['average_review'] = $average_review;
        $gig['freelancer_rating'] = calculateFreelancerRating($average_review, $completed_projects);

        $gig_search_blob = $gig['title'] . ' ' . $gig['description'] . ' ' . $gig['username'] . ' ' . ($gig['category'] ?? '') . ' ' . ($gig['subcategory'] ?? '') . ' ' . ($gig['service_type'] ?? '') . ' ' . ($gig['gig_tags'] ?? '');
        if ($search_term === '' || stripos($gig_search_blob, $search_term) !== false) {
            $featured_gigs[] = $gig;
        }
    }

    usort($featured_gigs, function ($left, $right) {
        if ($left['freelancer_rating'] === $right['freelancer_rating']) {
            if ($left['completed_projects'] === $right['completed_projects']) {
                return $right['gig_id'] <=> $left['gig_id'];
            }
            return $right['completed_projects'] <=> $left['completed_projects'];
        }
        return $right['freelancer_rating'] <=> $left['freelancer_rating'];
    });
    $featured_gigs = array_slice($featured_gigs, 0, 6);

    $top_freelancers = [];
    foreach ($all_sellers as $seller) {
        $seller_id = (int)$seller['user_id'];
        $completed_projects = $completed_map[$seller_id] ?? 0;
        $average_review = $review_map[$seller_id]['average_review'] ?? 0;
        $rating = calculateFreelancerRating($average_review, $completed_projects);

        if ($search_term === '' || stripos($seller['username'], $search_term) !== false) {
            $top_freelancers[] = [
                'user_id' => $seller_id,
                'username' => $seller['username'],
                'completed_projects' => $completed_projects,
                'average_review' => $average_review,
                'rating' => $rating,
            ];
        }
    }

    usort($top_freelancers, function ($left, $right) {
        if ($left['rating'] === $right['rating']) {
            if ($left['completed_projects'] === $right['completed_projects']) {
                return $right['user_id'] <=> $left['user_id'];
            }
            return $right['completed_projects'] <=> $left['completed_projects'];
        }
        return $right['rating'] <=> $left['rating'];
    });
    $top_freelancers = array_slice($top_freelancers, 0, 6);

    $stats_sql = "SELECT
                    (SELECT COUNT(*) FROM users WHERE role = 'buyer') AS buyers_count,
                    (SELECT COUNT(*) FROM users WHERE role = 'seller') AS sellers_count,
                    (SELECT COUNT(*) FROM orders WHERE order_status = 'Completed') AS completed_orders";
    $stats_stmt = $conn->prepare($stats_sql);
    $stats_stmt->execute();
    $stats = $stats_stmt->fetch(PDO::FETCH_ASSOC);

    $buyer_reviews_stmt = $conn->prepare("SELECT reviews.review_text, reviews.rating, reviews.created_at, reviewer.username AS reviewer_name, seller.username AS seller_name, reviews.seller_id
                                          FROM reviews
                                          JOIN users AS reviewer ON reviews.reviewer_id = reviewer.user_id
                                          JOIN users AS seller ON reviews.seller_id = seller.user_id
                                          ORDER BY reviews.created_at DESC, reviews.review_id DESC
                                          LIMIT 3");
    $buyer_reviews_stmt->execute();
    $review_sources = $buyer_reviews_stmt->fetchAll(PDO::FETCH_ASSOC);
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
        ['reviewer_name' => 'Amina', 'seller_name' => 'CreativeSeller', 'rating' => 5, 'review_text' => 'Trusted collaborators make the whole marketplace feel reliable.'],
        ['reviewer_name' => 'Noah', 'seller_name' => 'WebCraftStudio', 'rating' => 5, 'review_text' => 'Fast communication and precise delivery are the standard here.'],
        ['reviewer_name' => 'Mia', 'seller_name' => 'BrandPilot', 'rating' => 4, 'review_text' => 'The conversation-first flow makes project scoping much clearer.'],
    ];
}
?>

<style>
    .hero-shell {
        background: linear-gradient(120deg, rgba(168, 149, 205, 0.26) 0%, rgba(111, 94, 147, 0.18) 50%, rgba(61, 47, 92, 0.12) 100%);
        border-radius: 22px;
        border: 1px solid rgba(111, 94, 147, 0.24);
        padding: 42px 28px;
        box-shadow: 0 12px 35px rgba(61, 47, 92, 0.14);
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
        border: 1px solid rgba(168, 149, 205, 0.28);
        background: #ffffff;
        text-align: center;
        padding: 16px;
        height: 100%;
    }

    .section-title {
        color: var(--jacarta);
        font-weight: 700;
    }

    .profile-card {
        border: 1px solid rgba(111, 94, 147, 0.18);
        border-radius: 14px;
        background: #fff;
        padding: 16px;
        height: 100%;
    }

    .review-card {
        border-radius: 16px;
        border: 1px solid rgba(168, 149, 205, 0.22);
        background: linear-gradient(180deg, rgba(228, 224, 242, 0.78), rgba(255, 255, 255, 0.92));
        padding: 18px;
        height: 100%;
    }

    .about-wrap {
        border-radius: 18px;
        background: rgba(255, 255, 255, 0.9);
        border: 1px solid rgba(111, 94, 147, 0.18);
        padding: 26px;
    }

    .contact-strip {
        border-radius: 18px;
        background: linear-gradient(135deg, var(--jacarta), var(--dark-blue-gray));
        color: #fff;
        padding: 26px;
    }
</style>

<section class="hero-shell">
    <div class="row align-items-center">
        <div class="col-lg-7">
            <h1 class="display-5 fw-bold mb-3 text-jacarta">Build Faster With Trusted Freelancers</h1>
            <p class="lead text-dark-blue-gray">A single marketplace for buyers and freelancers. Discover curated gigs, compare verified profiles, and start projects in minutes.</p>
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
            <div class="mt-4 p-3 rounded-4 bg-white border" style="max-width: 560px;">
                <form action="index.php" method="GET" class="row g-2 align-items-center">
                    <div class="col-12 col-md-8">
                        <input type="search" name="search" value="<?php echo htmlspecialchars($search_term); ?>" class="form-control form-control-lg" placeholder="Search work or freelancers">
                    </div>
                    <div class="col-12 col-md-4 d-grid">
                        <button type="submit" class="btn btn-velvet btn-lg">Search</button>
                    </div>
                </form>
            </div>
        </div>
        <div class="col-lg-5 mt-4 mt-lg-0">
            <div class="row g-2">
                <div class="col-4">
                    <div class="metric-card">
                        <div class="fw-bold fs-4 text-dark-blue-gray"><?php echo (int)$stats['buyers_count']; ?></div>
                        <small class="text-muted">Buyers</small>
                    </div>
                </div>
                <div class="col-4">
                    <div class="metric-card">
                        <div class="fw-bold fs-4 text-dark-blue-gray"><?php echo (int)$stats['sellers_count']; ?></div>
                        <small class="text-muted">Freelancers</small>
                    </div>
                </div>
                <div class="col-4">
                    <div class="metric-card">
                        <div class="fw-bold fs-4 text-dark-blue-gray"><?php echo (int)$stats['completed_orders']; ?></div>
                        <small class="text-muted">Completed</small>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<?php if ($search_term !== ''): ?>
    <section class="mb-4">
        <div class="alert alert-light border shadow-sm">
            Showing results for <strong><?php echo htmlspecialchars($search_term); ?></strong>.
        </div>
    </section>
<?php endif; ?>

<section class="mb-5" id="featured-freelancers">
    <div class="d-flex justify-content-between align-items-end mb-3">
        <h2 class="section-title mb-0">Top Freelancers</h2>
        <small class="text-muted">Ranked by reviews and completed projects</small>
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
                        <div class="mt-2 text-muted small">Customer rating</div>
                        <div class="fw-semibold text-dark-blue-gray">
                            <?php echo number_format((float)$freelancer['rating'], 1); ?>/5.0
                            <span class="small text-muted">(reviews + projects)</span>
                        </div>
                        <div class="mt-2 small text-muted">Completed projects</div>
                        <div class="fw-bold text-jacarta"><?php echo (int)$freelancer['completed_projects']; ?></div>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</section>

<section class="mb-5" id="featured-gigs">
    <h2 class="section-title mb-3"><?php echo $search_term !== '' ? 'Search Results' : 'Hero Gigs'; ?></h2>
    <div class="row row-cols-1 row-cols-md-2 row-cols-lg-3 g-4">
        <?php if (empty($featured_gigs)): ?>
            <div class="col-12 text-center py-4">
                <p class="text-muted fs-5"><?php echo $search_term !== '' ? 'No matching gigs found.' : 'No gigs available yet.'; ?></p>
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
                                <span class="small text-muted"><?php echo number_format((float)$gig['freelancer_rating'], 1); ?>/5.0</span>
                            </div>
                            <div class="d-flex flex-wrap gap-2 mb-2">
                                <span class="badge bg-bright-gray text-jacarta border"><?php echo htmlspecialchars($gig['category'] ?: 'Uncategorized'); ?></span>
                                <span class="badge bg-light text-dark border"><?php echo htmlspecialchars($gig['subcategory'] ?: 'General'); ?></span>
                            </div>
                            <h5 class="card-title fw-bold text-dark mb-2"><?php echo htmlspecialchars($gig['title']); ?></h5>
                            <p class="card-text text-muted small flex-grow-1">
                                <?php echo htmlspecialchars(substr($gig['description'], 0, 120)) . (strlen($gig['description']) > 120 ? '...' : ''); ?>
                            </p>

                            <div class="d-flex justify-content-between align-items-center mt-3 pt-3 border-top">
                                <span class="text-muted small fw-semibold"><?php echo (int)$gig['completed_projects']; ?> completed projects</span>
                                <span class="fs-5 fw-bold text-jacarta">$<?php echo number_format($gig['price'], 2); ?></span>
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
                    <p class="mb-2 text-jacarta"><?php echo htmlspecialchars($review['review_text'] ?? 'Trusted collaborators make the whole marketplace feel reliable.'); ?></p>
                    <div class="small text-muted">
                        <?php echo htmlspecialchars($review['reviewer_name'] ?? 'A buyer'); ?> has reviewed @<?php echo htmlspecialchars($review['seller_name'] ?? 'a freelancer'); ?>'s work
                    </div>
                    <div class="small mt-1 text-dark-blue-gray">Rating: <?php echo isset($review['rating']) ? (int)$review['rating'] . '/5' : '5/5'; ?></div>
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