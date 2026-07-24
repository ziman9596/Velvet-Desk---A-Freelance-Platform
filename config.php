<?php
// Initialize session if not already started
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Add security headers to prevent XSS, clickjacking, and other attacks
header("X-Content-Type-Options: nosniff");
header("X-Frame-Options: DENY");
header("X-XSS-Protection: 1; mode=block");
header("Content-Security-Policy: default-src 'self'; script-src 'self' 'unsafe-inline' https://cdn.jsdelivr.net; style-src 'self' 'unsafe-inline' https://cdn.jsdelivr.net; img-src 'self' https:");

// Database configuration and connection setup
$db_file = "velvetdesk_marketplace.db";

try {
    // Initialize PDO connection with error handling
    $conn = new PDO("sqlite:" . $db_file);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $conn->exec("PRAGMA foreign_keys = ON");
} catch (PDOException $e) {
    // Log error securely without exposing sensitive details
    error_log("Database connection failed: " . $e->getMessage());
    die("Database connection error. Please try again later.");
}

// Helper function: Generate CSRF token for form protection
function generateCSRFToken() {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

// Helper function: Validate CSRF token from form submission
function validateCSRFToken($token) {
    return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
}

function columnExists(PDO $conn, string $tableName, string $columnName): bool {
    $statement = $conn->query("PRAGMA table_info(" . $tableName . ")");
    while ($column = $statement->fetch(PDO::FETCH_ASSOC)) {
        if ($column['name'] === $columnName) {
            return true;
        }
    }
    return false;
}

function ensureDatabaseSchema(PDO $conn): void {
    $gigsColumns = [
        'category' => "TEXT DEFAULT ''",
        'subcategory' => "TEXT DEFAULT ''",
        'service_type' => "TEXT DEFAULT ''",
        'package_type' => "TEXT DEFAULT 'Standard'",
        'delivery_time_days' => "INTEGER DEFAULT 3",
        'revisions' => "INTEGER DEFAULT 2",
        'gig_tags' => "TEXT DEFAULT ''",
        'highlights' => "TEXT DEFAULT ''",
        'requirements' => "TEXT DEFAULT ''",
        'faq' => "TEXT DEFAULT ''",
        'includes_source_files' => "INTEGER DEFAULT 0",
        'video_link' => "TEXT DEFAULT ''",
    ];

    foreach ($gigsColumns as $columnName => $definition) {
        if (!columnExists($conn, 'gigs', $columnName)) {
            $conn->exec("ALTER TABLE gigs ADD COLUMN " . $columnName . " " . $definition);
        }
    }

    $conn->exec("CREATE TABLE IF NOT EXISTS order_messages (
        message_id INTEGER PRIMARY KEY AUTOINCREMENT,
        order_id INTEGER NOT NULL,
        sender_id INTEGER NOT NULL,
        message_text TEXT NOT NULL,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY(order_id) REFERENCES orders(order_id) ON DELETE CASCADE,
        FOREIGN KEY(sender_id) REFERENCES users(user_id) ON DELETE CASCADE
    )");

    $conn->exec("CREATE TABLE IF NOT EXISTS reviews (
        review_id INTEGER PRIMARY KEY AUTOINCREMENT,
        order_id INTEGER NOT NULL UNIQUE,
        reviewer_id INTEGER NOT NULL,
        seller_id INTEGER NOT NULL,
        rating INTEGER NOT NULL CHECK(rating BETWEEN 1 AND 5),
        review_text TEXT NOT NULL,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY(order_id) REFERENCES orders(order_id) ON DELETE CASCADE,
        FOREIGN KEY(reviewer_id) REFERENCES users(user_id) ON DELETE CASCADE,
        FOREIGN KEY(seller_id) REFERENCES users(user_id) ON DELETE CASCADE
    )");

    if (!columnExists($conn, 'orders', 'conversation_started_at')) {
        $conn->exec("ALTER TABLE orders ADD COLUMN conversation_started_at DATETIME DEFAULT NULL");
    }

    if (!columnExists($conn, 'orders', 'order_placed_at')) {
        $conn->exec("ALTER TABLE orders ADD COLUMN order_placed_at DATETIME DEFAULT NULL");
    }

    if (!columnExists($conn, 'orders', 'confirmed_at')) {
        $conn->exec("ALTER TABLE orders ADD COLUMN confirmed_at DATETIME DEFAULT NULL");
    }
}

function calculateFreelancerRating(float $averageReview, int $completedProjects): float {
    if ($completedProjects === 0) {
        return 0.0;
    }

    $baseRating = $averageReview > 0 ? $averageReview : 3.0;
    $projectBonus = min($completedProjects, 20) * 0.05;
    $rating = min(5, $baseRating + $projectBonus);
    return round($rating, 1);
}

ensureDatabaseSchema($conn);
?>