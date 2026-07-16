<?php
require_once '../config/database.php';
require_once '../includes/auth.php';
requireLogin();
header('Content-Type: application/json');
$me = $_SESSION['user_id'];
$action = $_GET['action'] ?? ($_POST['action'] ?? '');
$data = json_decode(file_get_contents('php://input'), true) ?? [];
$action = $action ?: ($data['action'] ?? '');

if ($action === 'list') {
    $s = $pdo->prepare("SELECT s.* FROM servers s JOIN server_members sm ON s.id=sm.server_id WHERE sm.user_id=? ORDER BY sm.joined_at ASC");
    $s->execute([$me]); echo json_encode($s->fetchAll()); exit;
}
if ($action === 'create') {
    $name = trim($data['name'] ?? '');
    if (!$name) { echo json_encode(['error'=>'Nama server wajib']); exit; }
    $code = substr(bin2hex(random_bytes(6)), 0, 8);
    $pdo->prepare("INSERT INTO servers(name,owner_id,invite_code) VALUES(?,?,?)")->execute([$name,$me,$code]);
    $sid = $pdo->lastInsertId();
    $pdo->prepare("INSERT INTO server_members(server_id,user_id,role) VALUES(?,?,'owner')")->execute([$sid,$me]);
    // Default channels
    $pdo->prepare("INSERT INTO channels(server_id,name,type,position) VALUES(?,?,?,?)")->execute([$sid,'umum','text',0]);
    $pdo->prepare("INSERT INTO channels(server_id,name,type,position) VALUES(?,?,?,?)")->execute([$sid,'Voice General','voice',1]);
    echo json_encode(['ok'=>true,'server_id'=>$sid,'invite_code'=>$code]); exit;
}
if ($action === 'join') {
    $code = trim($data['invite_code'] ?? '');
    $s = $pdo->prepare("SELECT * FROM servers WHERE invite_code=?"); $s->execute([$code]); $srv = $s->fetch();
    if (!$srv) { echo json_encode(['error'=>'Kode undangan tidak valid']); exit; }
    try { $pdo->prepare("INSERT INTO server_members(server_id,user_id) VALUES(?,?)")->execute([$srv['id'],$me]); } catch(Exception $e){}
    echo json_encode(['ok'=>true,'server_id'=>$srv['id']]); exit;
}
if ($action === 'get') {
    $sid = (int)($data['server_id'] ?? $_GET['server_id'] ?? 0);
    $s = $pdo->prepare("SELECT * FROM servers WHERE id=?"); $s->execute([$sid]); $srv = $s->fetch();
    $ch = $pdo->prepare("SELECT * FROM channels WHERE server_id=? ORDER BY position ASC"); $ch->execute([$sid]);
    $mem = $pdo->prepare("SELECT u.id,u.username,u.avatar,u.status,sm.role FROM server_members sm JOIN users u ON sm.user_id=u.id WHERE sm.server_id=?"); $mem->execute([$sid]);
    echo json_encode(['server'=>$srv,'channels'=>$ch->fetchAll(),'members'=>$mem->fetchAll()]); exit;
}
if ($action === 'create_channel') {
    $sid  = (int)($data['server_id'] ?? 0);
    $name = trim($data['name'] ?? '');
    $type = $data['type'] ?? 'text';
    // Check owner/admin
    $s = $pdo->prepare("SELECT role FROM server_members WHERE server_id=? AND user_id=?"); $s->execute([$sid,$me]); $r=$s->fetch();
    if (!$r || $r['role']==='member') { echo json_encode(['error'=>'Tidak ada izin']); exit; }
    $pdo->prepare("INSERT INTO channels(server_id,name,type) VALUES(?,?,?)")->execute([$sid,$name,$type]);
    echo json_encode(['ok'=>true,'channel_id'=>$pdo->lastInsertId()]); exit;
}
if ($action === 'leave') {
    $sid = (int)($data['server_id'] ?? 0);
    $s = $pdo->prepare("SELECT role FROM server_members WHERE server_id=? AND user_id=?"); $s->execute([$sid,$me]); $r=$s->fetch();
    if ($r && $r['role']==='owner') { $pdo->prepare("DELETE FROM servers WHERE id=?")->execute([$sid]); }
    else { $pdo->prepare("DELETE FROM server_members WHERE server_id=? AND user_id=?")->execute([$sid,$me]); }
    echo json_encode(['ok'=>true]); exit;
}
echo json_encode(['error'=>'Unknown action']);
?>
