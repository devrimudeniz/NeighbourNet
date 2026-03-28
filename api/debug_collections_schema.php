<?php
require_once '../includes/db.php';
try {
    $stmt = $pdo->query("SHOW CREATE TABLE collections");
    print_r($stmt->fetch(PDO::FETCH_ASSOC));
} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}
?>
