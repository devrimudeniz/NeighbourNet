<?php
require_once '../includes/db.php';

echo "Checking User Avatars:\n";
$stmt = $pdo->query("SELECT id, username, avatar FROM users WHERE avatar LIKE 'admin/%' OR avatar LIKE 'admin/uploads/%'");
$count = 0;
while ($row = $stmt->fetch()) {
    echo "User ID {$row['id']} ({$row['username']}): {$row['avatar']}\n";
    $count++;
}
echo "Found $count incorrect user avatars.\n\n";

echo "Checking User Cat Photos:\n";
$stmt = $pdo->query("SELECT id, user_photo FROM user_cat_collection WHERE user_photo LIKE 'admin/%'");
$count = 0;
while ($row = $stmt->fetch()) {
    echo "Collection ID {$row['id']}: {$row['user_photo']}\n";
    $count++;
}
echo "Found $count incorrect cat photos.\n";
?>
