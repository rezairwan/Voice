<?php
require_once '../config/database.php';
require_once '../includes/auth.php';
requireLogin();
header('Content-Type: application/json');
$me = $_SESSION['user_id'];
$action = $_GET['action'] ?? '';

if ($_SERVER['REQUEST_METHOD']==='POST') {
    $data = json_decode(file_get_contents('php://input'), true);
    $action = $data['action'] ?? '';

    if ($action === 'initiate') {
        // End any previous calls
        $pdo->prepare("UPDATE call_signals SET status='ended' WHERE (caller_id=? OR receiver_id=?) AND status IN ('calling','active')")->execute([$me,$me]);
        $stmt = $pdo->prepare("INSERT INTO call_signals(caller_id,receiver_id,caller_peer_id,status) VALUES(?,?,?,'calling')");
        $stmt->execute([$me,$data['receiver_id'],$data['peer_id']]);
        echo json_encode(['ok'=>true,'signal_id'=>$pdo->lastInsertId()]); exit;
    }
    if ($action === 'accept') {
        $pdo->prepare("UPDATE call_signals SET status='active' WHERE id=?")->execute([$data['signal_id']]);
        echo json_encode(['ok'=>true]); exit;
    }
}

if ($action === 'check') {
    // Check if someone is calling ME
    $s = $pdo->prepare("SELECT cs.*, u.username AS caller_username FROM call_signals cs JOIN users u ON cs.caller_id=u.id WHERE cs.receiver_id=? AND cs.status='calling' ORDER BY cs.created_at DESC LIMIT 1");
    $s->execute([$me]);
    $call = $s->fetch();
    echo json_encode(['call' => $call ?: null]); exit;
}
if ($action === 'status') {
    $id = (int)($_GET['id'] ?? 0);
    $s = $pdo->prepare("SELECT status FROM call_signals WHERE id=?");
    $s->execute([$id]);
    $row = $s->fetch();
    echo json_encode(['status' => $row['status'] ?? 'ended']); exit;
}
if ($action === 'reject') {
    $id = (int)($_GET['id'] ?? 0);
    $pdo->prepare("UPDATE call_signals SET status='rejected' WHERE id=?")->execute([$id]);
    echo json_encode(['ok'=>true]); exit;
}
if ($action === 'end') {
    $id = (int)($_GET['id'] ?? 0);
    $pdo->prepare("UPDATE call_signals SET status='ended' WHERE id=?")->execute([$id]);
    echo json_encode(['ok'=>true]); exit;
}

echo json_encode(['error'=>'Unknown action']);
?>
