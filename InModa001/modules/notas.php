<?php
session_start();

// Verificar sesión activa
if (!isset($_SESSION['user_id'])) {
    header("Location: ../login.php");
    exit();
}

require_once "../config/conexion.php";

// Obtener datos del usuario
$usuario = $_SESSION['usuario'] ?? 'Usuario';
$rol = $_SESSION['rol'] ?? 'Vendedor';
$empresa = $_SESSION['empresa'] ?? 'InModa';
$user_id = $_SESSION['user_id'];
$es_admin = ($rol === 'Administrador');

// ===== PROCESAMIENTO DE ACCIONES (solo admin) =====
$mensaje = '';
$tipo_mensaje = '';

// Crear nota (solo admin)
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['crear_nota']) && $es_admin) {
    $titulo = trim($_POST['titulo']);
    $contenido = trim($_POST['contenido']);
    $categoria = $_POST['categoria'];
    $prioridad = $_POST['prioridad'];
    
    $stmt = $mysqli->prepare("INSERT INTO notas (usuario_id, titulo, contenido, categoria, prioridad, fecha_creacion) VALUES (?, ?, ?, ?, ?, NOW())");
    $stmt->bind_param("issss", $user_id, $titulo, $contenido, $categoria, $prioridad);
    
    if ($stmt->execute()) {
        $mensaje = "Nota creada exitosamente";
        $tipo_mensaje = "success";
    } else {
        $mensaje = "Error al crear nota: " . $stmt->error;
        $tipo_mensaje = "error";
    }
    $stmt->close();
}

// Actualizar nota (solo admin)
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['actualizar_nota']) && $es_admin) {
    $id = $_POST['id'];
    $titulo = trim($_POST['titulo']);
    $contenido = trim($_POST['contenido']);
    $categoria = $_POST['categoria'];
    $prioridad = $_POST['prioridad'];
    
    $stmt = $mysqli->prepare("UPDATE notas SET titulo=?, contenido=?, categoria=?, prioridad=?, fecha_modificacion=NOW() WHERE id=?");
    $stmt->bind_param("ssssi", $titulo, $contenido, $categoria, $prioridad, $id);
    
    if ($stmt->execute()) {
        $mensaje = "Nota actualizada exitosamente";
        $tipo_mensaje = "success";
    } else {
        $mensaje = "Error al actualizar nota";
        $tipo_mensaje = "error";
    }
    $stmt->close();
}

// Eliminar nota (solo admin)
if (isset($_GET['eliminar']) && $es_admin) {
    $id = $_GET['eliminar'];
    
    $stmt = $mysqli->prepare("DELETE FROM notas WHERE id=?");
    $stmt->bind_param("i", $id);
    
    if ($stmt->execute()) {
        $mensaje = "Nota eliminada exitosamente";
        $tipo_mensaje = "success";
    } else {
        $mensaje = "Error al eliminar nota";
        $tipo_mensaje = "error";
    }
    $stmt->close();
}

