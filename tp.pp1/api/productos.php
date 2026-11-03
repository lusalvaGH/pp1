<?php
// ============================================================================
// API: Gestión de Productos - DeliStock
// ============================================================================
// Casos de Uso: Alta, Listado, Modificación y Eliminación de Productos
// ============================================================================
declare(strict_types=1);
header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/../conexion.php';

$pdo = getDBConnection();
$method = $_SERVER['REQUEST_METHOD'];
$action = $_GET['action'] ?? $_POST['action'] ?? '';

// ----------------------------------------------------------------------------
// 1. LISTADO DE PRODUCTOS (GET)
// ----------------------------------------------------------------------------
if ($method === 'GET' && ($action === '' || $action === 'listar')) {
    $search = trim((string)($_GET['buscar'] ?? ''));

    if ($search !== '') {
        $stmt = $pdo->prepare("SELECT idProducto, nombre, precio, stock FROM producto WHERE nombre LIKE ? ORDER BY nombre ASC");
        $stmt->execute(['%' . $search . '%']);
    } else {
        $stmt = $pdo->query("SELECT idProducto, nombre, precio, stock FROM producto ORDER BY idProducto DESC");
    }

    $productos = $stmt->fetchAll();

    // Formatear tipos adecuadamente
    foreach ($productos as &$p) {
        $p['idProducto'] = (int)$p['idProducto'];
        $p['precio'] = (float)$p['precio'];
        $p['stock'] = (float)$p['stock'];
    }

    echo json_encode([
        'success' => true,
        'total' => count($productos),
        'productos' => $productos
    ]);
    exit;
}

// ----------------------------------------------------------------------------
// OBTENER UN PRODUCTO POR ID (GET)
// ----------------------------------------------------------------------------
if ($method === 'GET' && $action === 'obtener') {
    $id = (int)($_GET['id'] ?? 0);
    $stmt = $pdo->prepare("SELECT idProducto, nombre, precio, stock FROM producto WHERE idProducto = ?");
    $stmt->execute([$id]);
    $producto = $stmt->fetch();

    if ($producto) {
        $producto['idProducto'] = (int)$producto['idProducto'];
        $producto['precio'] = (float)$producto['precio'];
        $producto['stock'] = (float)$producto['stock'];
        echo json_encode(['success' => true, 'producto' => $producto]);
    } else {
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'Producto no encontrado (inexistente).']);
    }
    exit;
}

// Leer payload para operaciones POST / JSON
$rawInput = file_get_contents('php://input');
$data = json_decode($rawInput, true) ?? $_POST;

// ----------------------------------------------------------------------------
// 2. ALTA DE PRODUCTOS (POST - action=crear)
// ----------------------------------------------------------------------------
if ($method === 'POST' && $action === 'crear') {
    $nombre = trim((string)($data['nombre'] ?? ''));
    $precioRaw = $data['precio'] ?? null;
    $stockRaw = $data['stock'] ?? null;

    // A2. Campos obligatorios incompletos
    if ($nombre === '' || $precioRaw === null || $precioRaw === '' || $stockRaw === null || $stockRaw === '') {
        echo json_encode([
            'success' => false,
            'code' => 'A2',
            'message' => 'Todos los campos son obligatorios (Nombre, Precio y Stock).'
        ]);
        exit;
    }

    // A3. Precio o stock inválido
    if (!is_numeric($precioRaw) || !is_numeric($stockRaw)) {
        echo json_encode([
            'success' => false,
            'code' => 'A3',
            'message' => 'El precio y el stock deben ser valores numéricos.'
        ]);
        exit;
    }

    $precio = (float)$precioRaw;
    $stock = (float)$stockRaw;

    if ($precio <= 0) {
        echo json_encode([
            'success' => false,
            'code' => 'A3',
            'message' => 'El precio debe ser mayor a 0.'
        ]);
        exit;
    }

    if ($stock < 0) {
        echo json_encode([
            'success' => false,
            'code' => 'A3',
            'message' => 'El stock inicial no puede ser negativo.'
        ]);
        exit;
    }

    // A1. El producto ya existe (comprobación por nombre duplicado)
    $stmtCheck = $pdo->prepare("SELECT idProducto FROM producto WHERE LOWER(nombre) = LOWER(?) LIMIT 1");
    $stmtCheck->execute([$nombre]);
    if ($stmtCheck->fetch()) {
        echo json_encode([
            'success' => false,
            'code' => 'A1',
            'message' => 'Ya existe un producto registrado con el nombre "' . htmlspecialchars($nombre) . '".'
        ]);
        exit;
    }

    // Flujo Principal: Registrar producto
    try {
        $stmtInsert = $pdo->prepare("INSERT INTO producto (nombre, precio, stock) VALUES (?, ?, ?)");
        $stmtInsert->execute([$nombre, $precio, $stock]);
        $newId = (int)$pdo->lastInsertId();

        echo json_encode([
            'success' => true,
            'message' => '¡Producto registrado con éxito!',
            'producto' => [
                'idProducto' => $newId,
                'nombre' => $nombre,
                'precio' => $precio,
                'stock' => $stock
            ]
        ]);
    } catch (PDOException $e) {
        echo json_encode([
            'success' => false,
            'message' => 'Error al guardar en la base de datos: ' . $e->getMessage()
        ]);
    }
    exit;
}

