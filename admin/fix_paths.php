<?php
require_once '../includes/db.php';

echo "<h1>Path Fix Tool</h1>";

$tables = [
    'users' => 'avatar',
    'user_cat_collection' => 'user_photo'
];

foreach ($tables as $table => $column) {
    echo "<h2>Processing $table ($column)</h2>";
    
    // Find incorrect paths
    $stmt = $pdo->prepare("SELECT id, $column FROM $table WHERE $column LIKE 'admin/%'");
    $stmt->execute();
    $rows = $stmt->fetchAll();
    
    if (empty($rows)) {
        echo "<p>No incorrect paths found for $table.</p>";
        continue;
    }
    
    echo "<ul>";
    foreach ($rows as $row) {
        $old_path = $row[$column];
        // Strip 'admin/' from the beginning
        $new_path = preg_replace('/^admin\//', '', $old_path);
        
        $update = $pdo->prepare("UPDATE $table SET $column = ? WHERE id = ?");
        if ($update->execute([$new_path, $row['id']])) {
            echo "<li>Updated ID {$row['id']}: <del>$old_path</del> &rarr; <strong>$new_path</strong></li>";
        } else {
            echo "<li><span style='color:red'>Failed to update ID {$row['id']}</span></li>";
        }
    }
    echo "</ul>";
}

echo "<hr><p>Done.</p>";
?>