// Marcar como completada (solo admin)
if (isset($_GET['completar']) && $es_admin) {
    $id = $_GET['completar'];
    
    $stmt = $mysqli->prepare("UPDATE notas SET completada = NOT completada WHERE id=?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $stmt->close();
    
    header("Location: notas.php");
    exit();
}

// Obtener notas
$query = "SELECT n.*, u.usuario as autor FROM notas n LEFT JOIN usuarios u ON n.usuario_id = u.id ORDER BY n.fecha_creacion DESC";
$result_notas = $mysqli->query($query);
$notas = [];
while ($row = $result_notas->fetch_assoc()) {
    $notas[] = $row;
}

// Estadísticas
$total_notas = count($notas);
$notas_pendientes = count(array_filter($notas, fn($n) => !$n['completada']));
$notas_completadas = count(array_filter($notas, fn($n) => $n['completada']));
$notas_alta_prioridad = count(array_filter($notas, fn($n) => $n['prioridad'] == 'Alta' && !$n['completada']));
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>InModa - Notas</title>
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

        .notas-container {
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
            background: linear-gradient(135deg, #6f42c1, #5a32a3);
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

        .btn-nueva-nota {
            background: white;
            color: #6f42c1;
            padding: 12px 25px;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            font-size: 1em;
            font-weight: 600;
            transition: all 0.3s ease;
        }

        .btn-nueva-nota:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(255,255,255,0.3);
        }

        .btn-nueva-nota:disabled {
            opacity: 0.5;
            cursor: not-allowed;
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
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
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

        .stat-icon.purple { background: linear-gradient(135deg, #6f42c1, #5a32a3); color: white; }
        .stat-icon.yellow { background: linear-gradient(135deg, #FFD700, #ffc107); color: white; }
        .stat-icon.green { background: linear-gradient(135deg, #28a745, #1e7e34); color: white; }
        .stat-icon.red { background: linear-gradient(135deg, #dc3545, #c82333); color: white; }

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

        .filtros-section {
            background: white;
            padding: 20px;
            border-radius: 15px;
            margin-bottom: 25px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.08);
            display: flex;
            gap: 15px;
            flex-wrap: wrap;
            align-items: center;
        }

        .filtros-section label {
            font-weight: 600;
            color: var(--color-text-black);
        }

        .filtros-section select {
            padding: 10px 15px;
            border: 2px solid var(--color-secondary-grey);
            border-radius: 8px;
            font-size: 0.95em;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .filtros-section select:focus {
            outline: none;
            border-color: #6f42c1;
        }

        .notas-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(350px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }

        .nota-card {
            background: white;
            padding: 20px;
            border-radius: 15px;
            box-shadow: 0 3px 12px rgba(0,0,0,0.1);
            transition: all 0.3s ease;
            border-left: 5px solid #6f42c1;
        }

        .nota-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 20px rgba(0,0,0,0.15);
        }

        .nota-card.completada {
            opacity: 0.7;
            border-left-color: var(--color-green);
        }

        .nota-card.prioridad-alta { border-left-color: var(--color-red); }
        .nota-card.prioridad-media { border-left-color: #FFD700; }
        .nota-card.prioridad-baja { border-left-color: var(--color-green); }

        .nota-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 12px;
        }

        .nota-titulo {
            font-size: 1.3em;
            font-weight: 700;
            color: var(--color-text-black);
            margin: 0;
        }

        .nota-badges {
            display: flex;
            gap: 8px;
            flex-wrap: wrap;
        }

        .badge {
            padding: 4px 10px;
            border-radius: 20px;
            font-size: 0.8em;
            font-weight: 600;
        }

        .badge.trabajo { background: #e3f2fd; color: #1976d2; }
        .badge.personal { background: #f3e5f5; color: #7b1fa2; }
        .badge.recordatorio { background: #fff3e0; color: #f57c00; }
        .badge.idea { background: #e8f5e9; color: #388e3c; }

        .badge.alta { background: #ffebee; color: #c62828; }
        .badge.media { background: #fff9e7; color: #f57f17; }
        .badge.baja { background: #e8f5e9; color: #2e7d32; }

        .nota-contenido {
            color: var(--color-light-grey-text);
            margin-bottom: 15px;
            line-height: 1.6;
            max-height: 150px;
            overflow-y: auto;
        }

        .nota-footer {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding-top: 12px;
            border-top: 1px solid var(--color-secondary-grey);
            font-size: 0.85em;
            color: var(--color-light-grey-text);
        }

        .nota-actions {
            display: flex;
            gap: 8px;
        }

        .btn-icon {
            width: 35px;
            height: 35px;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1em;
        }

        .btn-icon.complete {
            background: #e8f5e9;
            color: var(--color-green);
        }

        .btn-icon.complete:hover {
            background: var(--color-green);
            color: white;
        }

        .btn-icon.edit {
            background: #e3f2fd;
            color: #1976d2;
        }

        .btn-icon.edit:hover {
            background: #1976d2;
            color: white;
        }

        .btn-icon.delete {
            background: #ffebee;
            color: var(--color-red);
        }

        .btn-icon.delete:hover {
            background: var(--color-red);
            color: white;
        }

        .btn-icon:disabled {
            opacity: 0.3;
            cursor: not-allowed;
        }

        .no-data {
            text-align: center;
            padding: 60px 20px;
            color: var(--color-light-grey-text);
        }

        .no-data i {
            font-size: 5em;
            margin-bottom: 20px;
            color: var(--color-secondary-grey);
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
            background: linear-gradient(135deg, #6f42c1, #5a32a3);
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
        .form-group select,
        .form-group textarea {
            width: 100%;
            padding: 12px 15px;
            border: 2px solid var(--color-secondary-grey);
            border-radius: 8px;
            font-size: 1em;
            transition: all 0.3s ease;
        }

        .form-group textarea {
            resize: vertical;
            min-height: 120px;
        }

        .form-group input:focus,
        .form-group select:focus,
        .form-group textarea:focus {
            outline: none;
            border-color: #6f42c1;
        }

        .form-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
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
            background: #6f42c1;
            color: white;
        }

        .btn-submit:hover {
            background: #5a32a3;
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(111, 66, 193, 0.4);
        }

        @media (max-width: 768px) {
            .notas-container {
                padding: 15px;
            }

            .page-header {
                flex-direction: column;
                gap: 15px;
            }

            .notas-grid {
                grid-template-columns: 1fr;
            }

            .filtros-section {
                flex-direction: column;
            }

            .form-grid {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>

<div class="notas-container">
    <!-- Botón Volver -->
    <a href="../index.php" class="btn-back">
        <i class="fas fa-arrow-left"></i> Volver al Panel Principal
    </a>

    <!-- Encabezado -->
    <div class="page-header">
        <h1>
            <i class="fas fa-sticky-note"></i>
            Notas
        </h1>
        <?php if ($es_admin): ?>
        <button class="btn-nueva-nota" onclick="abrirModalNuevo()">
            <i class="fas fa-plus"></i> Nueva Nota
        </button>
        <?php else: ?>
        <button class="btn-nueva-nota" disabled title="Solo administradores pueden crear notas">
            <i class="fas fa-lock"></i> Solo Lectura
        </button>
        <?php endif; ?>
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
            <div class="stat-icon purple">
                <i class="fas fa-sticky-note"></i>
            </div>
            <div class="stat-info">
                <h3>Total Notas</h3>
                <p><?= $total_notas ?></p>
            </div>
        </div>

        <div class="stat-card">
            <div class="stat-icon yellow">
                <i class="fas fa-clock"></i>
            </div>
            <div class="stat-info">
                <h3>Pendientes</h3>
                <p><?= $notas_pendientes ?></p>
            </div>
        </div>

        <div class="stat-card">
            <div class="stat-icon green">
                <i class="fas fa-check-circle"></i>
            </div>
            <div class="stat-info">
                <h3>Completadas</h3>
                <p><?= $notas_completadas ?></p>
            </div>
        </div>

        <div class="stat-card">
            <div class="stat-icon red">
                <i class="fas fa-exclamation-triangle"></i>
            </div>
            <div class="stat-info">
                <h3>Alta Prioridad</h3>
                <p><?= $notas_alta_prioridad ?></p>
            </div>
        </div>
    </div>

    <!-- Filtros -->
    <div class="filtros-section">
        <label>Filtrar por:</label>
        <select id="filtroCategoria" onchange="filtrarNotas()">
            <option value="">Todas las categorías</option>
            <option value="Trabajo">Trabajo</option>
            <option value="Personal">Personal</option>
            <option value="Recordatorio">Recordatorio</option>
            <option value="Idea">Idea</option>
        </select>

        <select id="filtroPrioridad" onchange="filtrarNotas()">
            <option value="">Todas las prioridades</option>
            <option value="Alta">Alta</option>
            <option value="Media">Media</option>
            <option value="Baja">Baja</option>
        </select>

        <select id="filtroEstado" onchange="filtrarNotas()">
            <option value="">Todos los estados</option>
            <option value="pendiente">Pendientes</option>
            <option value="completada">Completadas</option>
        </select>
    </div>

    <!-- Grid de Notas -->
    <?php if (count($notas) > 0): ?>
    <div class="notas-grid">
        <?php foreach ($notas as $nota): ?>
        <div class="nota-card <?= $nota['completada'] ? 'completada' : '' ?> prioridad-<?= strtolower($nota['prioridad']) ?>"
             data-categoria="<?= $nota['categoria'] ?>"
             data-prioridad="<?= $nota['prioridad'] ?>"
             data-estado="<?= $nota['completada'] ? 'completada' : 'pendiente' ?>">
            
            <div class="nota-header">
                <h3 class="nota-titulo">
                    <?php if ($nota['completada']): ?>
                    <i class="fas fa-check-circle" style="color: var(--color-green);"></i>
                    <?php endif; ?>
                    <?= htmlspecialchars($nota['titulo']) ?>
                </h3>
            </div>

            <div class="nota-badges">
                <span class="badge <?= strtolower($nota['categoria']) ?>">
                    <?= $nota['categoria'] ?>
                </span>
                <span class="badge <?= strtolower($nota['prioridad']) ?>">
                    <?= $nota['prioridad'] ?>
                </span>
            </div>

            <div class="nota-contenido">
                <?= nl2br(htmlspecialchars($nota['contenido'])) ?>
            </div>

            <div class="nota-footer">
                <div>
                    <span><?= htmlspecialchars($nota['autor'] ?? 'Sistema') ?></span>
                    <span style="margin-left: 10px;">• <?= date('d/m/Y', strtotime($nota['fecha_creacion'])) ?></span>
                </div>

                <div class="nota-actions">
                    <button class="btn-icon complete" 
                            onclick="<?= $es_admin ? "completarNota({$nota['id']})" : "alert('⚠️ Solo administradores pueden marcar notas')" ?>"
                            title="<?= $nota['completada'] ? 'Marcar como pendiente' : 'Marcar como completada' ?>"
                            <?= !$es_admin ? 'disabled' : '' ?>>
                        <i class="fas fa-check-circle"></i>
                    </button>
                    <button class="btn-icon edit" 
                            onclick="<?= $es_admin ? "editarNota(" . json_encode($nota) . ")" : "alert('⚠️ Solo administradores pueden editar notas')" ?>"
                            title="Editar nota"
                            <?= !$es_admin ? 'disabled' : '' ?>>
                        <i class="fas fa-edit"></i>
                    </button>
                    <button class="btn-icon delete" 
                            onclick="<?= $es_admin ? "eliminarNota({$nota['id']}, '" . htmlspecialchars($nota['titulo']) . "')" : "alert('⚠️ Solo administradores pueden eliminar notas')" ?>"
                            title="Eliminar nota"
                            <?= !$es_admin ? 'disabled' : '' ?>>
                        <i class="fas fa-trash"></i>
                    </button>
                </div>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
    <?php else: ?>
    <div class="no-data">
        <i class="fas fa-sticky-note"></i>
        <p>No hay notas registradas</p>
        <?php if ($es_admin): ?>
        <button class="btn-nueva-nota" onclick="abrirModalNuevo()" style="margin-top: 20px;">
            <i class="fas fa-plus"></i> Crear primera nota
        </button>
        <?php endif; ?>
    </div>
    <?php endif; ?>
</div>

<!-- Modal Nueva/Editar Nota -->
<?php if ($es_admin): ?>
<div class="modal" id="modalNota">
    <div class="modal-content">
        <div class="modal-header">
            <h2 id="modalTitulo"><i class="fas fa-sticky-note"></i> Nueva Nota</h2>
            <button class="close-modal" onclick="cerrarModal()">&times;</button>
        </div>

        <form method="POST" action="" id="formNota">
            <input type="hidden" name="id" id="notaId">
            
            <div class="form-group">
                <label><i class="fas fa-heading"></i> Título *</label>
                <input type="text" name="titulo" id="notaTitulo" required placeholder="Título de la nota">
            </div>

            <div class="form-group">
                <label><i class="fas fa-align-left"></i> Contenido *</label>
                <textarea name="contenido" id="notaContenido" required placeholder="Escribe el contenido de la nota..."></textarea>
            </div>

            <div class="form-grid">
                <div class="form-group">
                    <label><i class="fas fa-tag"></i> Categoría *</label>
                    <select name="categoria" id="notaCategoria" required>
                        <option value="Trabajo">Trabajo</option>
                        <option value="Personal">Personal</option>
                        <option value="Recordatorio">Recordatorio</option>
                        <option value="Idea">Idea</option>
                    </select>
                </div>

                <div class="form-group">
                    <label><i class="fas fa-exclamation-circle"></i> Prioridad *</label>
                    <select name="prioridad" id="notaPrioridad" required>
                        <option value="Baja">Baja</option>
                        <option value="Media" selected>Media</option>
                        <option value="Alta">Alta</option>
                    </select>
                </div>
            </div>

            <div class="form-actions">
                <button type="button" class="btn-cancel" onclick="cerrarModal()">
                    <i class="fas fa-times"></i> Cancelar
                </button>
                <button type="submit" class="btn-submit" name="crear_nota" id="btnSubmit">
                    <i class="fas fa-save"></i> Guardar Nota
                </button>
            </div>
        </form>
    </div>
</div>
<?php endif; ?>

<script>
<?php if ($es_admin): ?>
// Modal Nota (solo para admin)
function abrirModalNuevo() {
    document.getElementById('modalTitulo').innerHTML = '<i class="fas fa-sticky-note"></i> Nueva Nota';
    document.getElementById('formNota').reset();
    document.getElementById('notaId').value = '';
    document.getElementById('btnSubmit').name = 'crear_nota';
    document.getElementById('btnSubmit').innerHTML = '<i class="fas fa-save"></i> Crear Nota';
    document.getElementById('modalNota').classList.add('active');
}

function editarNota(nota) {
    document.getElementById('modalTitulo').innerHTML = '<i class="fas fa-edit"></i> Editar Nota';
    document.getElementById('notaId').value = nota.id;
    document.getElementById('notaTitulo').value = nota.titulo;
    document.getElementById('notaContenido').value = nota.contenido;
    document.getElementById('notaCategoria').value = nota.categoria;
    document.getElementById('notaPrioridad').value = nota.prioridad;
    document.getElementById('btnSubmit').name = 'actualizar_nota';
    document.getElementById('btnSubmit').innerHTML = '<i class="fas fa-save"></i> Actualizar';
    document.getElementById('modalNota').classList.add('active');
}

function cerrarModal() {
    document.getElementById('modalNota').classList.remove('active');
}

function eliminarNota(id, titulo) {
    if (confirm(`¿Estás seguro de eliminar la nota "${titulo}"?\n\nEsta acción no se puede deshacer.`)) {
        window.location.href = `?eliminar=${id}`;
    }
}

function completarNota(id) {
    window.location.href = `?completar=${id}`;
}
<?php else: ?>
function abrirModalNuevo() {
    alert('⚠️ Solo administradores pueden crear notas');
}
<?php endif; ?>

// Filtrar notas
function filtrarNotas() {
    const categoria = document.getElementById('filtroCategoria').value.toLowerCase();
    const prioridad = document.getElementById('filtroPrioridad').value.toLowerCase();
    const estado = document.getElementById('filtroEstado').value;
    
    const cards = document.querySelectorAll('.nota-card');
    
    cards.forEach(card => {
        const cardCategoria = card.dataset.categoria.toLowerCase();
        const cardPrioridad = card.dataset.prioridad.toLowerCase();
        const cardEstado = card.dataset.estado;
        
        const matchCategoria = !categoria || cardCategoria === categoria;
        const matchPrioridad = !prioridad || cardPrioridad === prioridad;
        const matchEstado = !estado || cardEstado === estado;
        
        if (matchCategoria && matchPrioridad && matchEstado) {
            card.style.display = '';
        } else {
            card.style.display = 'none';
        }
    });
}

// Cerrar modal al hacer clic fuera
window.onclick = function(event) {
    const modal = document.getElementById('modalNota');
    if (event.target == modal) {
        cerrarModal();
    }
}
</script>

</body>
</html>