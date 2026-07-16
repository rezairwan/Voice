<?php
require_once '../config/database.php';
require_once '../includes/auth.php';
requireLogin();
header('Content-Type: application/json');
$data = json_decode(file_get_contents('php://input'), true);
$content = trim($data['content'] ?? '');
$receiverId = (int)($data['receiver_id'] ?? 0);
$me = $_SESSION['user_id'];
if (!$content || !$receiverId) { echo json_encode(['ok'=>false]); exit; }
$pdo->prepare("INSERT INTO messages(sender_id,receiver_id,content) VALUES(?,?,?)")->execute([$me,$receiverId,$content]);
echo json_encode(['ok'=>true,'id'=>$pdo->lastInsertId()]);
?>
