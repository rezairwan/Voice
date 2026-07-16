<?php
require_once '../config/database.php';
require_once '../includes/auth.php';
requireLogin();
header('Content-Type: application/json');

$data = json_decode(file_get_contents('php://input'), true);
$id      = (int)($data['id']      ?? 0);
$content = trim($data['content']  ?? '');
$me      = $_SESSION['user_id'];

if (!$id || !$content || mb_strlen($content) > 1000) {
    echo json_encode(['ok' => false, 'error' => 'Invalid input']); exit;
}

// Only sender can edit their own message
$stmt = $pdo->prepare("SELECT sender_id FROM messages WHERE id=?");
$stmt->execute([$id]);
$msg = $stmt->fetch();

if (!$msg || (int)$msg['sender_id'] !== $me) {
    echo json_encode(['ok' => false, 'error' => 'Unauthorized']); exit;
}

$pdo->prepare("UPDATE messages SET content=?, is_edited=1 WHERE id=?")->execute([$content, $id]);
echo json_encode(['ok' => true]);
?>
