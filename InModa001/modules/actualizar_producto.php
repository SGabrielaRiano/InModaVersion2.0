<?php
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'No autorizado']);
    exit();
}

require_once "../config/conexion.php";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = $_POST['id'] ?? null;
    $nombre = $_POST['nombre'] ?? '';
    $codigo = $_POST['codigo'] ?? '';
    $categoria = $_POST['categoria'] ?? '';
    $precio_venta = $_POST['precio_venta'] ?? 0;
    $stock = $_POST['stock'] ?? 0;
    $proveedor_id = $_POST['proveedor_id'] ?? null;
    
    if (!$id || empty($nombre) || empty($codigo)) {
        echo json_encode(['success' => false, 'message' => 'Datos incompletos']);
        exit();
    }
    
    try {
        $stmt = $mysqli->prepare("UPDATE productos SET nombre = ?, codigo = ?, categoria = ?, precio_venta = ?, stock = ?, proveedor_id = ? WHERE id = ?");
        
        if ($proveedor_id === '' || $proveedor_id === null) {
            $proveedor_id = null;
        }
        
        $stmt->bind_param("sssdiid", $nombre, $codigo, $categoria, $precio_venta, $stock, $proveedor_id, $id);
        
        if ($stmt->execute()) {
            echo json_encode(['success' => true, 'message' => 'Producto actualizado correctamente']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Error al actualizar el producto']);
        }
        
        $stmt->close();
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Método no permitido']);
}
?>