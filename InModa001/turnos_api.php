<?php
session_start();
header('Content-Type: application/json');

// Verificar sesión
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'No autorizado']);
    exit();
}

require_once "config/conexion.php";

$rol_usuario = $_SESSION['rol'] ?? 'Vendedor';
$es_admin = ($rol_usuario === 'Administrador');
$action = $_GET['action'] ?? $_POST['action'] ?? '';

// ===== LISTAR TURNOS =====
if ($action === 'listar') {
    try {
        $stmt = $mysqli->prepare("SELECT * FROM turnos ORDER BY fecha_inicio ASC");
        $stmt->execute();
        $result = $stmt->get_result();
        
        $turnos = [];
        while ($row = $result->fetch_assoc()) {
            $turnos[] = $row;
        }
        
        echo json_encode(['success' => true, 'turnos' => $turnos]);
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => 'Error al obtener turnos: ' . $e->getMessage()]);
    }
    exit();
}

// ===== AGREGAR TURNO (Solo Admin) =====
if ($action === 'agregar') {
    if (!$es_admin) {
        echo json_encode(['success' => false, 'message' => 'No tienes permisos para agregar turnos']);
        exit();
    }
    
    $titulo = $_POST['titulo'] ?? '';
    $descripcion = $_POST['descripcion'] ?? '';
    $fecha_inicio = $_POST['fecha_inicio'] ?? '';
    $fecha_fin = $_POST['fecha_fin'] ?? '';
    
    if (empty($titulo) || empty($fecha_inicio)) {
        echo json_encode(['success' => false, 'message' => 'Título y fecha de inicio son requeridos']);
        exit();
    }
    
    try {
        $stmt = $mysqli->prepare("INSERT INTO turnos (titulo, descripcion, fecha_inicio, fecha_fin, creado_por) VALUES (?, ?, ?, ?, ?)");
        $stmt->bind_param("ssssi", $titulo, $descripcion, $fecha_inicio, $fecha_fin, $_SESSION['user_id']);
        
        if ($stmt->execute()) {
            echo json_encode(['success' => true, 'message' => 'Turno agregado correctamente', 'id' => $mysqli->insert_id]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Error al agregar turno']);
        }
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
    }
    exit();
}

// ===== EDITAR TURNO (Solo Admin) =====
if ($action === 'editar') {
    if (!$es_admin) {
        echo json_encode(['success' => false, 'message' => 'No tienes permisos para editar turnos']);
        exit();
    }
    
    $id = $_POST['id'] ?? 0;
    $titulo = $_POST['titulo'] ?? '';
    $descripcion = $_POST['descripcion'] ?? '';
    $fecha_inicio = $_POST['fecha_inicio'] ?? '';
    $fecha_fin = $_POST['fecha_fin'] ?? '';
    
    if (empty($id) || empty($titulo) || empty($fecha_inicio)) {
        echo json_encode(['success' => false, 'message' => 'Datos incompletos']);
        exit();
    }
    
    try {
        $stmt = $mysqli->prepare("UPDATE turnos SET titulo=?, descripcion=?, fecha_inicio=?, fecha_fin=? WHERE id=?");
        $stmt->bind_param("ssssi", $titulo, $descripcion, $fecha_inicio, $fecha_fin, $id);
        
        if ($stmt->execute()) {
            echo json_encode(['success' => true, 'message' => 'Turno actualizado correctamente']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Error al actualizar turno']);
        }
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
    }
    exit();
}

// ===== ELIMINAR TURNO (Solo Admin) =====
if ($action === 'eliminar') {
    if (!$es_admin) {
        echo json_encode(['success' => false, 'message' => 'No tienes permisos para eliminar turnos']);
        exit();
    }
    
    $id = $_POST['id'] ?? $_GET['id'] ?? 0;
    
    if (empty($id)) {
        echo json_encode(['success' => false, 'message' => 'ID no válido']);
        exit();
    }
    
    try {
        $stmt = $mysqli->prepare("DELETE FROM turnos WHERE id=?");
        $stmt->bind_param("i", $id);
        
        if ($stmt->execute()) {
            echo json_encode(['success' => true, 'message' => 'Turno eliminado correctamente']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Error al eliminar turno']);
        }
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
    }
    exit();
}

// Acción no válida
echo json_encode(['success' => false, 'message' => 'Acción no válida']);
?>