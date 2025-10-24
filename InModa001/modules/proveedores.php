<?php
session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: ../login.php");
    exit();
}

if ($_SESSION['rol'] !== 'Administrador') {
    header("Location: ../index.php");
    exit();
}

require_once "../config/conexion.php";

$usuario = $_SESSION['usuario'] ?? 'Admin';
$rol = $_SESSION['rol'] ?? 'Administrador';
$empresa = $_SESSION['empresa'] ?? 'InModa';

$mensaje = '';
$tipo_mensaje = '';

// Crear proveedor
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['crear_proveedor'])) {
    $nombre = trim($_POST['nombre']);
    $nit = trim($_POST['nit']);
    $telefono = trim($_POST['telefono']);
    $email = trim($_POST['email']);
    $direccion = trim($_POST['direccion']);
    $contacto = trim($_POST['contacto']);
    $tipo_producto = trim($_POST['tipo_producto']);
    
    $stmt = $mysqli->prepare("INSERT INTO proveedores (nombre, nit, telefono, email, direccion, contacto, tipo_producto) VALUES (?, ?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("sssssss", $nombre, $nit, $telefono, $email, $direccion, $contacto, $tipo_producto);
    
    if ($stmt->execute()) {
        $mensaje = "Proveedor creado exitosamente";
        $tipo_mensaje = "success";
    } else {
        $mensaje = "Error al crear proveedor: " . $stmt->error;
        $tipo_mensaje = "error";
    }
    $stmt->close();
}

// Actualizar proveedor
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['actualizar_proveedor'])) {
    $id = $_POST['id'];
    $nombre = trim($_POST['nombre']);
    $nit = trim($_POST['nit']);
    $telefono = trim($_POST['telefono']);
    $email = trim($_POST['email']);
    $direccion = trim($_POST['direccion']);
    $contacto = trim($_POST['contacto']);
    $tipo_producto = trim($_POST['tipo_producto']);
    
    $stmt = $mysqli->prepare("UPDATE proveedores SET nombre=?, nit=?, telefono=?, email=?, direccion=?, contacto=?, tipo_producto=? WHERE id=?");
    $stmt->bind_param("sssssssi", $nombre, $nit, $telefono, $email, $direccion, $contacto, $tipo_producto, $id);
    
    if ($stmt->execute()) {
        $mensaje = "Proveedor actualizado exitosamente";
        $tipo_mensaje = "success";
    } else {
        $mensaje = "Error al actualizar proveedor";
        $tipo_mensaje = "error";
    }
    $stmt->close();
}

// Eliminar proveedor
if (isset($_GET['eliminar'])) {
    $id = $_GET['eliminar'];
    
    $stmt = $mysqli->prepare("DELETE FROM proveedores WHERE id=?");
    $stmt->bind_param("i", $id);
    
    if ($stmt->execute()) {
        $mensaje = "Proveedor eliminado exitosamente";
        $tipo_mensaje = "success";
    } else {
        $mensaje = "Error al eliminar proveedor";
        $tipo_mensaje = "error";
    }
    $stmt->close();
}

// Obtener proveedores
$query = "SELECT * FROM proveedores ORDER BY nombre ASC";
$result_proveedores = $mysqli->query($query);
$proveedores = [];
while ($row = $result_proveedores->fetch_assoc()) {
    $proveedores[] = $row;
}

