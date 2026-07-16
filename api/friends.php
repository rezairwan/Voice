<?php
require_once '../config/database.php';
require_once '../includes/auth.php';
requireLogin();
header('Content-Type: application/json');
$me = $_SESSION['user_id'];
$action = $_GET['action'] ?? 'list';

if ($action === 'list') {
    $friends = getFriends($pdo, $me);
    foreach ($friends as &$f) { $f['unread'] = getUnreadCount($pdo, $me, $f['id']); }
    echo json_encode($friends); exit;
}

if ($action === 'search') {
    $q = trim($_GET['q'] ?? '');
    $s = $pdo->prepare("SELECT id,username,avatar,status FROM users WHERE (username LIKE ? OR email LIKE ?) AND id!=? LIMIT 15");
    $s->execute(["%$q%","%$q%",$me]);
    $results = $s->fetchAll();
    foreach ($results as &$r) { $r['fs'] = getFriendshipStatus($pdo,$me,$r['id']); }
    echo json_encode($results); exit;
}

if ($action === 'add') {
    $tid = (int)($_GET['user_id'] ?? 0);
    try { $pdo->prepare("INSERT INTO friendships(requester_id,receiver_id) VALUES(?,?)")->execute([$me,$tid]); } catch(Exception $e){}
    echo json_encode(['ok'=>true]); exit;
}
if ($action === 'accept') {
    $tid = (int)($_GET['user_id'] ?? 0);
    $pdo->prepare("UPDATE friendships SET status='accepted' WHERE requester_id=? AND receiver_id=?")->execute([$tid,$me]);
    echo json_encode(['ok'=>true]); exit;
}
if ($action === 'reject') {
    $tid = (int)($_GET['user_id'] ?? 0);
    $pdo->prepare("UPDATE friendships SET status='rejected' WHERE requester_id=? AND receiver_id=?")->execute([$tid,$me]);
    echo json_encode(['ok'=>true]); exit;
}
if ($action === 'remove') {
    $tid = (int)($_GET['user_id'] ?? 0);
    $pdo->prepare("DELETE FROM friendships WHERE (requester_id=? AND receiver_id=?) OR (requester_id=? AND receiver_id=?)")->execute([$me,$tid,$tid,$me]);
    echo json_encode(['ok'=>true]); exit;
}

echo json_encode(['error'=>'Unknown action']);
?>
