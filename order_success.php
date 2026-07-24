<?php
include 'config.php';

// Security: Sirf logged in user hi is dynamic verification confirmation par aa sake
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

include 'header.php';
?>

<div class="row justify-content-center my-5">
    <div class="col-md-6 text-center">
        <div class="card p-5 shadow-sm border-0" style="border-radius: 16px; background: white; border: 1px solid rgba(111, 94, 147, 0.18);">
            <!-- Large green success badge visual aspect -->
            <div class="display-1 text-dark-blue-gray mb-4">✔️</div>
            <h2 class="fw-bold text-jacarta mb-3">Payment Successful!</h2>
            <p class="text-muted fs-5 mb-4">Aapka order process ho gaya hai aur transaction successfully authenticate ho chuki hai.</p>
            
            <div class="p-3 bg-bright-gray rounded-3 mb-4 font-monospace text-start small">
                <strong>Status:</strong> <span class="text-dark-blue-gray fw-bold">PAID (Captured)</span><br>
                <strong>Method:</strong> Instant Checkout Processing Loop<br>
                <strong>Date/Time:</strong> <?php echo date("Y-m-d H:i:s"); ?> (Live Server Timestamp)
            </div>

            <div class="d-grid gap-2">
                <a href="index.php" class="btn btn-velvet fw-bold py-2 fs-5">Back to Marketplace</a>
            </div>
        </div>
    </div>
</div>

<?php include 'footer.php'; ?>