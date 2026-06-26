<?php
// Active database connection config import
include 'config.php';
include 'header.php';

try {
    // Marketplace se saari services fetch krna metadata settings ke sath
    $sql = "SELECT gigs.*, users.username FROM gigs 
            JOIN users ON gigs.user_id = users.user_id 
            ORDER BY gigs.gig_id DESC";
    $stmt = $conn->prepare($sql);
    $stmt->execute();
    $gigs_dataset = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    echo "<div class='alert alert-danger'>Marketplace Fetch Error: " . htmlspecialchars($e->getMessage()) . "</div>";
    $gigs_dataset = [];
}
?>

<div class="row mb-4">
    <div class="col-12 text-center my-4">
        <h1 class="fw-bold display-5" style="color: #A78BC5;">Premium Freelance Marketplace</h1>
        <p class="text-muted">Explore niche specialized developer frameworks and solutions</p>
    </div>
</div>

<div class="row row-cols-1 row-cols-md-2 row-cols-lg-3 g-4">
    <?php if (empty($gigs_dataset)): ?>
        <div class="col-12 text-center py-5">
            <p class="text-muted fs-5">No service parameters published on the network yet.</p>
        </div>
    <?php else: ?>
        <?php foreach ($gigs_dataset as $gig): ?>
            <div class="col">
                <div class="card card-gig h-100">
                    <!-- Image render box block -->
                    <div class="gig-img-container">
                        <img src="<?php echo htmlspecialchars($gig['image_path']); ?>" class="gig-img" alt="Service Preview">
                    </div>
                    
                    <div class="card-body d-flex flex-column">
                        <div class="d-flex align-items-center mb-2">
                            <span class="badge bg-light text-dark border p-2 small">
                                🧑‍💻 @<?php echo htmlspecialchars($gig['username']); ?>
                            </span>
                        </div>
                        <h5 class="card-title fw-bold text-dark mb-2"><?php echo htmlspecialchars($gig['title']); ?></h5>
                        <p class="card-text text-muted small flex-grow-1">
                            <?php echo htmlspecialchars(substr($gig['description'], 0, 120)) . (strlen($gig['description']) > 120 ? '...' : ''); ?>
                        </p>
                        
                        <div class="d-flex justify-content-between align-items-center mt-3 pt-3 border-top">
                            <span class="text-muted small fw-semibold">Starting Budget</span>
                            <span class="fs-5 fw-bold" style="color: #A78BC5;">$<?php echo number_format($gig['price'], 2); ?></span>
                        </div>
                    </div>
                    
                    <!-- Dynamic Validation Button Controller Box -->
                    <div class="card-footer bg-transparent border-0 pt-0 pb-3">
                        <?php if (isset($_SESSION['user_id'])): ?>
                            <?php if ($_SESSION['user_id'] == $gig['user_id']): ?>
                                <!-- Conflict Bypass rule setup: No self buying -->
                                <button class="btn btn-sm btn-secondary w-100 py-2 fw-semibold" disabled>Your Own Listing</button>
                            <?php else: ?>
                                <!-- Silent routing initialization link -->
                                <a href="orders.php?action=place_order&gig_id=<?php echo $gig['gig_id']; ?>" 
                                   class="btn btn-sm btn-outline-velvet w-100 py-2 fw-semibold" 
                                   onclick="return confirm('Do you want to initialize a service engagement pipeline for this offering?');">
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

<?php include 'footer.php'; ?>