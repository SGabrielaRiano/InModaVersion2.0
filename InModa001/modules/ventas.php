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

// ===== PROCESAMIENTO DE ACCIONES =====
$mensaje = '';
$tipo_mensaje = '';

// Crear nueva venta
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['crear_venta'])) {
    $cliente_id = $_POST['cliente_id'] ?? null;
    $metodo_pago = $_POST['metodo_pago'];
    $productos = json_decode($_POST['productos'], true);
    
    // Calcular total
    $total = 0;
    foreach ($productos as $producto) {
        $total += $producto['subtotal'];
    }
    
    // Iniciar transacción
    $mysqli->begin_transaction();
    
    try {
        // Insertar venta
        $stmt = $mysqli->prepare("INSERT INTO ventas (usuario_id, cliente_id, total, metodo_pago, fecha) VALUES (?, ?, ?, ?, NOW())");
        $stmt->bind_param("iids", $user_id, $cliente_id, $total, $metodo_pago);
        $stmt->execute();
        $venta_id = $mysqli->insert_id;
        $stmt->close();
        
        // Insertar detalles de venta y actualizar stock
        foreach ($productos as $producto) {
            $stmt = $mysqli->prepare("INSERT INTO detalle_venta (venta_id, producto_id, cantidad, precio_unitario, subtotal) VALUES (?, ?, ?, ?, ?)");
            $stmt->bind_param("iiidd", $venta_id, $producto['id'], $producto['cantidad'], $producto['precio'], $producto['subtotal']);
            $stmt->execute();
            $stmt->close();
            
            // Actualizar stock
            $stmt = $mysqli->prepare("UPDATE productos SET stock = stock - ? WHERE id = ?");
            $stmt->bind_param("ii", $producto['cantidad'], $producto['id']);
            $stmt->execute();
            $stmt->close();
        }
        
        $mysqli->commit();
        $mensaje = "Venta registrada exitosamente - ID: #$venta_id";
        $tipo_mensaje = "success";
    } catch (Exception $e) {
        $mysqli->rollback();
        $mensaje = "Error al registrar venta: " . $e->getMessage();
        $tipo_mensaje = "error";
    }
}

// Obtener ventas (admin ve todas, vendedor solo las suyas)
if ($es_admin) {
    $query = "SELECT v.*, u.usuario, c.nombre as cliente_nombre 
              FROM ventas v 
              LEFT JOIN usuarios u ON v.usuario_id = u.id 
              LEFT JOIN clientes c ON v.cliente_id = c.id 
              ORDER BY v.fecha DESC 
              LIMIT 50";
} else {
    $query = "SELECT v.*, u.usuario, c.nombre as cliente_nombre 
              FROM ventas v 
              LEFT JOIN usuarios u ON v.usuario_id = u.id 
              LEFT JOIN clientes c ON v.cliente_id = c.id 
              WHERE v.usuario_id = $user_id 
              ORDER BY v.fecha DESC 
              LIMIT 50";
}

$result_ventas = $mysqli->query($query);
$ventas = [];
while ($row = $result_ventas->fetch_assoc()) {
    $ventas[] = $row;
}

// Obtener productos para el selector
$query_productos = "SELECT id, nombre, codigo, precio_venta, stock FROM productos WHERE stock > 0 ORDER BY nombre";
$result_productos = $mysqli->query($query_productos);
$productos = [];
while ($row = $result_productos->fetch_assoc()) {
    $productos[] = $row;
}

// Obtener clientes
$query_clientes = "SELECT id, nombre, documento FROM clientes ORDER BY nombre";
$result_clientes = $mysqli->query($query_clientes);
$clientes = [];
while ($row = $result_clientes->fetch_assoc()) {
    $clientes[] = $row;
}

