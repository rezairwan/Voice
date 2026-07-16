<?php
$host     = getenv('MYSQLHOST')     ?: 'localhost';
$port     = getenv('MYSQLPORT')     ?: '3306';
$dbname   = getenv('MYSQLDATABASE') ?: 'kaivc_db';
$username = getenv('MYSQLUSER')     ?: 'root';
$password = getenv('MYSQLPASSWORD') ?: '';

$mysqlUrl = getenv('MYSQL_URL') ?: null;
if ($mysqlUrl) {
    $p = parse_url($mysqlUrl);
    $host = $p['host']; $port = $p['port'] ?? 3306;
    $dbname = ltrim($p['path'] ?? '/kaivc_db', '/');
    $username = $p['user'] ?? 'root';
    $password = $p['pass'] ?? '';
}

try {
    $pdo = new PDO("mysql:host=$host;port=$port;dbname=$dbname;charset=utf8mb4", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    die(json_encode(['error' => $e->getMessage()]));
}
?>
