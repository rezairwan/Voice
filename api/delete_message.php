<?php
require_once '../config/database.php';
require_once '../includes/auth.php';
requireLogin();
header('Content-Type: application/json');

$data = json_decode(file_get_contents('php://input'), true);
$id   = (int)($data['id'] ?? 0);
$me   = $_SESSION['user_id'];

if (!$id) {
    echo json_encode(['ok' => false, 'error' => 'Invalid ID']); exit;
}

// Only sender can delete their own message
$stmt = $pdo->prepare("SELECT sender_id FROM messages WHERE id=?");
$stmt->execute([$id]);
$msg = $stmt->fetch();

if (!$msg || (int)$msg['sender_id'] !== $me) {
    echo json_encode(['ok' => false, 'error' => 'Unauthorized']); exit;
}

$pdo->prepare("DELETE FROM messages WHERE id=?")->execute([$id]);
echo json_encode(['ok' => true]);
?>
