<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);

function shutdown() {
    $output = ob_get_contents();
    ob_end_clean();
    echo "--- CAPTURED OUTPUT ---\n";
    echo $output;
    echo "\n--- END CAPTURED OUTPUT ---\n";
}
register_shutdown_function('shutdown');

ob_start();

// Mock Session
// We define $_SESSION before including, but we don't call session_start() because chat_fetch.php does.
// However, in CLI, cookies aren't passed, so chat_fetch.php might start a NEW empty session.
// We need to inject user_id into that session.
// Override session_start to be a no-op? No, can't in PHP userland easily without runkit.
// Instead, let's just let it start, then inject.
// But we can't inject *after* the check if the check is:
// session_start(); if(!isset($_SESSION...)) exit;
// 
// So we MUST start session before.
ob_start(); // Buffer the potential "Session already started" notice
session_start();
$_SESSION['user_id'] = 1;
$_SESSION['username'] = 'TestUser';
ob_end_clean(); // Discard the notice from our start if any

$_GET['partner_id'] = 2;
$_GET['after_id'] = 0;

require 'chat_fetch.php';
?>
