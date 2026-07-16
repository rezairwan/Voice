<?php
session_start();

function isLoggedIn() { return isset($_SESSION['user_id']); }

function requireLogin() {
    if (!isLoggedIn()) { header('Location: /login.php'); exit; }
}

function getCurrentUser($pdo) {
    if (!isLoggedIn()) return null;
    $s = $pdo->prepare("SELECT * FROM users WHERE id = ?");
    $s->execute([$_SESSION['user_id']]);
    return $s->fetch();
}

function updateOnlineStatus($pdo, $userId, $status = 'online') {
    $pdo->prepare("UPDATE users SET status=?, last_seen=NOW() WHERE id=?")
        ->execute([$status, $userId]);
}

function getFriendshipStatus($pdo, $userId, $otherId) {
    $s = $pdo->prepare("SELECT * FROM friendships WHERE (requester_id=? AND receiver_id=?) OR (requester_id=? AND receiver_id=?) LIMIT 1");
    $s->execute([$userId, $otherId, $otherId, $userId]);
    return $s->fetch();
}

function getFriends($pdo, $userId) {
    $s = $pdo->prepare("
        SELECT u.*, f.created_at as friend_since
        FROM friendships f
        JOIN users u ON (CASE WHEN f.requester_id=? THEN f.receiver_id ELSE f.requester_id END = u.id)
        WHERE (f.requester_id=? OR f.receiver_id=?) AND f.status='accepted'
        ORDER BY u.status DESC, u.username ASC
    ");
    $s->execute([$userId, $userId, $userId]);
    return $s->fetchAll();
}

function getUnreadCount($pdo, $userId, $fromId) {
    $s = $pdo->prepare("SELECT COUNT(*) FROM messages WHERE sender_id=? AND receiver_id=? AND is_read=0");
    $s->execute([$fromId, $userId]);
    return $s->fetchColumn();
}

function timeAgo($datetime) {
    $diff = time() - strtotime($datetime);
    if ($diff < 60)    return 'baru saja';
    if ($diff < 3600)  return floor($diff/60).' mnt lalu';
    if ($diff < 86400) return floor($diff/3600).' jam lalu';
    return date('d M', strtotime($datetime));
}
?>
