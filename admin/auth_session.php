<?php
session_start();

// Check if user is logged in AND has required role
$allowed_roles = ['admin', 'founder', 'moderator'];

if (!isset($_SESSION['user_id']) || !isset($_SESSION['role']) || !in_array($_SESSION['role'], $allowed_roles)) {
    // Redirect EVERYONE else to 403 Forbidden
    header("Location: ../403.php");
    exit();
}
?>
