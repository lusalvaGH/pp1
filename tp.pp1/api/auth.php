<?php
// ============================================================================
// API: Autenticación de Administrador (DeliStock)
// ============================================================================
declare(strict_types=1);
session_start();
header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/../conexion.php';

$method = $_SERVER['REQUEST_METHOD'];
$action = $_GET['action'] ?? $_POST['action'] ?? '';

$pdo = getDBConnection();

if ($action === 'check') {
    if (isset($_SESSION['admin'])) {
        echo json_encode([
            'authenticated' => true,
            'admin' => $_SESSION['admin']
        ]);
    } else {
        echo json_encode([
            'authenticated' => false
        ]);
    }
    exit;
}

if ($action === 'login' && $method === 'POST') {
    $rawInput = file_get_contents('php://input');
    $data = json_decode($rawInput, true) ?? $_POST;

    $usuario = trim((string)($data['usuario'] ?? ''));
    $contrasena = trim((string)($data['contrasena'] ?? ''));

    if ($usuario === '' || $contrasena === '') {
        echo json_encode([
            'success' => false,
            'message' => 'Por favor complete el usuario y la contraseña.'
        ]);
        exit;
    }

    $stmt = $pdo->prepare("SELECT idAdministrador, nombre, usuario, contrasena FROM administrador WHERE usuario = ? LIMIT 1");
    $stmt->execute([$usuario]);
    $admin = $stmt->fetch();

    if ($admin && ($admin['contrasena'] === $contrasena || password_verify($contrasena, $admin['contrasena']))) {
        $_SESSION['admin'] = [
            'idAdministrador' => (int)$admin['idAdministrador'],
            'nombre' => $admin['nombre'],
            'usuario' => $admin['usuario']
        ];

        echo json_encode([
            'success' => true,
            'message' => 'Bienvenido, ' . $admin['nombre'],
            'admin' => $_SESSION['admin']
        ]);
    } else {
        echo json_encode([
            'success' => false,
            'message' => 'Usuario o contraseña incorrectos.'
        ]);
    }
    exit;
}

if ($action === 'logout') {
    unset($_SESSION['admin']);
    session_destroy();
    echo json_encode([
        'success' => true,
        'message' => 'Sesión cerrada correctamente.'
    ]);
    exit;
}

echo json_encode([
    'success' => false,
    'message' => 'Acción no reconocida.'
]);
