<?php
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'No autorizado']);
    exit();
}

require_once "../config/conexion.php";

$venta_id = $_GET['id'] ?? null;

if (!$venta_id) {
    echo json_encode(['success' => false, 'message' => 'ID de venta no proporcionado']);
    exit();
}

$user_id = $_SESSION['user_id'];
$es_admin = ($_SESSION['rol'] === 'Administrador');

try {
    // Obtener información de la venta
    if ($es_admin) {
        $query = "SELECT v.*, u.usuario as vendedor, c.nombre as cliente 
                  FROM ventas v 
                  LEFT JOIN usuarios u ON v.usuario_id = u.id 
                  LEFT JOIN clientes c ON v.cliente_id = c.id 
                  WHERE v.id = ?";
        $stmt = $mysqli->prepare($query);
        $stmt->bind_param("i", $venta_id);
    } else {
        $query = "SELECT v.*, u.usuario as vendedor, c.nombre as cliente 
                  FROM ventas v 
                  LEFT JOIN usuarios u ON v.usuario_id = u.id 
                  LEFT JOIN clientes c ON v.cliente_id = c.id 
                  WHERE v.id = ? AND v.usuario_id = ?";
        $stmt = $mysqli->prepare($query);
        $stmt->bind_param("ii", $venta_id, $user_id);
    }
    
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        echo json_encode(['success' => false, 'message' => 'Venta no encontrada']);
        exit();
    }
    
    $venta = $result->fetch_assoc();
    $stmt->close();
    
    // Formatear la fecha
    $venta['fecha'] = date('d/m/Y H:i', strtotime($venta['fecha']));
    
    // Obtener productos de la venta
    $query_productos = "SELECT dv.*, p.nombre, p.codigo 
                        FROM detalle_venta dv 
                        INNER JOIN productos p ON dv.producto_id = p.id 
                        WHERE dv.venta_id = ?";
    $stmt = $mysqli->prepare($query_productos);
    $stmt->bind_param("i", $venta_id);
    $stmt->execute();
    $result_productos = $stmt->get_result();
    
    $productos = [];
    while ($row = $result_productos->fetch_assoc()) {
        $productos[] = $row;
    }
    $stmt->close();
    
    echo json_encode([
        'success' => true,
        'venta' => $venta,
        'productos' => $productos
    ]);
    
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
}
?>