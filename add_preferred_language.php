<?php
/**
 * Migration: Add preferred_language column to users table
 */
require_once 'includes/db.php';

try {
    // Check if column exists
    $check = $pdo->query("SHOW COLUMNS FROM users LIKE 'preferred_language'");
    
    if ($check->rowCount() == 0) {
        // Add column
        $pdo->exec("ALTER TABLE users ADD COLUMN preferred_language VARCHAR(5) DEFAULT 'tr' AFTER email");
        echo "✅ preferred_language column added successfully!\n";
    } else {
        echo "ℹ️  preferred_language column already exists.\n";
    }
    
    // Set default language for existing users without preference
    $pdo->exec("UPDATE users SET preferred_language = 'tr' WHERE preferred_language IS NULL OR preferred_language = ''");
    echo "✅ Default language set for existing users.\n";
    
    echo "\n🎉 Migration completed successfully!\n";
    
} catch (PDOException $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
}
?>