$total_proveedores = count($proveedores);
$proveedores_activos = count(array_filter($proveedores, fn($p) => isset($p['estado']) && $p['estado'] == 'Activo'));
if ($proveedores_activos == 0) $proveedores_activos = $total_proveedores;
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>InModa - Proveedores</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <style>
        :root {
            --color-primary-blue: #007bff;
            --color-background-main: #f5f5dc;
            --color-secondary-grey: #d3d3d3;
            --color-text-black: #000000;
            --color-white: #FFFFFF;
            --color-light-grey-text: #666666;
            --color-hover-grey: #bfbfbf;
            --color-green: #28a745;
            --color-red: #dc3545;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background-color: var(--color-background-main);
            overflow-y: auto !important;
            overflow-x: hidden !important;
        }

        .proveedores-container {
            padding: 30px;
            min-height: 100vh;
            max-width: 1400px;
            margin: 0 auto;
        }

        .btn-back {
            background: var(--color-secondary-grey);
            color: var(--color-text-black);
            padding: 10px 20px;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            margin-bottom: 20px;
            transition: all 0.3s ease;
        }

        .btn-back:hover {
            background: var(--color-hover-grey);
        }

        .page-header {
            background: linear-gradient(135deg, #fd7e14, #dc6502);
            color: white;
            padding: 25px 30px;
            border-radius: 15px;
            margin-bottom: 30px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.15);
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .page-header h1 {
            margin: 0;
            font-size: 2em;
            display: flex;
            align-items: center;
            gap: 15px;
        }

        .btn-nuevo {
            background: white;
            color: #fd7e14;
            padding: 12px 25px;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            font-size: 1em;
            font-weight: 600;
            transition: all 0.3s ease;
        }

        .btn-nuevo:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(255,255,255,0.3);
        }

        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }

        .stat-card {
            background: white;
            padding: 25px;
            border-radius: 15px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.08);
            display: flex;
            align-items: center;
            gap: 20px;
            transition: all 0.3s ease;
        }

        .stat-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 5px 20px rgba(0,0,0,0.15);
        }

        .stat-icon {
            width: 60px;
            height: 60px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.8em;
            color: white;
        }

        .stat-icon.orange { background: linear-gradient(135deg, #fd7e14, #dc6502); }
        .stat-icon.green { background: linear-gradient(135deg, #28a745, #1e7e34); }

        .stat-info h3 {
            font-size: 0.9em;
            color: var(--color-light-grey-text);
            margin-bottom: 5px;
        }

        .stat-info p {
            font-size: 2em;
            font-weight: 700;
            color: var(--color-text-black);
        }

        .alert {
            padding: 15px 20px;
            border-radius: 10px;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 10px;
            animation: slideDown 0.4s ease;
        }

        @keyframes slideDown {
            from { opacity: 0; transform: translateY(-20px); }
            to { opacity: 1; transform: translateY(0); }
        }

        .alert.success {
            background: #d4edda;
            color: #155724;
            border-left: 4px solid var(--color-green);
        }

        .alert.error {
            background: #f8d7da;
            color: #721c24;
            border-left: 4px solid var(--color-red);
        }

        .proveedores-card {
            background: white;
            padding: 25px;
            border-radius: 15px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.08);
        }

        .proveedores-card h3 {
            margin-bottom: 20px;
            color: var(--color-text-black);
            display: flex;
            align-items: center;
            gap: 10px;
            font-size: 1.3em;
        }

        .search-bar {
            margin-bottom: 20px;
        }

        .search-bar input {
            width: 100%;
            padding: 12px 20px;
            border: 2px solid var(--color-secondary-grey);
            border-radius: 8px;
            font-size: 1em;
            transition: all 0.3s ease;
        }

        .search-bar input:focus {
            outline: none;
            border-color: #fd7e14;
            box-shadow: 0 0 0 3px rgba(253, 126, 20, 0.1);
        }

        .proveedores-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(320px, 1fr));
            gap: 20px;
        }

        .proveedor-card {
            background: #f8f9fa;
            padding: 20px;
            border-radius: 12px;
            border: 2px solid transparent;
            transition: all 0.3s ease;
        }

        .proveedor-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 5px 20px rgba(253, 126, 20, 0.2);
            border-color: #fd7e14;
        }

        .proveedor-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 15px;
            padding-bottom: 15px;
            border-bottom: 2px solid #dee2e6;
        }

        .proveedor-header h4 {
            color: var(--color-text-black);
            font-size: 1.2em;
        }

        .badge {
            padding: 5px 12px;
            border-radius: 20px;
            font-size: 0.85em;
            font-weight: 600;
        }

        .badge.ropa { background: #d4edda; color: #155724; }
        .badge.accesorios { background: #fff3cd; color: #856404; }
        .badge.calzado { background: #cce5ff; color: #004085; }
        .badge.textil { background: #f8d7da; color: #721c24; }
        .badge.mixto { background: #e2e3e5; color: #383d41; }

        .proveedor-info {
            margin-bottom: 15px;
        }

        .proveedor-info p {
            margin-bottom: 8px;
            color: var(--color-light-grey-text);
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .proveedor-info i {
            width: 20px;
            color: #fd7e14;
        }

        .proveedor-actions {
            display: flex;
            gap: 10px;
        }

        .btn-action {
            flex: 1;
            padding: 10px;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            font-weight: 600;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 5px;
        }

        .btn-edit {
            background: #ffc107;
            color: var(--color-text-black);
        }

        .btn-edit:hover {
            background: #e0a800;
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(255, 193, 7, 0.3);
        }

        .btn-delete {
            background: var(--color-red);
            color: white;
        }

        .btn-delete:hover {
            background: #c82333;
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(220, 53, 69, 0.3);
        }

        .no-data {
            text-align: center;
            padding: 60px 20px;
            color: var(--color-light-grey-text);
        }

        .no-data i {
            font-size: 4em;
            margin-bottom: 15px;
            opacity: 0.3;
        }

        /* Modal Styles */
        .modal {
            display: none;
            position: fixed;
            z-index: 2000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0, 0, 0, 0.6);
            animation: fadeIn 0.3s ease;
        }

        .modal.active {
            display: block;
        }

        @keyframes fadeIn {
            from { opacity: 0; }
            to { opacity: 1; }
        }

        .modal-content {
            background-color: white;
            margin: 3% auto;
            padding: 0;
            border-radius: 15px;
            width: 90%;
            max-width: 700px;
            box-shadow: 0 10px 40px rgba(0, 0, 0, 0.3);
            animation: slideIn 0.3s ease;
            max-height: 90vh;
            overflow-y: auto;
        }

        @keyframes slideIn {
            from { transform: translateY(-50px); opacity: 0; }
            to { transform: translateY(0); opacity: 1; }
        }

        .modal-header {
            background: linear-gradient(135deg, #fd7e14, #dc6502);
            color: white;
            padding: 20px 25px;
            border-radius: 15px 15px 0 0;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .modal-header h2 {
            margin: 0;
            font-size: 1.5em;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .close-modal {
            background: none;
            border: none;
            color: white;
            font-size: 28px;
            font-weight: bold;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .close-modal:hover {
            transform: rotate(90deg);
        }

        .modal form {
            padding: 25px;
        }

        .form-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 20px;
            margin-bottom: 20px;
        }

        .form-group {
            display: flex;
            flex-direction: column;
        }

        .form-group.full-width {
            grid-column: 1 / -1;
        }

        .form-group label {
            margin-bottom: 8px;
            color: var(--color-text-black);
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .form-group input,
        .form-group select {
            padding: 12px 15px;
            border: 2px solid var(--color-secondary-grey);
            border-radius: 8px;
            font-size: 1em;
            transition: all 0.3s ease;
        }

        .form-group input:focus,
        .form-group select:focus {
            outline: none;
            border-color: #fd7e14;
            box-shadow: 0 0 0 3px rgba(253, 126, 20, 0.1);
        }

        .form-actions {
            display: flex;
            gap: 10px;
            justify-content: flex-end;
            padding-top: 20px;
            border-top: 2px solid #f0f0f0;
        }

        .btn-cancel,
        .btn-submit {
            padding: 12px 25px;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            font-size: 1em;
            font-weight: 600;
            transition: all 0.3s ease;
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }

        .btn-cancel {
            background: var(--color-secondary-grey);
            color: var(--color-text-black);
        }

        .btn-cancel:hover {
            background: var(--color-hover-grey);
        }

        .btn-submit {
            background: var(--color-green);
            color: white;
        }

        .btn-submit:hover {
            background: #218838;
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(40, 167, 69, 0.3);
        }

        /* Modal Eliminar */
        .delete-modal-body {
            text-align: center;
            padding: 30px 25px;
        }

        .delete-modal-body i {
            font-size: 4em;
            color: var(--color-red);
            margin-bottom: 20px;
        }

        .delete-modal-body h3 {
            margin-bottom: 10px;
            color: var(--color-text-black);
        }

        .delete-modal-body p {
            color: var(--color-light-grey-text);
            margin-bottom: 5px;
        }

        .btn-confirm-delete {
            background: var(--color-red);
            color: white;
        }

        .btn-confirm-delete:hover {
            background: #c82333;
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(220, 53, 69, 0.3);
        }

        /* Responsive */
        @media (max-width: 768px) {
            .proveedores-container {
                padding: 15px;
            }

            .page-header {
                flex-direction: column;
                text-align: center;
                gap: 15px;
            }

            .form-grid {
                grid-template-columns: 1fr;
            }

            .proveedores-grid {
                grid-template-columns: 1fr;
            }

            .modal-content {
                width: 95%;
                margin: 10% auto;
            }
        }
    </style>
</head>
<body>

<div class="proveedores-container">
    <a href="../index.php" class="btn-back">
        <i class="fas fa-arrow-left"></i> Volver al Inicio
    </a>

    <div class="page-header">
        <div>
            <h1><i class="fas fa-truck-loading"></i> Gestión de Proveedores</h1>
            <p style="margin: 5px 0 0 0; opacity: 0.9;">Administra tus proveedores</p>
        </div>
        <button class="btn-nuevo" onclick="abrirModalNuevo()">
            <i class="fas fa-plus"></i> Nuevo Proveedor
        </button>
    </div>

    <?php if ($mensaje): ?>
    <div class="alert <?= $tipo_mensaje ?>">
        <i class="fas fa-<?= $tipo_mensaje == 'success' ? 'check-circle' : 'exclamation-circle' ?>"></i>
        <?= $mensaje ?>
    </div>
    <?php endif; ?>

    <div class="stats-grid">
        <div class="stat-card">
            <div class="stat-icon orange">
                <i class="fas fa-truck"></i>
            </div>
            <div class="stat-info">
                <h3>Total Proveedores</h3>
                <p><?= $total_proveedores ?></p>
            </div>
        </div>

        <div class="stat-card">
            <div class="stat-icon green">
                <i class="fas fa-check-circle"></i>
            </div>
            <div class="stat-info">
                <h3>Proveedores Activos</h3>
                <p><?= $proveedores_activos ?></p>
            </div>
        </div>
    </div>

    <div class="proveedores-card">
        <h3><i class="fas fa-list"></i> Listado de Proveedores</h3>
        
        <div class="search-bar">
            <input type="text" id="searchInput" placeholder="🔍 Buscar proveedor..." onkeyup="filtrarProveedores()">
        </div>

        <?php if (count($proveedores) > 0): ?>
        <div class="proveedores-grid" id="proveedoresGrid">
            <?php foreach ($proveedores as $proveedor): ?>
            <div class="proveedor-card" data-search="<?= strtolower(htmlspecialchars($proveedor['nombre'] . ' ' . ($proveedor['nit'] ?? '') . ' ' . ($proveedor['tipo_producto'] ?? ''))) ?>">
                <div class="proveedor-header">
                    <h4><?= htmlspecialchars($proveedor['nombre']) ?></h4>
                    <span class="badge <?= strtolower(str_replace(' ', '', $proveedor['tipo_producto'] ?? 'ropa')) ?>">
                        <?= htmlspecialchars($proveedor['tipo_producto'] ?? 'Ropa') ?>
                    </span>
                </div>
                
                <div class="proveedor-info">
                    <?php if (isset($proveedor['nit'])): ?>
                    <p><i class="fas fa-id-card"></i> <strong>NIT:</strong> <?= htmlspecialchars($proveedor['nit']) ?></p>
                    <?php endif; ?>
                    <p><i class="fas fa-phone"></i> <?= htmlspecialchars($proveedor['telefono']) ?></p>
                    <p><i class="fas fa-envelope"></i> <?= htmlspecialchars($proveedor['email']) ?></p>
                    <p><i class="fas fa-map-marker-alt"></i> <?= htmlspecialchars($proveedor['direccion']) ?></p>
                    <?php if (isset($proveedor['contacto'])): ?>
                    <p><i class="fas fa-user-tie"></i> <strong>Contacto:</strong> <?= htmlspecialchars($proveedor['contacto']) ?></p>
                    <?php endif; ?>
                </div>

                <div class="proveedor-actions">
                    <button class="btn-action btn-edit" onclick='editarProveedor(<?= json_encode($proveedor) ?>)'>
                        <i class="fas fa-edit"></i> Editar
                    </button>
                    <button class="btn-action btn-delete" onclick="abrirModalEliminar(<?= $proveedor['id'] ?>, '<?= htmlspecialchars($proveedor['nombre']) ?>')">
                        <i class="fas fa-trash"></i> Eliminar
                    </button>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
        <?php else: ?>
        <div class="no-data">
            <i class="fas fa-truck"></i>
            <p>No hay proveedores registrados</p>
        </div>
        <?php endif; ?>
    </div>
</div>

<!-- Modal Nuevo/Editar Proveedor -->
<div class="modal" id="modalProveedor">
    <div class="modal-content">
        <div class="modal-header">
            <h2 id="modalTitulo"><i class="fas fa-truck-loading"></i> Nuevo Proveedor</h2>
            <button class="close-modal" onclick="cerrarModal()">&times;</button>
        </div>

        <form method="POST" action="" id="formProveedor">
            <input type="hidden" name="id" id="proveedorId">
            
            <div class="form-grid">
                <div class="form-group">
                    <label><i class="fas fa-building"></i> Nombre de Empresa *</label>
                    <input type="text" name="nombre" id="proveedorNombre" required>
                </div>

                <div class="form-group">
                    <label><i class="fas fa-id-card"></i> NIT *</label>
                    <input type="text" name="nit" id="proveedorNit" required>
                </div>

                <div class="form-group">
                    <label><i class="fas fa-phone"></i> Teléfono *</label>
                    <input type="tel" name="telefono" id="proveedorTelefono" required>
                </div>

                <div class="form-group">
                    <label><i class="fas fa-envelope"></i> Email *</label>
                    <input type="email" name="email" id="proveedorEmail" required>
                </div>

                <div class="form-group full-width">
                    <label><i class="fas fa-map-marker-alt"></i> Dirección *</label>
                    <input type="text" name="direccion" id="proveedorDireccion" required>
                </div>

                <div class="form-group">
                    <label><i class="fas fa-user-tie"></i> Persona de Contacto *</label>
                    <input type="text" name="contacto" id="proveedorContacto" required>
                </div>

                <div class="form-group">
                    <label><i class="fas fa-tags"></i> Tipo de Producto *</label>
                    <select name="tipo_producto" id="proveedorTipo" required>
                        <option value="Ropa">Ropa</option>
                        <option value="Accesorios">Accesorios</option>
                        <option value="Calzado">Calzado</option>
                        <option value="Textil">Textil</option>
                        <option value="Mixto">Mixto</option>
                    </select>
                </div>
            </div>

            <div class="form-actions">
                <button type="button" class="btn-cancel" onclick="cerrarModal()">
                    <i class="fas fa-times"></i> Cancelar
                </button>
                <button type="submit" class="btn-submit" name="crear_proveedor" id="btnSubmit">
                    <i class="fas fa-save"></i> Guardar
                </button>
            </div>
        </form>
    </div>
</div>

<!-- Modal Eliminar Proveedor -->
<div id="modalEliminar" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <h2><i class="fas fa-exclamation-triangle"></i> Confirmar Eliminación</h2>
            <button class="close-modal" onclick="cerrarModalEliminar()">&times;</button>
        </div>
        <div class="delete-modal-body">
            <i class="fas fa-trash-alt"></i>
            <h3>¿Estás seguro?</h3>
            <p>¿Deseas eliminar el proveedor</p>
            <p><strong id="nombre_proveedor_eliminar"></strong>?</p>
            <p style="color: var(--color-red); margin-top: 15px;">Esta acción no se puede deshacer.</p>
        </div>
        <div class="form-actions" style="padding: 0 25px 25px; border: none;">
            <button type="button" class="btn-cancel" onclick="cerrarModalEliminar()">
                <i class="fas fa-times"></i> Cancelar
            </button>
            <button type="button" class="btn-cancel btn-confirm-delete" onclick="confirmarEliminacion()">
                <i class="fas fa-trash"></i> Sí, Eliminar
            </button>
        </div>
    </div>
</div>

<script>
let proveedorIdEliminar = null;

function abrirModalNuevo() {
    document.getElementById('modalTitulo').innerHTML = '<i class="fas fa-truck-loading"></i> Nuevo Proveedor';
    document.getElementById('formProveedor').reset();
    document.getElementById('proveedorId').value = '';
    document.getElementById('btnSubmit').name = 'crear_proveedor';
    document.getElementById('btnSubmit').innerHTML = '<i class="fas fa-save"></i> Crear Proveedor';
    document.getElementById('modalProveedor').classList.add('active');
}

function editarProveedor(proveedor) {
    document.getElementById('modalTitulo').innerHTML = '<i class="fas fa-edit"></i> Editar Proveedor';
    document.getElementById('proveedorId').value = proveedor.id;
    document.getElementById('proveedorNombre').value = proveedor.nombre;
    document.getElementById('proveedorNit').value = proveedor.nit || '';
    document.getElementById('proveedorTelefono').value = proveedor.telefono;
    document.getElementById('proveedorEmail').value = proveedor.email;
    document.getElementById('proveedorDireccion').value = proveedor.direccion;
    document.getElementById('proveedorContacto').value = proveedor.contacto || '';
    document.getElementById('proveedorTipo').value = proveedor.tipo_producto || 'Ropa';
    document.getElementById('btnSubmit').name = 'actualizar_proveedor';
    document.getElementById('btnSubmit').innerHTML = '<i class="fas fa-save"></i> Actualizar';
    document.getElementById('modalProveedor').classList.add('active');
}

function cerrarModal() {
    document.getElementById('modalProveedor').classList.remove('active');
}

function abrirModalEliminar(id, nombre) {
    proveedorIdEliminar = id;
    document.getElementById('nombre_proveedor_eliminar').textContent = nombre;
    document.getElementById('modalEliminar').classList.add('active');
}

function cerrarModalEliminar() {
    document.getElementById('modalEliminar').classList.remove('active');
    proveedorIdEliminar = null;
}

function confirmarEliminacion() {
    if (proveedorIdEliminar) {
        window.location.href = `?eliminar=${proveedorIdEliminar}`;
    }
}

function filtrarProveedores() {
    const input = document.getElementById('searchInput');
    const filter = input.value.toLowerCase();
    const cards = document.querySelectorAll('.proveedor-card');

    cards.forEach(card => {
        const searchText = card.getAttribute('data-search');
        if (searchText.includes(filter)) {
            card.style.display = '';
        } else {
            card.style.display = 'none';
        }
    });
}

// Cerrar modales al hacer clic fuera de ellos
window.onclick = function(event) {
    if (event.target == document.getElementById('modalProveedor')) {
        cerrarModal();
    }
    if (event.target == document.getElementById('modalEliminar')) {
        cerrarModalEliminar();
    }
}
</script>

</body>
</html>