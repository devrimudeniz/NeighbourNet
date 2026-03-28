<?php
session_start();
require_once 'includes/db.php';
require_once 'includes/auth_helper.php';

// Clear remember token from database and cookie
if (isset($_SESSION['user_id'])) {
    clearRememberToken($pdo, $_SESSION['user_id']);
}

// Clear session
session_unset();
session_destroy();

header("Location: index");
exit();
