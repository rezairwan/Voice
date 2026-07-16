<?php
require_once '../config/database.php';
require_once '../includes/auth.php';
requireLogin();
header('Content-Type: application/json');
$me = $_SESSION['user_id'];
$action = $_GET['action'] ?? '';
$data = json_decode(file_get_contents('php://input'), true) ?? [];

if ($action === 'messages') {
    $cid   = (int)($_GET['channel_id'] ?? 0);
    $after = (int)($_GET['after'] ?? 0);
    $s = $pdo->prepare("SELECT cm.*,u.username,u.avatar FROM channel_messages cm JOIN users u ON cm.sender_id=u.id WHERE cm.channel_id=? AND cm.id>? ORDER BY cm.created_at ASC LIMIT 80");
    $s->execute([$cid,$after]);
    $msgs = $s->fetchAll();
    foreach ($msgs as &$m) $m['time'] = date('H:i', strtotime($m['created_at']));
    echo json_encode(['messages'=>$msgs]); exit;
}
if ($action === 'send') {
    $cid     = (int)($data['channel_id'] ?? 0);
    $content = trim($data['content'] ?? '');
    if (!$cid || !$content) { echo json_encode(['ok'=>false]); exit; }
    $pdo->prepare("INSERT INTO channel_messages(channel_id,sender_id,content) VALUES(?,?,?)")->execute([$cid,$me,$content]);
    echo json_encode(['ok'=>true,'id'=>$pdo->lastInsertId()]); exit;
}
// Voice participants
if ($action === 'join_voice') {
    $cid    = (int)($data['channel_id'] ?? 0);
    $peerId = $data['peer_id'] ?? '';
    $pdo->prepare("INSERT INTO voice_participants(channel_id,user_id,peer_id) VALUES(?,?,?) ON DUPLICATE KEY UPDATE peer_id=?,joined_at=NOW()")->execute([$cid,$me,$peerId,$peerId]);
    // Return existing participants
    $s = $pdo->prepare("SELECT vp.*,u.username,u.avatar FROM voice_participants vp JOIN users u ON vp.user_id=u.id WHERE vp.channel_id=? AND vp.user_id!=? AND vp.joined_at > NOW()-INTERVAL 30 SECOND");
    $s->execute([$cid,$me]);
    echo json_encode(['ok'=>true,'participants'=>$s->fetchAll()]); exit;
}
if ($action === 'leave_voice') {
    $cid = (int)($data['channel_id'] ?? 0);
    $pdo->prepare("DELETE FROM voice_participants WHERE channel_id=? AND user_id=?")->execute([$cid,$me]);
    echo json_encode(['ok'=>true]); exit;
}
if ($action === 'voice_peers') {
    $cid = (int)($_GET['channel_id'] ?? 0);
    $s = $pdo->prepare("SELECT vp.*,u.username FROM voice_participants vp JOIN users u ON vp.user_id=u.id WHERE vp.channel_id=? AND vp.user_id!=? AND vp.joined_at > NOW()-INTERVAL 30 SECOND");
    $s->execute([$cid,$me]);
    echo json_encode($s->fetchAll()); exit;
}
echo json_encode(['error'=>'Unknown']);
?>
