<?php
session_start();

// Verificar sesión activa
if (!isset($_SESSION['user_id'])) {
    header("Location: ../login.php");
    exit();
}

// Verificar que sea administrador
if ($_SESSION['rol'] !== 'Administrador') {
    $_SESSION['mensaje_error'] = "No tienes permisos para acceder a esta sección";
    header("Location: ../index.php");
    exit();
}

require_once "../config/conexion.php";

// Obtener datos del usuario
$usuario = $_SESSION['usuario'] ?? 'Admin';
$rol = $_SESSION['rol'] ?? 'Administrador';
$empresa = $_SESSION['empresa'] ?? 'InModa';

// ===== PROCESAMIENTO DE ACCIONES =====
$mensaje = '';
$tipo_mensaje = '';

// Crear nuevo usuario
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['crear_usuario'])) {
    $nombre = trim($_POST['nombre']);
    $usuario_nuevo = trim($_POST['usuario']);
    $email = trim($_POST['email']);
    $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
    $rol_nuevo = $_POST['rol'];
    $estado = $_POST['estado'] ?? 'Activo';
    
    $stmt = $mysqli->prepare("INSERT INTO usuarios (nombre, usuario, email, password, rol, estado) VALUES (?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("ssssss", $nombre, $usuario_nuevo, $email, $password, $rol_nuevo, $estado);
    
    if ($stmt->execute()) {
        $mensaje = "Usuario creado exitosamente";
        $tipo_mensaje = "success";
    } else {
        $mensaje = "Error al crear usuario: " . $stmt->error;
        $tipo_mensaje = "error";
    }
    $stmt->close();
}

// Actualizar usuario
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['actualizar_usuario'])) {
    $id = $_POST['id'];
    $nombre = trim($_POST['nombre']);
    $usuario_upd = trim($_POST['usuario']);
    $email = trim($_POST['email']);
    $rol_upd = $_POST['rol'];
    $estado = $_POST['estado'];
    
    if (!empty($_POST['password'])) {
        $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
        $stmt = $mysqli->prepare("UPDATE usuarios SET nombre=?, usuario=?, email=?, password=?, rol=?, estado=? WHERE id=?");
        $stmt->bind_param("ssssssi", $nombre, $usuario_upd, $email, $password, $rol_upd, $estado, $id);
    } else {
        $stmt = $mysqli->prepare("UPDATE usuarios SET nombre=?, usuario=?, email=?, rol=?, estado=? WHERE id=?");
        $stmt->bind_param("sssssi", $nombre, $usuario_upd, $email, $rol_upd, $estado, $id);
    }
    
    if ($stmt->execute()) {
        $mensaje = "Usuario actualizado exitosamente";
        $tipo_mensaje = "success";
    } else {
        $mensaje = "Error al actualizar usuario";
        $tipo_mensaje = "error";
    }
    $stmt->close();
}

// Eliminar usuario
if (isset($_GET['eliminar'])) {
    $id = $_GET['eliminar'];
    
    // No permitir eliminar el propio usuario
    if ($id == $_SESSION['user_id']) {
        $mensaje = "No puedes eliminar tu propio usuario";
        $tipo_mensaje = "error";
    } else {
        $stmt = $mysqli->prepare("DELETE FROM usuarios WHERE id=?");
        $stmt->bind_param("i", $id);
        
        if ($stmt->execute()) {
            $mensaje = "Usuario eliminado exitosamente";
            $tipo_mensaje = "success";
        } else {
            $mensaje = "Error al eliminar usuario";
            $tipo_mensaje = "error";
        }
        $stmt->close();
    }
}

// Obtener todos los usuarios
$query = "SELECT id, nombre, usuario, email, rol, estado, fecha_registro FROM usuarios ORDER BY fecha_registro DESC";
$result_usuarios = $mysqli->query($query);
$usuarios = [];
while ($row = $result_usuarios->fetch_assoc()) {
    $usuarios[] = $row;
}

