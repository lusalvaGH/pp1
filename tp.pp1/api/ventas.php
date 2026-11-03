<?php
// ============================================================================
// API: Gestión de Ventas y Actualización de Stock - DeliStock
// ============================================================================
// Casos de Uso: Registrar Venta (include Actualización de Stock), Listado de Ventas
// ============================================================================
declare(strict_types=1);
session_start();
header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/../conexion.php';

$pdo = getDBConnection();
$method = $_SERVER['REQUEST_METHOD'];
$action = $_GET['action'] ?? $_POST['action'] ?? '';

// ----------------------------------------------------------------------------
// 1. LISTADO DE VENTAS (GET)
// ----------------------------------------------------------------------------
if ($method === 'GET' && ($action === '' || $action === 'listar')) {
    // Consultar las ventas con su administrador asociado
    $stmt = $pdo->query("
        SELECT 
            v.idVenta,
            v.fecha,
            v.total,
            v.idAdministrador,
            COALESCE(a.nombre, 'Administrador') AS nombreAdministrador
        FROM venta v
        LEFT JOIN administrador a ON v.idAdministrador = a.idAdministrador
        ORDER BY v.fecha DESC, v.idVenta DESC
    ");
    $ventas = $stmt->fetchAll();

    // Obtener detalles de cada venta
    $resultado = [];
    foreach ($ventas as $v) {
        $stmtDetalle = $pdo->prepare("
            SELECT 
                dv.idDetalleVenta,
                dv.idProducto,
                p.nombre AS nombreProducto,
                dv.cantidad,
                dv.precioUnitario,
                (dv.cantidad * dv.precioUnitario) AS subtotal
            FROM detalle_venta dv
            INNER JOIN producto p ON dv.idProducto = p.idProducto
            WHERE dv.idVenta = ?
            ORDER BY dv.idDetalleVenta ASC
        ");
        $stmtDetalle->execute([$v['idVenta']]);
        $detalles = $stmtDetalle->fetchAll();

        // Convertir tipos
        foreach ($detalles as &$d) {
            $d['idDetalleVenta'] = (int)$d['idDetalleVenta'];
            $d['idProducto'] = (int)$d['idProducto'];
            $d['cantidad'] = (float)$d['cantidad'];
            $d['precioUnitario'] = (float)$d['precioUnitario'];
            $d['subtotal'] = (float)$d['subtotal'];
        }

        $resultado[] = [
            'idVenta' => (int)$v['idVenta'],
            'fecha' => $v['fecha'],
            'total' => (float)$v['total'],
            'idAdministrador' => (int)$v['idAdministrador'],
            'nombreAdministrador' => $v['nombreAdministrador'],
            'detalles' => $detalles
        ];
    }

    echo json_encode([
        'success' => true,
        'total' => count($resultado),
        'ventas' => $resultado
    ]);
    exit;
}

// ----------------------------------------------------------------------------
// 2. REGISTRAR VENTA (POST) - Con Actualización Automática de Stock (Transaccional)
// ----------------------------------------------------------------------------
$rawInput = file_get_contents('php://input');
$data = json_decode($rawInput, true) ?? $_POST;

if ($method === 'POST' && ($action === 'registrar' || $action === 'crear')) {
    $items = $data['items'] ?? [];
    $idAdmin = (int)($_SESSION['admin']['idAdministrador'] ?? $data['idAdministrador'] ?? 1);

    if (empty($items) || !is_array($items)) {
        echo json_encode([
            'success' => false,
            'code' => 'A2',
            'message' => 'Debe agregar al menos un producto a la venta.'
        ]);
        exit;
    }

    // Iniciar Transacción para asegurar la atomicidad (Registrar Venta + Actualizar Stock)
    try {
        $pdo->beginTransaction();

        $totalVenta = 0.0;
        $detallesValidados = [];

        // 1. Validar productos y stock disponible
        foreach ($items as $item) {
            $idProd = (int)($item['idProducto'] ?? 0);
            $cantidad = (float)($item['cantidad'] ?? 0);

            if ($idProd <= 0 || $cantidad <= 0) {
                $pdo->rollBack();
                echo json_encode([
                    'success' => false,
                    'code' => 'A2',
                    'message' => 'Datos de producto o cantidad inválidos en la venta.'
                ]);
                exit;
            }

            // Consultar producto actual con bloqueo para actualización (FOR UPDATE)
            $stmtProd = $pdo->prepare("SELECT idProducto, nombre, precio, stock FROM producto WHERE idProducto = ? FOR UPDATE");
            $stmtProd->execute([$idProd]);
            $producto = $stmtProd->fetch();

            // A2. Producto inexistente
            if (!$producto) {
                $pdo->rollBack();
                echo json_encode([
                    'success' => false,
                    'code' => 'A2',
                    'message' => 'El producto seleccionado (ID: ' . $idProd . ') no existe en la base de datos.'
                ]);
                exit;
            }

            $stockActual = (float)$producto['stock'];
            $precioUnitario = (float)$producto['precio'];

            // A1. Stock insuficiente
            if ($stockActual < $cantidad) {
                $pdo->rollBack();
                echo json_encode([
                    'success' => false,
                    'code' => 'A1',
                    'message' => 'Stock insuficiente para "' . $producto['nombre'] . '". Disponible: ' . $stockActual . ', Solicitado: ' . $cantidad . '.'
                ]);
                exit;
            }

            $subtotal = $cantidad * $precioUnitario;
            $totalVenta += $subtotal;

            $detallesValidados[] = [
                'idProducto' => $idProd,
                'nombre' => $producto['nombre'],
                'cantidad' => $cantidad,
                'precioUnitario' => $precioUnitario,
                'nuevoStock' => $stockActual - $cantidad
            ];
        }

        // 2. Registrar la Venta (Cabecera)
        $stmtVenta = $pdo->prepare("INSERT INTO venta (idAdministrador, fecha, total) VALUES (?, NOW(), ?)");
        $stmtVenta->execute([$idAdmin, $totalVenta]);
        $idVenta = (int)$pdo->lastInsertId();

        // 3. Registrar Detalle de Venta y Actualizar Stock (CU 6: Actualización de Stock)
        $stmtDetalle = $pdo->prepare("INSERT INTO detalle_venta (idVenta, idProducto, cantidad, precioUnitario) VALUES (?, ?, ?, ?)");
        $stmtUpdateStock = $pdo->prepare("UPDATE producto SET stock = ? WHERE idProducto = ?");

        foreach ($detallesValidados as $detalle) {
            // Guardar renglón de detalle
            $stmtDetalle->execute([
                $idVenta,
                $detalle['idProducto'],
                $detalle['cantidad'],
                $detalle['precioUnitario']
            ]);

            // Descontar automáticamente el stock
            $stmtUpdateStock->execute([
                $detalle['nuevoStock'],
                $detalle['idProducto']
            ]);
        }

        // Confirmar la transacción
        $pdo->commit();

        echo json_encode([
            'success' => true,
            'message' => '¡Venta #' . $idVenta . ' registrada con éxito y stock actualizado!',
            'idVenta' => $idVenta,
            'total' => $totalVenta,
            'itemsAfectados' => count($detallesValidados)
        ]);
    } catch (Exception $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        echo json_encode([
            'success' => false,
            'code' => 'A1_STOCK',
            'message' => 'Error durante el registro de la venta o actualización del stock: ' . $e->getMessage()
        ]);
    }
    exit;
}

echo json_encode([
    'success' => false,
    'message' => 'Acción no permitida.'
]);
