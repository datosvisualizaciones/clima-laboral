<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Content-Type');
header('Access-Control-Allow-Methods: POST, OPTIONS');
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { http_response_code(204); exit; }

// CONFIGURAR: credenciales MySQL
$DB_HOST = 'localhost';
$DB_NAME = 'TU_DB';
$DB_USER = 'TU_USER';
$DB_PASS = 'TU_PASS';

$raw = file_get_contents('php://input');
if (!$raw) { http_response_code(400); echo json_encode(['ok'=>false,'error'=>'No body']); exit; }
$body = json_decode($raw, true);
if (!$body) { http_response_code(400); echo json_encode(['ok'=>false,'error'=>'Bad JSON']); exit; }

$usuario = trim($body['usuario'] ?? '');
$estado  = trim($body['estado'] ?? ''); // borrador | completo
$timestamp = $body['timestamp'] ?? date('c');
$respuestas = $body['respuestas'] ?? [];

if (!$usuario || !in_array($estado, ['borrador','completo'], true)) {
  http_response_code(400); echo json_encode(['ok'=>false,'error'=>'usuario/estado inválidos']); exit; }

try {
  $pdo = new PDO("mysql:host=$DB_HOST;dbname=$DB_NAME;charset=utf8mb4", $DB_USER, $DB_PASS, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
  $cols = ['usuario','estado','timestamp'];
  for ($i=1; $i<=29; $i++) $cols[] = "q$i";
  $placeholders = implode(',', array_fill(0, count($cols), '?'));
  $assignments  = implode(',', array_map(fn($c)=>"$c=VALUES($c)", $cols));
  $sql = "INSERT INTO respuestas (".implode(',',$cols).") VALUES ($placeholders) ON DUPLICATE KEY UPDATE $assignments";

  $vals = [$usuario, $estado, date('Y-m-d H:i:s', strtotime($timestamp))];
  for ($i=1; $i<=29; $i++) { $vals[] = $respuestas["q$i"] ?? ''; }

  $stmt = $pdo->prepare($sql);
  $stmt->execute($vals);
  echo json_encode(['ok'=>true]);
} catch (Exception $e) {
  http_response_code(500); echo json_encode(['ok'=>false,'error'=>$e->getMessage()]); }
?>
