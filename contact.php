<?php
include 'config.php';
include 'header.php';

$message = '';

if (isset($_POST['contact_btn'])) {
    if (!isset($_POST['csrf_token']) || !validateCSRFToken($_POST['csrf_token'])) {
        $message = "<div class='alert alert-danger'>Security validation failed. Please refresh and try again.</div>";
    } else {
        $name = trim($_POST['name'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $subject = trim($_POST['subject'] ?? '');
        $details = trim($_POST['details'] ?? '');

        if ($name === '' || $email === '' || $subject === '' || $details === '') {
            $message = "<div class='alert alert-danger'>Please complete all fields.</div>";
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $message = "<div class='alert alert-danger'>Please provide a valid email address.</div>";
        } else {
            $message = "<div class='alert alert-success'>Thank you. Your message has been received. Our team will contact you soon.</div>";
        }
    }
}

$csrf_token = generateCSRFToken();
?>

<div class="row justify-content-center mt-2 mb-4">
    <div class="col-lg-8">
        <div class="card border-0 shadow-sm" style="border-radius: 16px; border: 1px solid rgba(111, 94, 147, 0.18) !important;">
            <div class="card-body p-4 p-lg-5" style="background: linear-gradient(180deg, rgba(228, 224, 242, 0.72), #ffffff); border-radius: 16px;">
                <h1 class="h2 fw-bold mb-2 text-jacarta">Contact Us</h1>
                <p class="text-muted mb-4">Send us your question and we will get back to you as quickly as possible.</p>

                <?php echo $message; ?>

                <form action="contact.php" method="POST">
                    <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Full Name</label>
                            <input type="text" class="form-control" name="name" placeholder="Your name" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Email</label>
                            <input type="email" class="form-control" name="email" placeholder="name@example.com" required>
                        </div>
                        <div class="col-12">
                            <label class="form-label fw-semibold">Subject</label>
                            <input type="text" class="form-control" name="subject" placeholder="What can we help with?" required>
                        </div>
                        <div class="col-12">
                            <label class="form-label fw-semibold">Message</label>
                            <textarea class="form-control" name="details" rows="6" placeholder="Write your message" required></textarea>
                        </div>
                    </div>

                    <button type="submit" name="contact_btn" class="btn btn-velvet mt-4 px-4">Send Message</button>
                </form>
            </div>
        </div>
    </div>
</div>

<?php include 'footer.php'; ?>