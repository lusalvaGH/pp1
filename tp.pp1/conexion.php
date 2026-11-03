<?php
// ============================================================================
// CONEXIÓN A BASE DE DATOS - DeliStock
// ============================================================================
// Compatible automáticamente con XAMPP estándar (sin pass) y MySQL local ('1234')
// ============================================================================

function getDBConnection(): PDO {
    static $pdo = null;
    if ($pdo !== null) {
        return $pdo;
    }

    $host = '127.0.0.1';
    $port = 3306;
    $dbName = 'delistock';
    $user = 'root';
    $passwordsToTry = ['', '1234', 'root', 'admin'];

    $lastError = null;
    $connectedServer = null;

    // 1. Intentar conectar al servidor MySQL
    foreach ($passwordsToTry as $pass) {
        try {
            $testPdo = new PDO("mysql:host=$host;port=$port;charset=utf8mb4", $user, $pass, [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false
            ]);
            $connectedServer = ['pdo' => $testPdo, 'pass' => $pass];
            break;
        } catch (PDOException $e) {
            $lastError = $e->getMessage();
        }
    }

    if (!$connectedServer) {
        http_response_code(500);
        die(json_encode([
            'success' => false,
            'message' => 'No se pudo conectar al servidor MySQL en ' . $host . ':' . $port . '. Verifique que MySQL esté iniciado en XAMPP. Detalle: ' . $lastError
        ]));
    }

    // 2. Asegurarse de que la base de datos existe y tiene las tablas
    try {
        $serverPdo = $connectedServer['pdo'];
        $serverPdo->exec("CREATE DATABASE IF NOT EXISTS `$dbName` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
        
        $pdo = new PDO("mysql:host=$host;port=$port;dbname=$dbName;charset=utf8mb4", $user, $connectedServer['pass'], [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false
        ]);

        // Verificar si la tabla 'producto' existe; si no, inicializar con el archivo SQL
        $stmt = $pdo->query("SHOW TABLES LIKE 'producto'");
        if ($stmt->rowCount() === 0) {
            $sqlFile = __DIR__ . '/database.sql';
            if (file_exists($sqlFile)) {
                $sql = file_get_contents($sqlFile);
                $pdo->exec($sql);
            }
        }

        return $pdo;
    } catch (PDOException $e) {
        http_response_code(500);
        die(json_encode([
            'success' => false,
            'message' => 'Error al inicializar la base de datos: ' . $e->getMessage()
        ]));
    }
}
