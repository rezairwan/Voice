<?php
require_once 'config/database.php';
require_once 'includes/auth.php';
if (isLoggedIn()) {
    $pdo->prepare("UPDATE users SET status='offline', last_seen=NOW() WHERE id=?")->execute([$_SESSION['user_id']]);
    session_destroy();
}
header('Location: login.php'); exit;
?>
