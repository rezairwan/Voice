<?php
require_once '../config/database.php';
require_once '../includes/auth.php';
requireLogin();
header('Content-Type: application/json');

$with  = (int)($_GET['with']  ?? 0);
$after = (int)($_GET['after'] ?? 0);
$me    = $_SESSION['user_id'];

$stmt = $pdo->prepare("
    SELECT m.*, u.username FROM messages m
    JOIN users u ON m.sender_id = u.id
    WHERE ((m.sender_id=? AND m.receiver_id=?) OR (m.sender_id=? AND m.receiver_id=?))
    AND m.id > ?
    ORDER BY m.created_at ASC LIMIT 50
");
$stmt->execute([$me,$with,$with,$me,$after]);
$messages = $stmt->fetchAll();

foreach ($messages as &$m) {
    $m['time'] = date('H:i', strtotime($m['created_at']));
}

// Mark as read
$pdo->prepare("UPDATE messages SET is_read=1 WHERE sender_id=? AND receiver_id=? AND is_read=0")
    ->execute([$with,$me]);

echo json_encode(['messages' => $messages]);
?>
