<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Content-Type');
header('Access-Control-Allow-Methods: POST, OPTIONS');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204);
    exit;
}

// CONFIGURAR: credenciales MySQL
$DB_HOST = 'localhost';
$DB_NAME = 'TU_DB';
$DB_USER = 'TU_USER';
$DB_PASS = 'TU_PASS';

$raw = file_get_contents('php://input');
if (!$raw) {
    http_response_code(400);
    echo json_encode(['ok'=>false,'error'=>'No body']);
    exit;
}

$body = json_decode($raw, true);
if (!$body) {
    http_response_code(400);
    echo json_encode(['ok'=>false,'error'=>'Bad JSON']);
    exit;
}

// Determinar si es login o guardar respuestas
$action = $body['action'] ?? '';

if ($action === 'login') {
    // AUTENTICACIÓN SEGURA
    $usuario = trim($body['usuario'] ?? '');
    $password = $body['password'] ?? '';

    if (!$usuario || !$password) {
        http_response_code(400);
        echo json_encode(['ok'=>false,'error'=>'Usuario y contraseña requeridos']);
        exit;
    }

    try {
        $pdo = new PDO("mysql:host=$DB_HOST;dbname=$DB_NAME;charset=utf8mb4", $DB_USER, $DB_PASS, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
        ]);

        $stmt = $pdo->prepare("SELECT password_hash FROM usuarios WHERE username = ?");
        $stmt->execute([$usuario]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($user && password_verify($password, $user['password_hash'])) {
            // Crear token de sesión simple
            $token = bin2hex(random_bytes(32));

            // Guardar token en base de datos
            $stmt = $pdo->prepare("UPDATE usuarios SET session_token = ?, token_expires = DATE_ADD(NOW(), INTERVAL 24 HOUR) WHERE username = ?");
            $stmt->execute([$token, $usuario]);

            echo json_encode(['ok'=>true, 'token'=>$token, 'usuario'=>$usuario]);
        } else {
            http_response_code(401);
            echo json_encode(['ok'=>false,'error'=>'Credenciales inválidas']);
        }

    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['ok'=>false,'error'=>$e->getMessage()]);
    }
    exit;
}

// Código para guardar respuestas - verificar token de sesión
$usuario = trim($body['usuario'] ?? '');
$token = $body['token'] ?? '';
$estado  = trim($body['estado'] ?? ''); // borrador | completo
$timestamp = $body['timestamp'] ?? date('c');
$respuestas = $body['respuestas'] ?? [];

if (!$usuario || !$token || !in_array($estado, ['borrador','completo'], true)) {
    http_response_code(400);
    echo json_encode(['ok'=>false,'error'=>'Datos inválidos o sesión expirada']);
    exit;
}

try {
    $pdo = new PDO("mysql:host=$DB_HOST;dbname=$DB_NAME;charset=utf8mb4", $DB_USER, $DB_PASS, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
    ]);

    // Verificar token de sesión
    $stmt = $pdo->prepare("SELECT username FROM usuarios WHERE username = ? AND session_token = ? AND token_expires > NOW()");
    $stmt->execute([$usuario, $token]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$user) {
        http_response_code(401);
        echo json_encode(['ok'=>false,'error'=>'Sesión inválida o expirada']);
        exit;
    }

    // Guardar respuestas
    $cols = ['usuario','estado','timestamp'];
    for ($i=1; $i<=41; $i++) $cols[] = "q$i";
    $placeholders = implode(',', array_fill(0, count($cols), '?'));
    $assignments  = implode(',', array_map(fn($c)=>"$c=VALUES($c)", $cols));
    $sql = "INSERT INTO respuestas (".implode(',',$cols).") VALUES ($placeholders) ON DUPLICATE KEY UPDATE $assignments";

    $vals = [$usuario, $estado, date('Y-m-d H:i:s', strtotime($timestamp))];
    for ($i=1; $i<=41; $i++) { $vals[] = $respuestas["q$i"] ?? ''; }

    $stmt = $pdo->prepare($sql);
    $stmt->execute($vals);
    echo json_encode(['ok'=>true]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['ok'=>false,'error'=>$e->getMessage()]);
}
?>
