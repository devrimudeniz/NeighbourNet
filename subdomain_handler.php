<?php
/**
 * Subdomain Handler
 * This file handles wildcard subdomain routing
 * Place this at the root and configure your server to route subdomains here
 */

require_once 'includes/db.php';

// Get the current host
$host = $_SERVER['HTTP_HOST'] ?? '';

// Extract subdomain
$parts = explode('.', $host);

// Check if it's a subdomain (not www or main domain)
if (count($parts) >= 3 && $parts[0] !== 'www') {
    $subdomain = strtolower($parts[0]);
    
    // Look up business by subdomain
    $stmt = $pdo->prepare("
        SELECT id FROM business_listings 
        WHERE subdomain = ? AND subdomain_status = 'approved'
    ");
    $stmt->execute([$subdomain]);
    $business = $stmt->fetch();
    
    if ($business) {
        // Redirect to menu page
        $_GET['id'] = $business['id'];
        require 'menu.php';
        exit();
    } else {
        // Subdomain not found
        http_response_code(404);
        ?>
        <!DOCTYPE html>
        <html lang="tr">
        <head>
            <meta charset="UTF-8">
            <meta name="viewport" content="width=device-width, initial-scale=1.0">
            <title>Subdomain Not Found</title>
            <script src="https://cdn.tailwindcss.com"></script>
        </head>
        <body class="bg-slate-50 min-h-screen flex items-center justify-center p-4">
            <div class="text-center max-w-md">
                <div class="text-6xl mb-4">🔍</div>
                <h1 class="text-3xl font-black text-slate-800 mb-2">Subdomain Not Found</h1>
                <p class="text-slate-500 mb-6">
                    The subdomain <strong><?php echo htmlspecialchars($subdomain); ?></strong> does not exist or is not yet approved.
                </p>
                <a href="https://kalkansocial.com" class="inline-block bg-violet-500 text-white px-6 py-3 rounded-xl font-bold hover:bg-violet-600 transition-colors">
                    Go to Kalkan Social
                </a>
            </div>
        </body>
        </html>
        <?php
        exit();
    }
}

// If not a subdomain, redirect to main site
header('Location: https://kalkansocial.com');
exit();