// ----------------------------------------------------------------------------
// 3. MODIFICACIÓN DE PRODUCTOS (POST - action=actualizar)
// ----------------------------------------------------------------------------
if ($method === 'POST' && $action === 'actualizar') {
    $idProducto = (int)($data['idProducto'] ?? 0);
    $nombre = trim((string)($data['nombre'] ?? ''));
    $precioRaw = $data['precio'] ?? null;
    $stockRaw = $data['stock'] ?? null;

    // A1. Producto inexistente
    $stmtExists = $pdo->prepare("SELECT idProducto FROM producto WHERE idProducto = ?");
    $stmtExists->execute([$idProducto]);
    if (!$stmtExists->fetch()) {
        echo json_encode([
            'success' => false,
            'code' => 'A1',
            'message' => 'El producto a modificar no existe.'
        ]);
        exit;
    }

    // A2. Datos inválidos / campos incompletos
    if ($nombre === '' || $precioRaw === null || $precioRaw === '' || $stockRaw === null || $stockRaw === '') {
        echo json_encode([
            'success' => false,
            'code' => 'A2',
            'message' => 'Todos los campos son obligatorios.'
        ]);
        exit;
    }

    if (!is_numeric($precioRaw) || !is_numeric($stockRaw)) {
        echo json_encode([
            'success' => false,
            'code' => 'A2',
            'message' => 'El precio y el stock deben ser numéricos.'
        ]);
        exit;
    }

    $precio = (float)$precioRaw;
    $stock = (float)$stockRaw;

    if ($precio <= 0 || $stock < 0) {
        echo json_encode([
            'success' => false,
            'code' => 'A2',
            'message' => 'El precio debe ser mayor a 0 y el stock no puede ser negativo.'
        ]);
        exit;
    }

    // Verificar que el nuevo nombre no colisione con otro producto diferente
    $stmtCheck = $pdo->prepare("SELECT idProducto FROM producto WHERE LOWER(nombre) = LOWER(?) AND idProducto != ? LIMIT 1");
    $stmtCheck->execute([$nombre, $idProducto]);
    if ($stmtCheck->fetch()) {
        echo json_encode([
            'success' => false,
            'code' => 'A2',
            'message' => 'Ya existe otro producto con el nombre "' . htmlspecialchars($nombre) . '".'
        ]);
        exit;
    }

    // Flujo Principal: Guardar modificación
    try {
        $stmtUpdate = $pdo->prepare("UPDATE producto SET nombre = ?, precio = ?, stock = ? WHERE idProducto = ?");
        $stmtUpdate->execute([$nombre, $precio, $stock, $idProducto]);

        echo json_encode([
            'success' => true,
            'message' => 'Producto actualizado correctamente.',
            'producto' => [
                'idProducto' => $idProducto,
                'nombre' => $nombre,
                'precio' => $precio,
                'stock' => $stock
            ]
        ]);
    } catch (PDOException $e) {
        echo json_encode([
            'success' => false,
            'message' => 'Error al actualizar el producto: ' . $e->getMessage()
        ]);
    }
    exit;
}

// ----------------------------------------------------------------------------
// 4. ELIMINACIÓN DE PRODUCTOS (POST - action=eliminar)
// ----------------------------------------------------------------------------
if ($method === 'POST' && $action === 'eliminar') {
    $idProducto = (int)($data['idProducto'] ?? 0);

    // A1. Producto no encontrado
    $stmtFind = $pdo->prepare("SELECT idProducto, nombre FROM producto WHERE idProducto = ?");
    $stmtFind->execute([$idProducto]);
    $producto = $stmtFind->fetch();

    if (!$producto) {
        echo json_encode([
            'success' => false,
            'code' => 'A1',
            'message' => 'Producto no encontrado para eliminar.'
        ]);
        exit;
    }

    // Verificar si tiene ventas registradas en detalle_venta
    $stmtCheckSales = $pdo->prepare("SELECT COUNT(*) FROM detalle_venta WHERE idProducto = ?");
    $stmtCheckSales->execute([$idProducto]);
    $salesCount = (int)$stmtCheckSales->fetchColumn();

    if ($salesCount > 0) {
        echo json_encode([
            'success' => false,
            'code' => 'A2',
            'message' => 'No es posible eliminar el producto "' . htmlspecialchars($producto['nombre']) . '" porque ya cuenta con ' . $salesCount . ' registro(s) de venta en el historial. Puede modificar su stock a 0 si ya no se comercializa.'
        ]);
        exit;
    }

    // Flujo Principal: Eliminar
    try {
        $stmtDelete = $pdo->prepare("DELETE FROM producto WHERE idProducto = ?");
        $stmtDelete->execute([$idProducto]);

        echo json_encode([
            'success' => true,
            'message' => 'El producto "' . $producto['nombre'] . '" fue eliminado con éxito.'
        ]);
    } catch (PDOException $e) {
        echo json_encode([
            'success' => false,
            'message' => 'Error al eliminar el producto: ' . $e->getMessage()
        ]);
    }
    exit;
}

echo json_encode([
    'success' => false,
    'message' => 'Acción no soportada.'
]);
