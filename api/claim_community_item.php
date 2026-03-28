<?php
require_once '../includes/db.php';
session_start();

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'error' => 'Invalid method']);
    exit();
}

$id = (int)($_POST['id'] ?? 0);

if ($id <= 0) {
    echo json_encode(['success' => false, 'error' => 'Invalid ID']);
    exit();
}

try {
    // Transaction to safely decrease quantity
    $pdo->beginTransaction();

    // Check current quantity
    $stmt = $pdo->prepare("SELECT quantity FROM community_items WHERE id = ? FOR UPDATE");
    $stmt->execute([$id]);
    $item = $stmt->fetch();

    if (!$item || $item['quantity'] <= 0) {
        $pdo->rollBack();
        echo json_encode(['success' => false, 'error' => 'Item not available']);
        exit();
    }

    // Decrease quantity
    $new_qty = $item['quantity'] - 1;
    $pdo->prepare("UPDATE community_items SET quantity = ? WHERE id = ?")->execute([$new_qty, $id]);

    $pdo->commit();
    echo json_encode(['success' => true, 'remaining' => $new_qty]);

} catch (PDOException $e) {
    $pdo->rollBack();
    echo json_encode(['success' => false, 'error' => 'DB Error']);
}
?>