// Estadísticas
$total_usuarios = count($usuarios);
$usuarios_activos = count(array_filter($usuarios, fn($u) => $u['estado'] == 'Activo'));
$usuarios_administradores = count(array_filter($usuarios, fn($u) => $u['rol'] == 'Administrador'));
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>InModa - Usuarios</title>
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
            overflow-y: auto;
            overflow-x: hidden;
        }

        .usuarios-container {
            padding: 30px;
            background-color: var(--color-background-main);
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
            background: linear-gradient(135deg, var(--color-primary-blue), #0056b3);
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
            color: var(--color-primary-blue);
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
            border: 1px solid #c3e6cb;
        }

        .alert.error {
            background: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
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
        }

        .stat-icon {
            width: 60px;
            height: 60px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.8em;
        }

        .stat-icon.blue { background: linear-gradient(135deg, var(--color-primary-blue), #0056b3); color: white; }
        .stat-icon.green { background: linear-gradient(135deg, #28a745, #1e7e34); color: white; }
        .stat-icon.orange { background: linear-gradient(135deg, #fd7e14, #dc6502); color: white; }

        .stat-info h3 {
            margin: 0 0 5px 0;
            color: var(--color-light-grey-text);
            font-size: 0.9em;
        }

        .stat-info p {
            margin: 0;
            font-size: 1.8em;
            font-weight: 700;
        }

        .table-container {
            background: white;
            border-radius: 15px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.08);
            overflow: hidden;
            margin-bottom: 30px;
        }

        .search-bar {
            padding: 20px;
            border-bottom: 2px solid var(--color-secondary-grey);
        }

        .search-bar input {
            width: 100%;
            padding: 12px 20px;
            border: 2px solid var(--color-secondary-grey);
            border-radius: 10px;
            font-size: 1em;
            transition: all 0.3s ease;
        }

        .search-bar input:focus {
            outline: none;
            border-color: var(--color-primary-blue);
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        thead {
            background: linear-gradient(135deg, var(--color-primary-blue), #0056b3);
            color: white;
        }

        th {
            padding: 15px;
            text-align: left;
            font-weight: 600;
        }

        tbody tr {
            border-bottom: 1px solid var(--color-secondary-grey);
            transition: background 0.3s ease;
        }

        tbody tr:hover {
            background: #f8f9fa;
        }

        td {
            padding: 15px;
        }

        .badge {
            padding: 5px 12px;
            border-radius: 20px;
            font-size: 0.85em;
            font-weight: 600;
        }

        .badge.admin {
            background: #e3f2fd;
            color: #1976d2;
        }

        .badge.vendedor {
            background: #f3e5f5;
            color: #7b1fa2;
        }

        .badge.activo {
            background: #e8f5e9;
            color: #2e7d32;
        }

        .badge.inactivo {
            background: #ffebee;
            color: #c62828;
        }

        .btn-action {
            padding: 8px 15px;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            font-size: 0.9em;
            font-weight: 500;
            transition: all 0.3s ease;
            display: inline-flex;
            align-items: center;
            gap: 6px;
            margin: 3px 0;
        }

        .btn-edit {
            background: var(--color-primary-blue);
            color: white;
            margin-right: 10px;
        }

        .btn-edit:hover {
            background: #0056b3;
            transform: translateY(-2px);
            box-shadow: 0 4px 10px rgba(0,123,255,0.4);
        }

        .btn-delete {
            background: var(--color-red);
            color: white;
        }

        .btn-delete:hover {
            background: #c82333;
            transform: translateY(-2px);
            box-shadow: 0 4px 10px rgba(220,53,69,0.4);
        }

        /* Contenedor de acciones */
        td:last-child {
            white-space: nowrap;
            padding: 10px 15px;
        }

        /* Modal */
        .modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0,0,0,0.6);
            z-index: 1000;
            overflow-y: auto;
        }

        .modal.active {
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .modal-content {
            background: white;
            border-radius: 15px;
            width: 90%;
            max-width: 600px;
            max-height: 90vh;
            overflow-y: auto;
            box-shadow: 0 10px 40px rgba(0,0,0,0.3);
            animation: modalSlideIn 0.3s ease;
        }

        @keyframes modalSlideIn {
            from {
                opacity: 0;
                transform: translateY(-50px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .modal-header {
            padding: 25px 30px;
            border-bottom: 2px solid var(--color-secondary-grey);
            display: flex;
            justify-content: space-between;
            align-items: center;
            background: linear-gradient(135deg, var(--color-primary-blue), #0056b3);
            color: white;
            border-radius: 15px 15px 0 0;
        }

        .modal-header h2 {
            margin: 0;
            font-size: 1.5em;
        }

        .close-modal {
            background: none;
            border: none;
            font-size: 2em;
            color: white;
            cursor: pointer;
            transition: transform 0.2s;
        }

        .close-modal:hover {
            transform: rotate(90deg);
        }

        form {
            padding: 30px;
        }

        .form-group {
            margin-bottom: 20px;
        }

        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
            color: var(--color-text-black);
        }

        .form-group input,
        .form-group select {
            width: 100%;
            padding: 12px 15px;
            border: 2px solid var(--color-secondary-grey);
            border-radius: 8px;
            font-size: 1em;
            transition: all 0.3s ease;
        }

        .form-group input:focus,
        .form-group select:focus {
            outline: none;
            border-color: var(--color-primary-blue);
        }

        .form-actions {
            display: flex;
            gap: 15px;
            justify-content: flex-end;
            padding-top: 20px;
            border-top: 2px solid var(--color-secondary-grey);
        }

        .btn-cancel,
        .btn-submit {
            padding: 12px 30px;
            border: none;
            border-radius: 8px;
            font-size: 1em;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .btn-cancel {
            background: var(--color-secondary-grey);
            color: var(--color-text-black);
        }

        .btn-cancel:hover {
            background: var(--color-hover-grey);
        }

        .btn-submit {
            background: var(--color-primary-blue);
            color: white;
        }

        .btn-submit:hover {
            background: #0056b3;
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(0, 123, 255, 0.4);
        }

        .delete-modal-body {
            padding: 40px 30px;
            text-align: center;
        }

        .delete-modal-body i {
            font-size: 4em;
            color: var(--color-red);
            margin-bottom: 20px;
        }

        .delete-modal-body h3 {
            margin-bottom: 15px;
            color: var(--color-text-black);
        }

        .delete-modal-body p {
            color: var(--color-light-grey-text);
            margin-bottom: 10px;
        }

        .btn-confirm-delete {
            background: var(--color-red) !important;
            color: white !important;
        }

        .btn-confirm-delete:hover {
            background: #c82333 !important;
        }

        @media (max-width: 768px) {
            .usuarios-container {
                padding: 15px;
            }

            .page-header {
                flex-direction: column;
                gap: 15px;
            }

            .table-container {
                overflow-x: auto;
            }

            table {
                min-width: 800px;
            }
        }
    </style>
</head>
<body>

<div class="usuarios-container">
    <!-- Botón Volver -->
    <a href="../index.php" class="btn-back">
        <i class="fas fa-arrow-left"></i> Volver al Panel Principal
    </a>

    <!-- Encabezado -->
    <div class="page-header">
        <h1>
            <i class="fas fa-users"></i>
            Gestión de Usuarios
        </h1>
        <button class="btn-nuevo" onclick="abrirModalNuevo()">
            <i class="fas fa-user-plus"></i> Nuevo Usuario
        </button>
    </div>

    <!-- Mensajes -->
    <?php if ($mensaje): ?>
    <div class="alert <?= $tipo_mensaje ?>">
        <i class="fas fa-<?= $tipo_mensaje == 'success' ? 'check-circle' : 'exclamation-circle' ?>"></i>
        <?= $mensaje ?>
    </div>
    <?php endif; ?>

    <!-- Estadísticas -->
    <div class="stats-grid">
        <div class="stat-card">
            <div class="stat-icon blue">
                <i class="fas fa-users"></i>
            </div>
            <div class="stat-info">
                <h3>Total Usuarios</h3>
                <p><?= $total_usuarios ?></p>
            </div>
        </div>

        <div class="stat-card">
            <div class="stat-icon green">
                <i class="fas fa-user-check"></i>
            </div>
            <div class="stat-info">
                <h3>Usuarios Activos</h3>
                <p><?= $usuarios_activos ?></p>
            </div>
        </div>

        <div class="stat-card">
            <div class="stat-icon orange">
                <i class="fas fa-user-shield"></i>
            </div>
            <div class="stat-info">
                <h3>Administradores</h3>
                <p><?= $usuarios_administradores ?></p>
            </div>
        </div>
    </div>

    <!-- Tabla de Usuarios -->
    <div class="table-container">
        <div class="search-bar">
            <input type="text" id="searchInput" placeholder="🔍 Buscar usuario..." onkeyup="filtrarUsuarios()">
        </div>

        <table id="tablaUsuarios">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Nombre</th>
                    <th>Usuario</th>
                    <th>Email</th>
                    <th>Rol</th>
                    <th>Estado</th>
                    <th>Fecha Registro</th>
                    <th>Acciones</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($usuarios as $user): ?>
                <tr>
                    <td>#<?= $user['id'] ?></td>
                    <td><i class="fas fa-user"></i> <?= htmlspecialchars($user['nombre']) ?></td>
                    <td><?= htmlspecialchars($user['usuario']) ?></td>
                    <td><?= htmlspecialchars($user['email']) ?></td>
                    <td>
                        <span class="badge <?= strtolower($user['rol']) == 'administrador' ? 'admin' : 'vendedor' ?>">
                            <?= $user['rol'] ?>
                        </span>
                    </td>
                    <td>
                        <span class="badge <?= strtolower($user['estado']) ?>">
                            <?= $user['estado'] ?>
                        </span>
                    </td>
                    <td><?= date('d/m/Y', strtotime($user['fecha_registro'])) ?></td>
                    <td>
                        <button class="btn-action btn-edit" onclick='editarUsuario(<?= json_encode($user) ?>)'>
                            <i class="fas fa-edit"></i> Editar
                        </button>
                        <?php if ($user['id'] != $_SESSION['user_id']): ?>
                        <button class="btn-action btn-delete" onclick="abrirModalEliminar(<?= $user['id'] ?>, '<?= htmlspecialchars($user['nombre']) ?>')">
                            <i class="fas fa-trash"></i> Eliminar
                        </button>
                        <?php endif; ?>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Modal Nuevo/Editar Usuario -->
<div class="modal" id="modalUsuario">
    <div class="modal-content">
        <div class="modal-header">
            <h2 id="modalTitulo"><i class="fas fa-user-plus"></i> Nuevo Usuario</h2>
            <button class="close-modal" onclick="cerrarModal()">&times;</button>
        </div>

        <form method="POST" action="" id="formUsuario">
            <input type="hidden" name="id" id="userId">
            
            <div class="form-group">
                <label><i class="fas fa-user"></i> Nombre Completo *</label>
                <input type="text" name="nombre" id="userName" required>
            </div>

            <div class="form-group">
                <label><i class="fas fa-id-badge"></i> Usuario *</label>
                <input type="text" name="usuario" id="userUsername" required>
            </div>

            <div class="form-group">
                <label><i class="fas fa-envelope"></i> Email *</label>
                <input type="email" name="email" id="userEmail" required>
            </div>

            <div class="form-group">
                <label><i class="fas fa-lock"></i> Contraseña <span id="passwordHint" style="display:none;">(Dejar vacío para mantener actual)</span></label>
                <input type="password" name="password" id="userPassword">
            </div>

            <div class="form-group">
                <label><i class="fas fa-user-tag"></i> Rol *</label>
                <select name="rol" id="userRol" required>
                    <option value="Administrador">Administrador</option>
                    <option value="Vendedor">Vendedor</option>
                </select>
            </div>

            <div class="form-group">
                <label><i class="fas fa-toggle-on"></i> Estado *</label>
                <select name="estado" id="userEstado" required>
                    <option value="Activo">Activo</option>
                    <option value="Inactivo">Inactivo</option>
                </select>
            </div>

            <div class="form-actions">
                <button type="button" class="btn-cancel" onclick="cerrarModal()">
                    <i class="fas fa-times"></i> Cancelar
                </button>
                <button type="submit" class="btn-submit" name="crear_usuario" id="btnSubmit">
                    <i class="fas fa-save"></i> Guardar
                </button>
            </div>
        </form>
    </div>
</div>

<!-- Modal Eliminar Usuario -->
<div id="modalEliminar" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <h2><i class="fas fa-exclamation-triangle"></i> Confirmar Eliminación</h2>
            <button class="close-modal" onclick="cerrarModalEliminar()">&times;</button>
        </div>
        <div class="delete-modal-body">
            <i class="fas fa-user-times"></i>
            <h3>¿Estás seguro?</h3>
            <p>¿Deseas eliminar el usuario</p>
            <p><strong id="nombre_usuario_eliminar"></strong>?</p>
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
let usuarioIdEliminar = null;

// Modal Nuevo/Editar Usuario
function abrirModalNuevo() {
    document.getElementById('modalTitulo').innerHTML = '<i class="fas fa-user-plus"></i> Nuevo Usuario';
    document.getElementById('formUsuario').reset();
    document.getElementById('userId').value = '';
    document.getElementById('btnSubmit').name = 'crear_usuario';
    document.getElementById('btnSubmit').innerHTML = '<i class="fas fa-save"></i> Crear Usuario';
    document.getElementById('passwordHint').style.display = 'none';
    document.getElementById('userPassword').required = true;
    document.getElementById('modalUsuario').classList.add('active');
}

function editarUsuario(user) {
    document.getElementById('modalTitulo').innerHTML = '<i class="fas fa-edit"></i> Editar Usuario';
    document.getElementById('userId').value = user.id;
    document.getElementById('userName').value = user.nombre;
    document.getElementById('userUsername').value = user.usuario;
    document.getElementById('userEmail').value = user.email;
    document.getElementById('userRol').value = user.rol;
    document.getElementById('userEstado').value = user.estado;
    document.getElementById('userPassword').value = '';
    document.getElementById('btnSubmit').name = 'actualizar_usuario';
    document.getElementById('btnSubmit').innerHTML = '<i class="fas fa-save"></i> Actualizar';
    document.getElementById('passwordHint').style.display = 'inline';
    document.getElementById('userPassword').required = false;
    document.getElementById('modalUsuario').classList.add('active');
}

function cerrarModal() {
    document.getElementById('modalUsuario').classList.remove('active');
}

// Modal Eliminar
function abrirModalEliminar(id, nombre) {
    usuarioIdEliminar = id;
    document.getElementById('nombre_usuario_eliminar').textContent = nombre;
    document.getElementById('modalEliminar').classList.add('active');
}

function cerrarModalEliminar() {
    document.getElementById('modalEliminar').classList.remove('active');
    usuarioIdEliminar = null;
}

function confirmarEliminacion() {
    if (usuarioIdEliminar) {
        window.location.href = `?eliminar=${usuarioIdEliminar}`;
    }
}

// Filtrar usuarios
function filtrarUsuarios() {
    const input = document.getElementById('searchInput');
    const filter = input.value.toLowerCase();
    const table = document.getElementById('tablaUsuarios');
    const rows = table.getElementsByTagName('tr');

    for (let i = 1; i < rows.length; i++) {
        const row = rows[i];
        const text = row.textContent.toLowerCase();
        
        if (text.includes(filter)) {
            row.style.display = '';
        } else {
            row.style.display = 'none';
        }
    }
}

// Cerrar modales al hacer clic fuera
window.onclick = function(event) {
    if (event.target == document.getElementById('modalUsuario')) {
        cerrarModal();
    }
    if (event.target == document.getElementById('modalEliminar')) {
        cerrarModalEliminar();
    }
}
</script>

</body>
</html>