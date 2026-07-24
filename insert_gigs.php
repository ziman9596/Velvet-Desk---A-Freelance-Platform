<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
ini_set('memory_limit', '512M'); // Higher threshold buffer for safe processing
error_reporting(E_ALL);

include 'config.php';

try {
    // ---------------------------------------------------------
    // DROP & RE-INJECT SYSTEM TO START FROM CLEAN SHEET DATA
    // ---------------------------------------------------------
    $conn->exec("DELETE FROM reviews");
    $conn->exec("DELETE FROM order_messages");
    $conn->exec("DELETE FROM orders");
    $conn->exec("DELETE FROM gigs");
    $conn->exec("DELETE FROM users");

    echo "<h3>🚀 VelvetDesk Heavy Scaling Seeding Sequence Initialized</h3>";
    echo "<p style='color: orange;'>➔ Flushing conflict tables cache logs... Clean sheet state active.</p>";

    // ---------------------------------------------------------
    // 1. GENERATE 20 FREELANCERS & 30 CUSTOMERS (50 USERS TOTAL)
    // ---------------------------------------------------------
    // Hash 'test1234' so login.php's password_verify() works properly
    $hashed_password = password_hash("test1234", PASSWORD_BCRYPT);
    $freelancer_ids = [];
    $customer_ids = [];

    // Names list seeds for dynamic distribution matching realism
    $dev_names = ['iman_creative', 'natalia_codes', 'zain_dev', 'ayesha_ui', 'hamza_stack', 'sana_brander', 'bilal_sqlite', 'hira_pixels', 'umar_php', 'fiza_vector', 'ali_wireframe', 'mahnoor_seo', 'saad_js', 'laiba_crafts', 'zohaib_data', 'kinza_logos', 'ahmed_api', 'rida_interfaces', 'fahad_backend', 'mehak_mockups'];
    
    $buyer_names = ['john_doe', 'alpha_corp', 'nexus_tech', 'vertex_studios', 'sarah_connor', 'pixel_ventures', 'delta_goods', 'omega_builders', 'sam_wilson', 'innovate_lab', 'bruce_w', 'clark_k', 'diana_p', 'tony_stark', 'peter_p', 'selina_k', 'barry_a', 'hal_j', 'arthur_c', 'wanda_m', 'steve_r', 'natasha_r', 'clint_b', 'bucky_b', 'sam_p', 'harley_q', 'lex_l', 'harvey_d', 'gordon_j', 'rachel_g'];

    // Inject 20 Freelancers
    $user_ins = $conn->prepare("INSERT INTO users (username, email, password, role) VALUES (:name, :email, :pass, :role)");
    foreach ($dev_names as $index => $name) {
        $email = $name . "@velvetdesk.com";
        $user_ins->execute([':name' => $name, ':email' => $email, ':pass' => $hashed_password, ':role' => 'seller']);
        $freelancer_ids[] = $conn->lastInsertId();
    }

    // Inject 30 Buyers/Customers
    foreach ($buyer_names as $index => $name) {
        $email = $name . "@clientmail.com";
        $user_ins->execute([':name' => $name, ':email' => $email, ':pass' => $hashed_password, ':role' => 'buyer']);
        $customer_ids[] = $conn->lastInsertId();
    }
    echo "<p style='color: blue;'>✅ Phase 1: 50 User Nodes successfully locked in structural database paths (20 Sellers / 30 Buyers).</p>";

    // ---------------------------------------------------------
    // 2. GENERATE 20 FAKE GIGS (1 FOR EACH FREELANCER WITH PIC LINKS)
    // ---------------------------------------------------------
    // 20 High Quality targeted Unsplash tech/design placeholder links
    $images_stock = [
        'https://images.unsplash.com/photo-1626785774573-4b799315345d?w=500&auto=format&fit=crop&q=60',
        'https://images.unsplash.com/photo-1555066931-4365d14bab8c?w=500&auto=format&fit=crop&q=60',
        'https://images.unsplash.com/photo-1507238691740-187a5b1d37b8?w=500&auto=format&fit=crop&q=60',
        'https://images.unsplash.com/photo-1531403009284-440f080d1e12?w=500&auto=format&fit=crop&q=60',
        'https://images.unsplash.com/photo-1542831371-29b0f74f9713?w=500&auto=format&fit=crop&q=60',
        'https://images.unsplash.com/photo-1581291518633-83b4ebd1d83e?w=500&auto=format&fit=crop&q=60',
        'https://images.unsplash.com/photo-1517694712202-14dd9538aa97?w=500&auto=format&fit=crop&q=60',
        'https://images.unsplash.com/photo-1586717791821-3f44a563fa4c?w=500&auto=format&fit=crop&q=60',
        'https://images.unsplash.com/photo-1618005182384-a83a8bd57fbe?w=500&auto=format&fit=crop&q=60',
        'https://images.unsplash.com/photo-1522542550221-31fd19575a2d?w=500&auto=format&fit=crop&q=60',
        'https://images.unsplash.com/photo-1600132806370-bf17e65e942f?w=500&auto=format&fit=crop&q=60',
        'https://images.unsplash.com/photo-1460925895917-afdab827c52f?w=500&auto=format&fit=crop&q=60',
        'https://images.unsplash.com/photo-1504868584819-f8e8b4b6d7e3?w=500&auto=format&fit=crop&q=60',
        'https://images.unsplash.com/photo-1516321318423-f06f85e504b3?w=500&auto=format&fit=crop&q=60',
        'https://images.unsplash.com/photo-1614741118887-7a4ee193a5fa?w=500&auto=format&fit=crop&q=60',
        'https://images.unsplash.com/photo-1572044160444-ad600027b080?w=500&auto=format&fit=crop&q=60',
        'https://images.unsplash.com/photo-1551288049-bebda4e38f71?w=500&auto=format&fit=crop&q=60',
        'https://images.unsplash.com/photo-1508921912186-1d1a45ebb3c1?w=500&auto=format&fit=crop&q=60',
        'https://images.unsplash.com/photo-1629654297299-c8506221ca97?w=500&auto=format&fit=crop&q=60',
        'https://images.unsplash.com/photo-1541462608141-ad4979e408c9?w=500&auto=format&fit=crop&q=60'
    ];

    $gig_titles = [
        "Corporate Brand Design Systems", "PHP Core SQLite Application Engine", "Interactive Mobile Figma Prototypes",
        "Full-Stack Responsive Layout System", "API Infrastructure Management Logic", "SEO Content Strategies Engineering",
        "Custom SQL Enterprise Schemes", "Vector Graphic Illustration Masterclass", "E-Commerce Payment Pipeline Build",
        "Minimalist Web Wireframes Console", "Cybersecurity Source Integrity Checking", "Analytical Operations Dashboard Sheet",
        "React Interface Functional Components", "Tailwind Component Design Layouts", "Automated Script Flow Controllers",
        "Branded Digital Media Pitch Assets", "Cloud Server Schema Provisioning", "Creative Copywriting Campaign Tokens",
        "Microservice Routing Controller Node", "Advanced UI Typography Blueprint"
    ];

    $gig_ins = $conn->prepare("INSERT INTO gigs (user_id, title, description, price, image_path, category, subcategory, package_type) VALUES (:uid, :title, :desc, :price, :img, :cat, :sub, :pkg)");
    
    $gig_mapping = []; // Freelancer ID => Gig ID tracking link map
    
    for($i = 0; $i < 20; $i++) {
        $price = rand(5, 45) * 10; // Random pricing parameters
        $desc = "Expert level professional execution regarding your targeted " . $gig_titles[$i] . " operational needs. We deploy semantic clean files structural variables, modular logic parsing sheets, and unlimited iterative reviews cycle support variables to map your exact requirements.";
        
        $cat = ($i % 2 == 0) ? "Graphics & Design" : "Programming & Tech";
        $sub = ($i % 2 == 0) ? "UI/UX Modeling" : "Web Development Engine";

        $params = [
            ':uid' => $freelancer_ids[$i],
            ':title' => $gig_titles[$i],
            ':desc' => $desc,
            ':price' => $price,
            ':img' => $images_stock[$i],
            ':cat' => $cat,
            ':sub' => $sub,
            ':pkg' => 'Premium Suite Execution'
        ];
        $gig_ins->execute($params);
        $gig_mapping[$freelancer_ids[$i]] = $conn->lastInsertId();
    }
    echo "<p style='color: blue;'>✅ Phase 2: 20 Personalized Gigs mapped dynamically across all freelancer nodes indexes.</p>";

    // ---------------------------------------------------------
    // 3. GENERATE 60 BALANCED LIFE-CYCLE ORDERS
    // ---------------------------------------------------------
    $order_ins = $conn->prepare("INSERT INTO orders (gig_id, buyer_id, seller_id, order_status, delivery_file_path, order_placed_at, confirmed_at) VALUES (:gid, :bid, :sid, :status, :file, :placed, :confirmed)");
    
    $inserted_order_ids = [];
    $order_counter = 0;

    // A. Create 30 COMPLETED Orders
    for($i = 0; $i < 30; $i++) {
        $sid = $freelancer_ids[$i % 20];
        $bid = $customer_ids[$i % 30];
        $gid = $gig_mapping[$sid];

        $order_ins->execute([
            ':gid' => $gid, ':bid' => $bid, ':sid' => $sid,
            ':status' => 'Completed',
            ':file' => 'assets/deliveries/secure_production_payload_archive_' . $i . '.zip',
            ':placed' => '2026-07-01 12:00:00',
            ':confirmed' => '2026-07-06 18:25:00'
        ]);
        $inserted_order_ids[] = [
            'id' => $conn->lastInsertId(),
            'type' => 'Completed',
            'buyer' => $bid,
            'seller' => $sid
        ];
    }

    // B. Create 15 IN PROGRESS Orders (Will receive fake chats)
    for($i = 0; $i < 15; $i++) {
        $sid = $freelancer_ids[($i + 5) % 20];
        $bid = $customer_ids[($i + 10) % 30];
        $gid = $gig_mapping[$sid];

        $order_ins->execute([
            ':gid' => $gid, ':bid' => $bid, ':sid' => $sid,
            ':status' => 'In Progress',
            ':file' => null,
            ':placed' => '2026-07-15 09:15:00',
            ':confirmed' => null
        ]);
        $inserted_order_ids[] = [
            'id' => $conn->lastInsertId(),
            'type' => 'In Progress',
            'buyer' => $bid,
            'seller' => $sid
        ];
    }

    // C. Create 15 INITIALIZED Orders (Conversation Started, NO CHATS)
    for($i = 0; $i < 15; $i++) {
        $sid = $freelancer_ids[($i + 12) % 20];
        $bid = $customer_ids[($i + 18) % 30];
        $gid = $gig_mapping[$sid];

        $order_ins->execute([
            ':gid' => $gid, ':bid' => $bid, ':sid' => $sid,
            ':status' => 'Conversation Started',
            ':file' => null,
            ':placed' => null,
            ':confirmed' => null
        ]);
    }
    echo "<p style='color: blue;'>✅ Phase 3: 60 Lifecycle Contracts distributed perfectly (30 Completed / 15 Thread Active / 15 Initialized Static).</p>";

    // ---------------------------------------------------------
    // 4. INJECT THREADED MESSAGES IN THE 15 IN-PROGRESS ORDERS
    // ---------------------------------------------------------
    $msg_ins = $conn->prepare("INSERT INTO order_messages (order_id, sender_id, message_text) VALUES (:oid, :sender, :text)");
    
    $chat_templates = [
        ["Hi there! I initiated the contract parameter. Can you update me regarding design frameworks timelines?", "Hello client! Yes, system wires are mapped out. I am currently assembling the vector design tokens."],
        ["Hey! The SQL table parameters need to scale securely. Ensure foreign constraints match.", "Understood! Parameterized tracking is set. No SQL injections will penetrate our execution schema."],
        ["Project workspace brief documents attached. Let me know if revisions quota allows file adjustments.", "Perfect, data sheets analyzed. We will process your updates securely inside the current revision cycle."],
        ["Can you speed up the prototype handoff? We have an index matrix sync in 48 hours.", "Roger that. Working explicitly on responsive alignment loops now. Expect asset deployment tomorrow evening."],
        ["Excellent communication flow so far. Are source code templates packed in the final tier?", "Yes, absolutely! The asset archive bundle zip container will house fully editable development files."]
    ];

    $chat_index = 0;
    foreach($inserted_order_ids as $ord) {
        if($ord['type'] === 'In Progress') {
            $tpl = $chat_templates[$chat_index % 5];
            
            // Client Message
            $msg_ins->execute([':oid' => $ord['id'], ':sender' => $ord['buyer'], ':text' => $tpl[0]]);
            // Seller Message Response
            $msg_ins->execute([':oid' => $ord['id'], ':sender' => $ord['seller'], ':text' => $tpl[1]]);
            
            $chat_index++;
        }
    }
    echo "<p style='color: blue;'>✅ Phase 4: Threaded chat communication matrix populated across active tracking pipelines.</p>";

    // ---------------------------------------------------------
    // 5. INJECT REVIEWS FEEDBACK LINK FOR ALL 30 COMPLETED ORDERS
    // ---------------------------------------------------------
    $rev_ins = $conn->prepare("INSERT INTO reviews (order_id, reviewer_id, seller_id, rating, review_text) VALUES (:oid, :reviewer, :seller, :rating, :text)");
    
    $review_texts = [
        "Aala standard development! Code architecture variables are exceptionally structured.",
        "Outstanding layout delivery model. Communication response latency was amazingly fast.",
        "Beautiful vector aesthetics mapping. Revisions execution loop matched my exact inputs parameters.",
        "Relational schema indexing was flawlessly fast. Highly recommended developer interface.",
        "Brilliant engineering. Completely satisfied with the platform deliverable assets secure delivery."
    ];

    $rev_index = 0;
    foreach($inserted_order_ids as $ord) {
        if($ord['type'] === 'Completed') {
            $text = $review_texts[$rev_index % 5];
            $rating = rand(4, 5);

            $rev_ins->execute([
                ':oid' => $ord['id'],
                ':reviewer' => $ord['buyer'],
                ':seller' => $ord['seller'],
                ':rating' => $rating,
                ':text' => $text
            ]);
            $rev_index++;
        }
    }
    echo "<p style='color: blue;'>✅ Phase 5: 30 Verification feedback loops registered into review logs archive.</p>";

    echo "<h3 style='color: green;'>✨ VelvetDesk Scale Verification Data Successfully Injected!</h3>";
    echo "<p>Browse the interface dashboards now. Every element is populated with realistic structural assets rows!</p>";

} catch (PDOException $e) {
    die("<p style='color: red; font-weight: bold;'>Critical Framework Exception Terminated: " . htmlspecialchars($e->getMessage()) . "</p>");
}
?>