// Estadísticas
$total_ventas_count = count($ventas);
$total_ventas_monto = array_sum(array_column($ventas, 'total'));
$venta_promedio = $total_ventas_count > 0 ? $total_ventas_monto / $total_ventas_count : 0;
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>InModa - Ventas</title>
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
            overflow-x: hidden;
            overflow-y: auto;
        }

        .ventas-container {
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
            background: linear-gradient(135deg, #28a745, #1e7e34);
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

        .btn-nueva-venta {
            background: white;
            color: #28a745;
            padding: 12px 25px;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            font-size: 1em;
            font-weight: 600;
            transition: all 0.3s ease;
        }

        .btn-nueva-venta:hover {
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
            border-left: 4px solid var(--color-green);
        }

        .alert.error {
            background: #f8d7da;
            color: #721c24;
            border-left: 4px solid var(--color-red);
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
        }

        .stat-icon.green { background: linear-gradient(135deg, #28a745, #1e7e34); color: white; }
        .stat-icon.blue { background: linear-gradient(135deg, #007bff, #0056b3); color: white; }
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

        .ventas-card {
            background: white;
            padding: 25px;
            border-radius: 15px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.08);
            margin-bottom: 25px;
        }

        .ventas-card h3 {
            margin: 0 0 20px 0;
            color: var(--color-text-black);
            font-size: 1.3em;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .table-container {
            overflow-x: auto;
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        thead {
            background: #f8f9fa;
        }

        thead th {
            padding: 15px;
            text-align: left;
            font-weight: 600;
            color: var(--color-text-black);
            border-bottom: 2px solid #dee2e6;
        }

        tbody td {
            padding: 12px 15px;
            border-bottom: 1px solid #f0f0f0;
        }

        tbody tr:hover {
            background: #f8f9fa;
        }

        .badge {
            padding: 5px 12px;
            border-radius: 20px;
            font-size: 0.85em;
            font-weight: 600;
        }

        .badge.efectivo { background: #d4edda; color: #155724; }
        .badge.tarjeta { background: #cce5ff; color: #004085; }
        .badge.transferencia { background: #fff3cd; color: #856404; }

        .btn-ver-detalle {
            padding: 8px 15px;
            background: #007bff;
            color: white;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            transition: all 0.3s ease;
            display: inline-flex;
            align-items: center;
            gap: 5px;
        }

        .btn-ver-detalle:hover {
            background: #0056b3;
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(0, 123, 255, 0.3);
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
            overflow-y: auto;
        }

        .modal.active {
            display: block;
        }

        .modal-content {
            background-color: white;
            margin: 3% auto;
            padding: 0;
            border-radius: 15px;
            width: 95%;
            max-width: 900px;
            box-shadow: 0 10px 40px rgba(0, 0, 0, 0.3);
            animation: slideIn 0.3s ease;
        }

        @keyframes slideIn {
            from { transform: translateY(-50px); opacity: 0; }
            to { transform: translateY(0); opacity: 1; }
        }

        .modal-header {
            background: linear-gradient(135deg, #28a745, #1e7e34);
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

        .form-group label {
            margin-bottom: 8px;
            color: var(--color-text-black);
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .form-group select,
        .form-group input {
            padding: 12px 15px;
            border: 2px solid var(--color-secondary-grey);
            border-radius: 8px;
            font-size: 1em;
            transition: all 0.3s ease;
        }

        .form-group select:focus,
        .form-group input:focus {
            outline: none;
            border-color: #28a745;
            box-shadow: 0 0 0 3px rgba(40, 167, 69, 0.1);
        }

        .productos-selector {
            margin-bottom: 20px;
            padding: 20px;
            background: #f8f9fa;
            border-radius: 10px;
            overflow-x: auto;
        }

        .productos-selector h4 {
            margin: 0 0 15px 0;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .producto-item {
            display: flex;
            gap: 8px;
            margin-bottom: 10px;
            align-items: center;
            min-width: 700px;
        }

        .producto-item select {
            flex: 2;
            min-width: 250px;
            padding: 10px;
            border: 2px solid var(--color-secondary-grey);
            border-radius: 6px;
            font-size: 0.9em;
        }

        .producto-item input {
            flex: 0.7;
            min-width: 80px;
            padding: 10px;
            border: 2px solid var(--color-secondary-grey);
            border-radius: 6px;
            font-size: 0.9em;
        }

        .producto-item button {
            flex-shrink: 0;
            padding: 10px;
            background: var(--color-red);
            color: white;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            transition: all 0.3s ease;
            width: 40px;
            height: 40px;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .producto-item button:hover {
            background: #c82333;
        }

        .btn-agregar-producto {
            width: 100%;
            padding: 12px;
            background: #28a745;
            color: white;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            font-weight: 600;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
        }

        .btn-agregar-producto:hover {
            background: #218838;
            transform: translateY(-2px);
        }

        .total-venta {
            background: linear-gradient(135deg, #28a745, #1e7e34);
            color: white;
            padding: 20px;
            border-radius: 10px;
            text-align: center;
            margin: 20px 0;
        }

        .total-venta h3 {
            margin: 0 0 10px 0;
            font-size: 1em;
            opacity: 0.9;
        }

        .total-venta p {
            margin: 0;
            font-size: 2.5em;
            font-weight: 700;
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

        /* Modal Detalle */
        .detalle-venta {
            padding: 20px;
        }

        .detalle-header {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 15px;
            margin-bottom: 20px;
            padding: 20px;
            background: #f8f9fa;
            border-radius: 10px;
        }

        .detalle-item {
            display: flex;
            flex-direction: column;
            gap: 5px;
        }

        .detalle-item label {
            font-size: 0.85em;
            color: var(--color-light-grey-text);
            font-weight: 600;
        }

        .detalle-item span {
            font-size: 1.1em;
            color: var(--color-text-black);
            font-weight: 600;
        }

        .productos-detalle {
            margin-top: 20px;
        }

        .productos-detalle h4 {
            margin-bottom: 15px;
            color: var(--color-text-black);
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .producto-detalle-item {
            display: grid;
            grid-template-columns: 2fr 1fr 1fr 1fr;
            gap: 15px;
            padding: 15px;
            background: #f8f9fa;
            border-radius: 8px;
            margin-bottom: 10px;
            align-items: center;
        }

        .producto-detalle-item strong {
            color: var(--color-text-black);
        }

        .producto-detalle-item span {
            color: var(--color-light-grey-text);
        }

        .total-detalle {
            margin-top: 20px;
            padding: 20px;
            background: linear-gradient(135deg, #28a745, #1e7e34);
            color: white;
            border-radius: 10px;
            text-align: right;
        }

        .total-detalle h3 {
            margin: 0 0 5px 0;
            font-size: 1em;
            opacity: 0.9;
        }

        .total-detalle p {
            margin: 0;
            font-size: 2em;
            font-weight: 700;
        }

        .loading {
            text-align: center;
            padding: 40px;
            color: var(--color-light-grey-text);
        }

        .loading i {
            font-size: 3em;
            animation: spin 1s linear infinite;
        }

        @keyframes spin {
            from { transform: rotate(0deg); }
            to { transform: rotate(360deg); }
        }

        /* Responsive */
        @media (max-width: 768px) {
            .ventas-container {
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

            .producto-item {
                flex-wrap: wrap;
                min-width: auto;
            }

            .producto-item select {
                flex: 1 1 100%;
                min-width: 100%;
            }

            .producto-item input {
                flex: 1 1 calc(33.33% - 6px);
                min-width: 0;
            }

            .producto-item button {
                flex: 0 0 40px;
            }

            .producto-detalle-item {
                grid-template-columns: 1fr;
                text-align: left;
            }

            .detalle-header {
                grid-template-columns: 1fr;
            }

            table {
                font-size: 0.9em;
            }

            thead th,
            tbody td {
                padding: 10px;
            }

            .modal-content {
                width: 95%;
                margin: 5% auto;
            }
        }
    </style>
</head>
<body>

<div class="ventas-container">
    <a href="../index.php" class="btn-back">
        <i class="fas fa-arrow-left"></i> Volver al Panel
    </a>

    <div class="page-header">
        <h1><i class="fas fa-shopping-cart"></i> Gestión de Ventas</h1>
        <button class="btn-nueva-venta" onclick="abrirModalVenta()">
            <i class="fas fa-plus-circle"></i> Nueva Venta
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
            <div class="stat-icon green">
                <i class="fas fa-receipt"></i>
            </div>
            <div class="stat-info">
                <h3>Total Ventas</h3>
                <p><?= $total_ventas_count ?></p>
            </div>
        </div>

        <div class="stat-card">
            <div class="stat-icon blue">
                <i class="fas fa-dollar-sign"></i>
            </div>
            <div class="stat-info">
                <h3>Monto Total</h3>
                <p>$<?= number_format($total_ventas_monto, 0, ',', '.') ?></p>
            </div>
        </div>

        <div class="stat-card">
            <div class="stat-icon orange">
                <i class="fas fa-chart-line"></i>
            </div>
            <div class="stat-info">
                <h3>Venta Promedio</h3>
                <p>$<?= number_format($venta_promedio, 0, ',', '.') ?></p>
            </div>
        </div>
    </div>

    <div class="ventas-card">
        <h3><i class="fas fa-list"></i> Historial de Ventas</h3>
        
        <div class="table-container">
            <table>
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Fecha</th>
                        <?php if ($es_admin): ?>
                        <th>Vendedor</th>
                        <?php endif; ?>
                        <th>Cliente</th>
                        <th>Método Pago</th>
                        <th>Total</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($ventas as $venta): ?>
                    <tr>
                        <td>#<?= $venta['id'] ?></td>
                        <td><?= date('d/m/Y H:i', strtotime($venta['fecha'])) ?></td>
                        <?php if ($es_admin): ?>
                        <td><i class="fas fa-user"></i> <?= htmlspecialchars($venta['usuario'] ?? 'N/A') ?></td>
                        <?php endif; ?>
                        <td><?= htmlspecialchars($venta['cliente_nombre'] ?? 'Cliente General') ?></td>
                        <td>
                            <span class="badge <?= strtolower($venta['metodo_pago']) ?>">
                                <?= $venta['metodo_pago'] ?>
                            </span>
                        </td>
                        <td><strong>$<?= number_format($venta['total'], 0, ',', '.') ?></strong></td>
                        <td>
                            <button class="btn-ver-detalle" onclick="verDetalle(<?= $venta['id'] ?>)">
                                <i class="fas fa-eye"></i> Ver Detalle
                            </button>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Modal Nueva Venta -->
<div class="modal" id="modalVenta">
    <div class="modal-content">
        <div class="modal-header">
            <h2><i class="fas fa-cart-plus"></i> Nueva Venta</h2>
            <button class="close-modal" onclick="cerrarModalVenta()">&times;</button>
        </div>

        <form method="POST" action="" id="formVenta" onsubmit="return validarVenta()">
            <div class="form-grid">
                <div class="form-group">
                    <label><i class="fas fa-user"></i> Cliente</label>
                    <select name="cliente_id" id="clienteId">
                        <option value="">Cliente General</option>
                        <?php foreach ($clientes as $cliente): ?>
                        <option value="<?= $cliente['id'] ?>"><?= htmlspecialchars($cliente['nombre']) ?> - <?= $cliente['documento'] ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="form-group">
                    <label><i class="fas fa-credit-card"></i> Método de Pago *</label>
                    <select name="metodo_pago" id="metodoPago" required>
                        <option value="Efectivo">Efectivo</option>
                        <option value="Tarjeta">Tarjeta</option>
                        <option value="Transferencia">Transferencia</option>
                    </select>
                </div>
            </div>

            <div class="productos-selector">
                <h4><i class="fas fa-box"></i> Productos</h4>
                <div id="productosContainer"></div>
                <button type="button" class="btn-agregar-producto" onclick="agregarProducto()">
                    <i class="fas fa-plus"></i> Agregar Producto
                </button>
            </div>

            <div class="total-venta">
                <h3>TOTAL A PAGAR</h3>
                <p id="totalVenta">$0</p>
            </div>

            <input type="hidden" name="productos" id="productosJSON">

            <div class="form-actions">
                <button type="button" class="btn-cancel" onclick="cerrarModalVenta()">
                    <i class="fas fa-times"></i> Cancelar
                </button>
                <button type="submit" class="btn-submit" name="crear_venta">
                    <i class="fas fa-check"></i> Registrar Venta
                </button>
            </div>
        </form>
    </div>
</div>

<!-- Modal Detalle Venta -->
<div class="modal" id="modalDetalle">
    <div class="modal-content">
        <div class="modal-header">
            <h2><i class="fas fa-receipt"></i> Detalle de Venta</h2>
            <button class="close-modal" onclick="cerrarModalDetalle()">&times;</button>
        </div>

        <div class="detalle-venta" id="detalleVentaContent">
            <div class="loading">
                <i class="fas fa-spinner"></i>
                <p>Cargando detalles...</p>
            </div>
        </div>
    </div>
</div>

<script>
const productosDisponibles = <?= json_encode($productos) ?>;
let productosVenta = [];

// Modal Venta
function abrirModalVenta() {
    productosVenta = [];
    document.getElementById('formVenta').reset();
    document.getElementById('productosContainer').innerHTML = '';
    document.getElementById('totalVenta').textContent = '$0';
    agregarProducto();
    document.getElementById('modalVenta').classList.add('active');
}

function cerrarModalVenta() {
    document.getElementById('modalVenta').classList.remove('active');
}

function agregarProducto() {
    const index = productosVenta.length;
    const div = document.createElement('div');
    div.className = 'producto-item';
    div.id = `producto-${index}`;
    
    div.innerHTML = `
        <select onchange="actualizarPrecio(${index})" id="producto-select-${index}" required>
            <option value="">Seleccionar producto...</option>
            ${productosDisponibles.map(p => `<option value="${p.id}" data-precio="${p.precio_venta}" data-stock="${p.stock}">${p.nombre} - Stock: ${p.stock}</option>`).join('')}
        </select>
        <input type="number" placeholder="Cantidad" min="1" id="cantidad-${index}" onchange="calcularSubtotal(${index})" required>
        <input type="number" placeholder="Precio" id="precio-${index}" readonly>
        <input type="number" placeholder="Subtotal" id="subtotal-${index}" readonly>
        <button type="button" onclick="eliminarProducto(${index})"><i class="fas fa-trash"></i></button>
    `;
    
    document.getElementById('productosContainer').appendChild(div);
    productosVenta.push({ id: null, cantidad: 0, precio: 0, subtotal: 0 });
}

function actualizarPrecio(index) {
    const select = document.getElementById(`producto-select-${index}`);
    const option = select.options[select.selectedIndex];
    const precio = parseFloat(option.dataset.precio || 0);
    
    document.getElementById(`precio-${index}`).value = precio;
    productosVenta[index].id = select.value;
    productosVenta[index].precio = precio;
    
    calcularSubtotal(index);
}

function calcularSubtotal(index) {
    const cantidad = parseInt(document.getElementById(`cantidad-${index}`).value || 0);
    const precio = parseFloat(document.getElementById(`precio-${index}`).value || 0);
    const subtotal = cantidad * precio;
    
    document.getElementById(`subtotal-${index}`).value = subtotal;
    productosVenta[index].cantidad = cantidad;
    productosVenta[index].subtotal = subtotal;
    
    calcularTotal();
}

function calcularTotal() {
    const total = productosVenta.reduce((sum, p) => sum + p.subtotal, 0);
    document.getElementById('totalVenta').textContent = '$' + total.toLocaleString('es-CO');
}

function eliminarProducto(index) {
    document.getElementById(`producto-${index}`).remove();
    productosVenta[index] = { id: null, cantidad: 0, precio: 0, subtotal: 0 };
    calcularTotal();
}

function validarVenta() {
    const productosValidos = productosVenta.filter(p => p.id && p.cantidad > 0);
    
    if (productosValidos.length === 0) {
        alert('⚠️ Debes agregar al menos un producto a la venta');
        return false;
    }
    
    document.getElementById('productosJSON').value = JSON.stringify(productosValidos);
    return true;
}

function verDetalle(ventaId) {
    document.getElementById('modalDetalle').classList.add('active');
    document.getElementById('detalleVentaContent').innerHTML = `
        <div class="loading">
            <i class="fas fa-spinner"></i>
            <p>Cargando detalles...</p>
        </div>
    `;
    
    // Hacer petición AJAX para obtener el detalle
    fetch(`obtener_detalle_venta.php?id=${ventaId}`)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                mostrarDetalle(data.venta, data.productos);
            } else {
                document.getElementById('detalleVentaContent').innerHTML = `
                    <div style="text-align: center; padding: 40px; color: var(--color-red);">
                        <i class="fas fa-exclamation-circle" style="font-size: 3em; margin-bottom: 15px;"></i>
                        <p>Error al cargar el detalle de la venta</p>
                    </div>
                `;
            }
        })
        .catch(error => {
            console.error('Error:', error);
            document.getElementById('detalleVentaContent').innerHTML = `
                <div style="text-align: center; padding: 40px; color: var(--color-red);">
                    <i class="fas fa-exclamation-circle" style="font-size: 3em; margin-bottom: 15px;"></i>
                    <p>Error al cargar el detalle de la venta</p>
                </div>
            `;
        });
}

function mostrarDetalle(venta, productos) {
    const html = `
        <div class="detalle-header">
            <div class="detalle-item">
                <label><i class="fas fa-hashtag"></i> ID de Venta</label>
                <span>#${venta.id}</span>
            </div>
            <div class="detalle-item">
                <label><i class="fas fa-calendar"></i> Fecha</label>
                <span>${venta.fecha}</span>
            </div>
            <div class="detalle-item">
                <label><i class="fas fa-user"></i> Cliente</label>
                <span>${venta.cliente || 'Cliente General'}</span>
            </div>
            <div class="detalle-item">
                <label><i class="fas fa-user-tie"></i> Vendedor</label>
                <span>${venta.vendedor || 'N/A'}</span>
            </div>
            <div class="detalle-item">
                <label><i class="fas fa-credit-card"></i> Método de Pago</label>
                <span class="badge ${venta.metodo_pago.toLowerCase()}">${venta.metodo_pago}</span>
            </div>
        </div>

        <div class="productos-detalle">
            <h4><i class="fas fa-box"></i> Productos Vendidos</h4>
            ${productos.map(prod => `
                <div class="producto-detalle-item">
                    <div>
                        <strong>${prod.nombre}</strong>
                        <br><span>Código: ${prod.codigo}</span>
                    </div>
                    <div style="text-align: center;">
                        <span>Cantidad</span>
                        <br><strong>${prod.cantidad}</strong>
                    </div>
                    <div style="text-align: center;">
                        <span>Precio Unit.</span>
                        <br><strong>$${formatNumber(prod.precio_unitario)}</strong>
                    </div>
                    <div style="text-align: right;">
                        <span>Subtotal</span>
                        <br><strong>$${formatNumber(prod.subtotal)}</strong>
                    </div>
                </div>
            `).join('')}
        </div>

        <div class="total-detalle">
            <h3>TOTAL DE LA VENTA</h3>
            <p>$${formatNumber(venta.total)}</p>
        </div>
    `;
    
    document.getElementById('detalleVentaContent').innerHTML = html;
}

function cerrarModalDetalle() {
    document.getElementById('modalDetalle').classList.remove('active');
}

function formatNumber(num) {
    return parseFloat(num).toLocaleString('es-CO', { minimumFractionDigits: 0, maximumFractionDigits: 0 });
}

// Cerrar modal al hacer clic fuera
window.onclick = function(event) {
    if (event.target == document.getElementById('modalVenta')) {
        cerrarModalVenta();
    }
    if (event.target == document.getElementById('modalDetalle')) {
        cerrarModalDetalle();
    }
}
</script>

</body>
</html>