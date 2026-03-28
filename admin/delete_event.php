<?php
require_once 'auth_session.php';
require_once '../includes/db.php';

if (isset($_GET['id'])) {
    // Double check role - security measure
    if (!isset($_SESSION['role']) || ($_SESSION['role'] != 'venue' && $_SESSION['role'] != 'admin')) {
        header("Location: ../index");
        exit();
    }
    
    $id = $_GET['id'];
    $user_id = $_SESSION['user_id'];

    // Security check: Only delete if user owns the event
    $stmt = $pdo->prepare("DELETE FROM events WHERE id = ? AND user_id = ?");
    $stmt->execute([$id, $user_id]);
}

header("Location: index");
exit();
?>
