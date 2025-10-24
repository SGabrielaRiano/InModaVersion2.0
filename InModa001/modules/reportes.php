<?php
session_start();

// Verificar sesión activa
if (!isset($_SESSION['user_id'])) {
    header("Location: ../login.php");
    exit();
}

// SOLO ADMINISTRADOR puede ver reportes
if ($_SESSION['rol'] !== 'Administrador') {
    $_SESSION['mensaje_error'] = "No tienes permisos para acceder a esta sección";
    header("Location: ../index.php");
    exit();
}

require_once "../config/conexion.php";

$usuario = $_SESSION['usuario'] ?? 'Admin';
$rol = $_SESSION['rol'] ?? 'Administrador';
$empresa = $_SESSION['empresa'] ?? 'InModa';

// Procesamiento de filtros y períodos predefinidos
$periodo = $_GET['periodo'] ?? 'mes';
$fecha_inicio = $_GET['fecha_inicio'] ?? '';
$fecha_fin = $_GET['fecha_fin'] ?? '';

// Calcular fechas según el período seleccionado
if (empty($fecha_inicio) || empty($fecha_fin)) {
    switch ($periodo) {
        case 'hoy':
            $fecha_inicio = date('Y-m-d');
            $fecha_fin = date('Y-m-d');
            break;
        case 'ayer':
            $fecha_inicio = date('Y-m-d', strtotime('-1 day'));
            $fecha_fin = date('Y-m-d', strtotime('-1 day'));
            break;
        case 'semana':
            $fecha_inicio = date('Y-m-d', strtotime('monday this week'));
            $fecha_fin = date('Y-m-d');
            break;
        case 'mes':
        default:
            $fecha_inicio = date('Y-m-01');
            $fecha_fin = date('Y-m-d');
            break;
        case 'trimestre':
            $mes_actual = date('n');
            $mes_inicio = (floor(($mes_actual - 1) / 3) * 3) + 1;
            $fecha_inicio = date('Y-' . str_pad($mes_inicio, 2, '0', STR_PAD_LEFT) . '-01');
            $fecha_fin = date('Y-m-d');
            break;
        case 'ano':
            $fecha_inicio = date('Y-01-01');
            $fecha_fin = date('Y-m-d');
            break;
    }
}

$tipo_reporte = $_GET['tipo'] ?? 'general';

// Total de ventas en el período
$query_ventas = "SELECT COUNT(*) as total_ventas, SUM(total) as monto_total 
                 FROM ventas 
                 WHERE fecha BETWEEN ? AND ?";
$stmt = $mysqli->prepare($query_ventas);
$fecha_inicio_full = $fecha_inicio . ' 00:00:00';
$fecha_fin_full = $fecha_fin . ' 23:59:59';
$stmt->bind_param("ss", $fecha_inicio_full, $fecha_fin_full);
$stmt->execute();
$result = $stmt->get_result();
$result_ventas = $result->fetch_assoc();
$total_ventas = $result_ventas['total_ventas'] ?? 0;
$monto_total = $result_ventas['monto_total'] ?? 0;
$stmt->close();

// Productos más vendidos
$query_productos = "SELECT p.nombre, p.codigo, SUM(dv.cantidad) as cantidad_vendida, 
                    SUM(dv.subtotal) as ingresos
                    FROM detalle_venta dv
                    INNER JOIN productos p ON dv.producto_id = p.id
                    INNER JOIN ventas v ON dv.venta_id = v.id
                    WHERE v.fecha BETWEEN ? AND ?
                    GROUP BY dv.producto_id
                    ORDER BY cantidad_vendida DESC
                    LIMIT 10";
$stmt = $mysqli->prepare($query_productos);
$stmt->bind_param("ss", $fecha_inicio_full, $fecha_fin_full);
$stmt->execute();
$result_productos = $stmt->get_result();
$productos_vendidos = [];
while ($row = $result_productos->fetch_assoc()) {
    $productos_vendidos[] = $row;
}
$stmt->close();

// Ventas por día
$query_ventas_dia = "SELECT DATE(fecha) as fecha, COUNT(*) as num_ventas, SUM(total) as monto
                     FROM ventas
                     WHERE fecha BETWEEN ? AND ?
                     GROUP BY DATE(fecha)
                     ORDER BY fecha ASC";
$stmt = $mysqli->prepare($query_ventas_dia);
$stmt->bind_param("ss", $fecha_inicio_full, $fecha_fin_full);
$stmt->execute();
$result_dia = $stmt->get_result();
$ventas_por_dia = [];
while ($row = $result_dia->fetch_assoc()) {
    $ventas_por_dia[] = $row;
}
$stmt->close();

// Ventas por usuario
$query_usuarios = "SELECT u.usuario, COUNT(v.id) as num_ventas, SUM(v.total) as monto_total
                   FROM ventas v
                   LEFT JOIN usuarios u ON v.usuario_id = u.id
                   WHERE v.fecha BETWEEN ? AND ?
                   GROUP BY v.usuario_id
                   ORDER BY monto_total DESC";
$stmt = $mysqli->prepare($query_usuarios);
$stmt->bind_param("ss", $fecha_inicio_full, $fecha_fin_full);
$stmt->execute();
$result_usuarios = $stmt->get_result();
$ventas_por_usuario = [];
while ($row = $result_usuarios->fetch_assoc()) {
    $ventas_por_usuario[] = $row;
}
$stmt->close();

// Productos con bajo stock
$query_stock = "SELECT nombre, codigo, stock, stock_minimo 
                FROM productos 
                WHERE stock < stock_minimo 
                ORDER BY stock ASC 
                LIMIT 10";
$result_stock = $mysqli->query($query_stock);
$productos_stock_bajo = [];
while ($row = $result_stock->fetch_assoc()) {
    $productos_stock_bajo[] = $row;
}

// Total de clientes
$total_clientes = $mysqli->query("SELECT COUNT(*) as total FROM clientes")->fetch_assoc()['total'];

// Total de productos
$total_productos = $mysqli->query("SELECT COUNT(*) as total FROM productos")->fetch_assoc()['total'];

// Valor del inventario
$valor_inventario = $mysqli->query("SELECT SUM(stock * precio_venta) as valor FROM productos")->fetch_assoc()['valor'] ?? 0;

// Calcular promedio de venta
$promedio_venta = $total_ventas > 0 ? $monto_total / $total_ventas : 0;

// Análisis de tendencias
$tendencia = "estable";
$porcentaje_cambio = 0;
if (count($ventas_por_dia) >= 2) {
    $primera_mitad = array_slice($ventas_por_dia, 0, ceil(count($ventas_por_dia) / 2));
    $segunda_mitad = array_slice($ventas_por_dia, ceil(count($ventas_por_dia) / 2));
    
    $promedio_primera = array_sum(array_column($primera_mitad, 'monto')) / max(count($primera_mitad), 1);
    $promedio_segunda = array_sum(array_column($segunda_mitad, 'monto')) / max(count($segunda_mitad), 1);
    
    if ($promedio_primera > 0) {
        $porcentaje_cambio = (($promedio_segunda - $promedio_primera) / $promedio_primera) * 100;
    }
    
    if ($promedio_segunda > $promedio_primera * 1.1) {
        $tendencia = "creciente";
    } elseif ($promedio_segunda < $promedio_primera * 0.9) {
        $tendencia = "decreciente";
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>InModa - Reportes</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf-autotable/3.5.31/jspdf.plugin.autotable.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/xlsx/0.18.5/xlsx.full.min.js"></script>
    <style>
        :root {
            --color-primary-blue: #007bff;
            --color-background-main: #f5f5dc;
            --color-secondary-grey: #d3d3d3;
            --color-text-black: #000000;
            --color-white: #FFFFFF;
            --color-light-grey-text: #666666;
            --color-green: #28a745;
            --color-red: #dc3545;
            --color-orange: #fd7e14;
            --color-purple: #6f42c1;
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

        .reportes-container {
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
            background: #bfbfbf;
        }

        .page-header {
            background: linear-gradient(135deg, var(--color-primary-blue), #0056b3);
            color: white;
            padding: 25px 30px;
            border-radius: 15px;
            margin-bottom: 30px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.15);
        }

        .page-header h1 {
            margin: 0 0 10px 0;
            font-size: 2em;
            display: flex;
            align-items: center;
            gap: 15px;
        }

        .filters-section {
            background: white;
            padding: 25px;
            border-radius: 15px;
            margin-bottom: 25px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.08);
        }

        .periodo-buttons {
            display: flex;
            gap: 10px;
            margin-bottom: 20px;
            flex-wrap: wrap;
        }

        .btn-periodo {
            padding: 10px 20px;
            border: 2px solid var(--color-primary-blue);
            background: white;
            color: var(--color-primary-blue);
            border-radius: 8px;
            cursor: pointer;
            font-weight: 600;
            transition: all 0.3s ease;
        }

        .btn-periodo:hover, .btn-periodo.active {
            background: var(--color-primary-blue);
            color: white;
        }

        .filters-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
            margin-bottom: 20px;
        }

        .filter-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
            color: var(--color-text-black);
        }

        .filter-group input, .filter-group select {
            width: 100%;
            padding: 10px;
            border: 2px solid var(--color-secondary-grey);
            border-radius: 8px;
            font-size: 0.95em;
        }

        .btn-filter {
            background: var(--color-primary-blue);
            color: white;
            padding: 12px 30px;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            font-size: 1em;
            font-weight: 600;
            transition: all 0.3s ease;
        }

        .btn-filter:hover {
            background: #0056b3;
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(0,123,255,0.3);
        }

        .btn-export {
            background: var(--color-green);
            color: white;
            padding: 10px 20px;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            font-size: 0.95em;
            transition: all 0.3s ease;
            margin-left: 10px;
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }

        .btn-export:hover {
            background: #1e7e34;
            transform: translateY(-2px);
        }

        .btn-export.excel {
            background: #217346;
        }

        .btn-export.excel:hover {
            background: #1a5c37;
        }

        .analisis-card {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 25px;
            border-radius: 15px;
            margin-bottom: 25px;
            box-shadow: 0 5px 20px rgba(102, 126, 234, 0.3);
        }

        .analisis-card h3 {
            margin: 0 0 15px 0;
            font-size: 1.4em;
        }

        .analisis-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
        }

        .analisis-item {
            background: rgba(255, 255, 255, 0.2);
            padding: 15px;
            border-radius: 10px;
        }

        .analisis-item h4 {
            margin: 0 0 5px 0;
            font-size: 0.9em;
            opacity: 0.9;
        }

        .analisis-item p {
            margin: 0;
            font-size: 1.5em;
            font-weight: 700;
        }

        .tendencia {
            display: inline-flex;
            align-items: center;
            gap: 5px;
            padding: 5px 15px;
            border-radius: 20px;
            font-weight: 600;
            font-size: 0.9em;
        }

        .tendencia.creciente {
            background: rgba(40, 167, 69, 0.3);
        }

        .tendencia.decreciente {
            background: rgba(220, 53, 69, 0.3);
        }

        .tendencia.estable {
            background: rgba(255, 193, 7, 0.3);
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

        .stat-icon.blue { background: linear-gradient(135deg, #007bff, #0056b3); color: white; }
        .stat-icon.green { background: linear-gradient(135deg, #28a745, #1e7e34); color: white; }
        .stat-icon.orange { background: linear-gradient(135deg, #fd7e14, #dc6502); color: white; }
        .stat-icon.purple { background: linear-gradient(135deg, #6f42c1, #5a32a3); color: white; }

        .stat-info h3 {
            margin: 0 0 5px 0;
            color: var(--color-light-grey-text);
            font-size: 0.9em;
            font-weight: 500;
        }

        .stat-info p {
            margin: 0;
            font-size: 1.8em;
            font-weight: 700;
            color: var(--color-text-black);
        }

        .charts-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(450px, 1fr));
            gap: 25px;
            margin-bottom: 30px;
        }

        .chart-card {
            background: white;
            padding: 25px;
            border-radius: 15px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.08);
        }

        .chart-card h3 {
            margin: 0 0 20px 0;
            font-size: 1.3em;
            color: var(--color-text-black);
        }

        .chart-container {
            position: relative;
            height: 300px;
        }

        .table-card {
            background: white;
            padding: 25px;
            border-radius: 15px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.08);
            margin-bottom: 25px;
        }

        .table-card h3 {
            margin: 0 0 20px 0;
            font-size: 1.3em;
            color: var(--color-text-black);
        }

        .data-table {
            width: 100%;
            border-collapse: collapse;
        }

        .data-table th {
            background: var(--color-background-main);
            padding: 12px;
            text-align: left;
            font-weight: 600;
            border-bottom: 2px solid var(--color-secondary-grey);
        }

        .data-table td {
            padding: 12px;
            border-bottom: 1px solid #eee;
        }

        .data-table tbody tr:hover {
            background: var(--color-background-main);
        }

        .badge {
            padding: 5px 12px;
            border-radius: 20px;
            font-size: 0.85em;
            font-weight: 600;
        }

        .badge.danger { background: #ffe0e0; color: #dc3545; }
        .badge.warning { background: #fff3cd; color: #fd7e14; }
        .badge.success { background: #d4edda; color: #28a745; }

        .no-data {
            text-align: center;
            padding: 40px;
            color: var(--color-light-grey-text);
        }

        .no-data i {
            font-size: 3em;
            margin-bottom: 15px;
            opacity: 0.3;
        }

        @media (max-width: 768px) {
            .reportes-container {
                padding: 15px;
            }

            .charts-grid {
                grid-template-columns: 1fr;
            }

            .filters-grid {
                grid-template-columns: 1fr;
            }

            .periodo-buttons {
                flex-direction: column;
            }

            .btn-periodo, .btn-export {
                width: 100%;
                margin-left: 0;
                margin-top: 10px;
            }
        }
    </style>
</head>
<body>

<div class="reportes-container">
    <a href="../index.php" class="btn-back">
        <i class="fas fa-arrow-left"></i> Volver al Panel
    </a>

    <div class="page-header">
        <h1><i class="fas fa-chart-pie"></i> Reportes y Análisis</h1>
        <p style="margin: 0; opacity: 0.9;">Información detallada del negocio - Período: <?= date('d/m/Y', strtotime($fecha_inicio)) ?> al <?= date('d/m/Y', strtotime($fecha_fin)) ?></p>
    </div>

    <!-- Análisis Inteligente -->
    <div class="analisis-card">
        <h3><i class="fas fa-brain"></i> Análisis Inteligente</h3>
        <div class="analisis-grid">
            <div class="analisis-item">
                <h4>Promedio por Venta</h4>
                <p>$<?= number_format($promedio_venta, 0, ',', '.') ?></p>
            </div>
            <div class="analisis-item">
                <h4>Tendencia</h4>
                <p>
                    <span class="tendencia <?= $tendencia ?>">
                        <i class="fas fa-<?= $tendencia == 'creciente' ? 'arrow-up' : ($tendencia == 'decreciente' ? 'arrow-down' : 'minus') ?>"></i>
                        <?= ucfirst($tendencia) ?>
                        <?php if ($porcentaje_cambio != 0): ?>
                        (<?= number_format(abs($porcentaje_cambio), 1) ?>%)
                        <?php endif; ?>
                    </span>
                </p>
            </div>
            <div class="analisis-item">
                <h4>Días Analizados</h4>
                <p><?= count($ventas_por_dia) ?> días</p>
            </div>
            <div class="analisis-item">
                <h4>Productos Vendidos</h4>
                <p><?= array_sum(array_column($productos_vendidos, 'cantidad_vendida')) ?> unidades</p>
            </div>
        </div>
    </div>

    <!-- Filtros -->
    <div class="filters-section">
        <h3 style="margin: 0 0 15px 0;"><i class="fas fa-filter"></i> Filtros de Reporte</h3>
        
        <div class="periodo-buttons">
            <button class="btn-periodo <?= $periodo == 'hoy' ? 'active' : '' ?>" onclick="window.location.href='?periodo=hoy'">
                <i class="fas fa-calendar-day"></i> Hoy
            </button>
            <button class="btn-periodo <?= $periodo == 'ayer' ? 'active' : '' ?>" onclick="window.location.href='?periodo=ayer'">
                <i class="fas fa-calendar-minus"></i> Ayer
            </button>
            <button class="btn-periodo <?= $periodo == 'semana' ? 'active' : '' ?>" onclick="window.location.href='?periodo=semana'">
                <i class="fas fa-calendar-week"></i> Esta Semana
            </button>
            <button class="btn-periodo <?= $periodo == 'mes' ? 'active' : '' ?>" onclick="window.location.href='?periodo=mes'">
                <i class="fas fa-calendar-alt"></i> Este Mes
            </button>
            <button class="btn-periodo <?= $periodo == 'trimestre' ? 'active' : '' ?>" onclick="window.location.href='?periodo=trimestre'">
                <i class="fas fa-calendar"></i> Trimestre
            </button>
            <button class="btn-periodo <?= $periodo == 'ano' ? 'active' : '' ?>" onclick="window.location.href='?periodo=ano'">
                <i class="fas fa-calendar-check"></i> Este Año
            </button>
        </div>

        <form method="GET" action="" id="filterForm">
            <div class="filters-grid">
                <div class="filter-group">
                    <label><i class="fas fa-calendar-alt"></i> Fecha Inicio</label>
                    <input type="date" name="fecha_inicio" value="<?= $fecha_inicio ?>" required>
                </div>
                <div class="filter-group">
                    <label><i class="fas fa-calendar-alt"></i> Fecha Fin</label>
                    <input type="date" name="fecha_fin" value="<?= $fecha_fin ?>" required>
                </div>
                <div class="filter-group">
                    <label><i class="fas fa-filter"></i> Tipo de Reporte</label>
                    <select name="tipo">
                        <option value="general" <?= $tipo_reporte == 'general' ? 'selected' : '' ?>>General</option>
                        <option value="ventas" <?= $tipo_reporte == 'ventas' ? 'selected' : '' ?>>Ventas</option>
                        <option value="productos" <?= $tipo_reporte == 'productos' ? 'selected' : '' ?>>Productos</option>
                        <option value="inventario" <?= $tipo_reporte == 'inventario' ? 'selected' : '' ?>>Inventario</option>
                    </select>
                </div>
            </div>
            <button type="submit" class="btn-filter"><i class="fas fa-search"></i> Generar Reporte Personalizado</button>
            <button type="button" class="btn-export" onclick="exportarPDF()"><i class="fas fa-file-pdf"></i> Exportar PDF</button>
            <button type="button" class="btn-export excel" onclick="exportarExcel()"><i class="fas fa-file-excel"></i> Exportar Excel</button>
        </form>
    </div>

    <!-- Estadísticas Principales -->
    <div class="stats-grid">
        <div class="stat-card">
            <div class="stat-icon blue">
                <i class="fas fa-dollar-sign"></i>
            </div>
            <div class="stat-info">
                <h3>Ventas Totales</h3>
                <p>$<?= number_format($monto_total, 0, ',', '.') ?></p>
            </div>
        </div>

        <div class="stat-card">
            <div class="stat-icon green">
                <i class="fas fa-shopping-cart"></i>
            </div>
            <div class="stat-info">
                <h3>Número de Ventas</h3>
                <p><?= $total_ventas ?></p>
            </div>
        </div>

        <div class="stat-card">
            <div class="stat-icon orange">
                <i class="fas fa-box-open"></i>
            </div>
            <div class="stat-info">
                <h3>Total Productos</h3>
                <p><?= $total_productos ?></p>
            </div>
        </div>

        <div class="stat-card">
            <div class="stat-icon purple">
                <i class="fas fa-warehouse"></i>
            </div>
            <div class="stat-info">
                <h3>Valor Inventario</h3>
                <p>$<?= number_format($valor_inventario, 0, ',', '.') ?></p>
            </div>
        </div>
    </div>

    <!-- Gráficos -->
    <div class="charts-grid">
        <div class="chart-card">
            <h3><i class="fas fa-chart-line"></i> Ventas por Día</h3>
            <div class="chart-container">
                <canvas id="ventasDiaChart"></canvas>
            </div>
        </div>

        <div class="chart-card">
            <h3><i class="fas fa-chart-bar"></i> Top 5 Productos</h3>
            <div class="chart-container">
                <canvas id="productosChart"></canvas>
            </div>
        </div>
    </div>

    <!-- Tabla Productos Más Vendidos -->
    <div class="table-card">
        <h3><i class="fas fa-trophy"></i> Productos Más Vendidos</h3>
        <?php if (count($productos_vendidos) > 0): ?>
        <table class="data-table" id="productosTable">
            <thead>
                <tr>
                    <th>#</th>
                    <th>Código</th>
                    <th>Producto</th>
                    <th>Cantidad Vendida</th>
                    <th>Ingresos</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($productos_vendidos as $index => $producto): ?>
                <tr>
                    <td><?= $index + 1 ?></td>
                    <td><?= htmlspecialchars($producto['codigo']) ?></td>
                    <td><?= htmlspecialchars($producto['nombre']) ?></td>
                    <td><span class="badge success"><?= $producto['cantidad_vendida'] ?> unidades</span></td>
                    <td><strong>$<?= number_format($producto['ingresos'], 0, ',', '.') ?></strong></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        <?php else: ?>
        <div class="no-data">
            <i class="fas fa-inbox"></i>
            <p>No hay datos de ventas en este período</p>
        </div>
        <?php endif; ?>
    </div>

    <!-- Tabla Ventas por Usuario -->
    <div class="table-card">
        <h3><i class="fas fa-users"></i> Ventas por Usuario</h3>
        <?php if (count($ventas_por_usuario) > 0): ?>
        <table class="data-table" id="usuariosTable">
            <thead>
                <tr>
                    <th>Usuario</th>
                    <th>Número de Ventas</th>
                    <th>Monto Total</th>
                    <th>Promedio por Venta</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($ventas_por_usuario as $user): ?>
                <tr>
                    <td><i class="fas fa-user"></i> <?= htmlspecialchars($user['usuario'] ?? 'Sin asignar') ?></td>
                    <td><?= $user['num_ventas'] ?> ventas</td>
                    <td><strong>$<?= number_format($user['monto_total'], 0, ',', '.') ?></strong></td>
                    <td>$<?= number_format($user['monto_total'] / $user['num_ventas'], 0, ',', '.') ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        <?php else: ?>
        <div class="no-data">
            <i class="fas fa-inbox"></i>
            <p>No hay datos de ventas por usuario</p>
        </div>
        <?php endif; ?>
    </div>

    <!-- Tabla Stock Bajo -->
    <div class="table-card">
        <h3><i class="fas fa-exclamation-triangle"></i> Productos con Stock Bajo</h3>
        <?php if (count($productos_stock_bajo) > 0): ?>
        <table class="data-table" id="stockTable">
            <thead>
                <tr>
                    <th>Código</th>
                    <th>Producto</th>
                    <th>Stock Actual</th>
                    <th>Stock Mínimo</th>
                    <th>Estado</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($productos_stock_bajo as $producto): ?>
                <tr>
                    <td><?= htmlspecialchars($producto['codigo']) ?></td>
                    <td><?= htmlspecialchars($producto['nombre']) ?></td>
                    <td><?= $producto['stock'] ?></td>
                    <td><?= $producto['stock_minimo'] ?></td>
                    <td>
                        <?php if ($producto['stock'] == 0): ?>
                            <span class="badge danger">Sin Stock</span>
                        <?php else: ?>
                            <span class="badge warning">Stock Bajo</span>
                        <?php endif; ?>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        <?php else: ?>
        <div class="no-data">
            <i class="fas fa-check-circle" style="color: var(--color-green);"></i>
            <p>Todos los productos tienen stock adecuado</p>
        </div>
        <?php endif; ?>
    </div>
</div>

<script>
// Datos PHP para JavaScript
const datosReporte = {
    empresa: '<?= $empresa ?>',
    periodo: '<?= date('d/m/Y', strtotime($fecha_inicio)) ?> al <?= date('d/m/Y', strtotime($fecha_fin)) ?>',
    total_ventas: <?= $total_ventas ?>,
    monto_total: <?= $monto_total ?>,
    promedio_venta: <?= $promedio_venta ?>,
    tendencia: '<?= $tendencia ?>',
    productos_vendidos: <?= json_encode($productos_vendidos) ?>,
    ventas_por_dia: <?= json_encode($ventas_por_dia) ?>,
    ventas_por_usuario: <?= json_encode($ventas_por_usuario) ?>,
    productos_stock_bajo: <?= json_encode($productos_stock_bajo) ?>
};

// Gráfico Ventas por Día
const ventasDiaCtx = document.getElementById('ventasDiaChart');
const ventasDiaData = datosReporte.ventas_por_dia;

new Chart(ventasDiaCtx, {
    type: 'line',
    data: {
        labels: ventasDiaData.map(item => item.fecha),
        datasets: [{
            label: 'Monto de Ventas',
            data: ventasDiaData.map(item => parseFloat(item.monto)),
            borderColor: '#007bff',
            backgroundColor: 'rgba(0, 123, 255, 0.1)',
            borderWidth: 3,
            fill: true,
            tension: 0.4
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
            legend: { display: true }
        },
        scales: {
            y: {
                beginAtZero: true,
                ticks: {
                    callback: value => '$' + value.toLocaleString()
                }
            }
        }
    }
});

// Gráfico Top Productos
const productosCtx = document.getElementById('productosChart');
const productosData = datosReporte.productos_vendidos.slice(0, 5);

new Chart(productosCtx, {
    type: 'bar',
    data: {
        labels: productosData.map(item => item.nombre),
        datasets: [{
            label: 'Cantidad Vendida',
            data: productosData.map(item => parseInt(item.cantidad_vendida)),
            backgroundColor: [
                'rgba(0, 123, 255, 0.8)',
                'rgba(40, 167, 69, 0.8)',
                'rgba(255, 193, 7, 0.8)',
                'rgba(253, 126, 20, 0.8)',
                'rgba(111, 66, 193, 0.8)'
            ],
            borderWidth: 0
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
            legend: { display: false }
        },
        scales: {
            y: { beginAtZero: true }
        }
    }
});

// Función para exportar a PDF
function exportarPDF() {
    const { jsPDF } = window.jspdf;
    const doc = new jsPDF();
    
    // Título
    doc.setFontSize(20);
    doc.setTextColor(0, 123, 255);
    doc.text(`Reporte - ${datosReporte.empresa}`, 105, 20, { align: 'center' });
    
    // Período
    doc.setFontSize(12);
    doc.setTextColor(0, 0, 0);
    doc.text(`Período: ${datosReporte.periodo}`, 105, 30, { align: 'center' });
    
    // Resumen Ejecutivo
    doc.setFontSize(14);
    doc.setTextColor(0, 123, 255);
    doc.text('Resumen Ejecutivo', 14, 45);
    
    doc.setFontSize(11);
    doc.setTextColor(0, 0, 0);
    let y = 55;
    doc.text(`Total de Ventas: ${datosReporte.total_ventas}`, 14, y);
    y += 7;
    doc.text(`Monto Total: $${datosReporte.monto_total.toLocaleString('es-CO')}`, 14, y);
    y += 7;
    doc.text(`Promedio por Venta: $${Math.round(datosReporte.promedio_venta).toLocaleString('es-CO')}`, 14, y);
    y += 7;
    doc.text(`Tendencia: ${datosReporte.tendencia.toUpperCase()}`, 14, y);
    
    // Productos Más Vendidos
    y += 15;
    doc.setFontSize(14);
    doc.setTextColor(0, 123, 255);
    doc.text('Top 5 Productos Más Vendidos', 14, y);
    
    y += 5;
    const productosTableData = datosReporte.productos_vendidos.slice(0, 5).map((p, i) => [
        i + 1,
        p.codigo,
        p.nombre,
        p.cantidad_vendida,
        `$${parseFloat(p.ingresos).toLocaleString('es-CO')}`
    ]);
    
    doc.autoTable({
        startY: y,
        head: [['#', 'Código', 'Producto', 'Cantidad', 'Ingresos']],
        body: productosTableData,
        theme: 'grid',
        headStyles: { fillColor: [0, 123, 255] }
    });
    
    // Ventas por Usuario
    y = doc.lastAutoTable.finalY + 10;
    doc.setFontSize(14);
    doc.setTextColor(0, 123, 255);
    doc.text('Ventas por Usuario', 14, y);
    
    y += 5;
    const usuariosTableData = datosReporte.ventas_por_usuario.map(u => [
        u.usuario || 'Sin asignar',
        u.num_ventas,
        `$${parseFloat(u.monto_total).toLocaleString('es-CO')}`,
        `$${Math.round(u.monto_total / u.num_ventas).toLocaleString('es-CO')}`
    ]);
    
    doc.autoTable({
        startY: y,
        head: [['Usuario', 'N° Ventas', 'Monto Total', 'Promedio']],
        body: usuariosTableData,
        theme: 'grid',
        headStyles: { fillColor: [0, 123, 255] }
    });
    
    // Guardar PDF
    doc.save(`Reporte_${datosReporte.empresa}_${new Date().toISOString().split('T')[0]}.pdf`);
}

// Función para exportar a Excel
function exportarExcel() {
    const wb = XLSX.utils.book_new();
    
    // Hoja 1: Resumen
    const resumen = [
        ['REPORTE DE VENTAS - ' + datosReporte.empresa],
        ['Período: ' + datosReporte.periodo],
        [],
        ['RESUMEN EJECUTIVO'],
        ['Total de Ventas', datosReporte.total_ventas],
        ['Monto Total', '$' + datosReporte.monto_total.toLocaleString('es-CO')],
        ['Promedio por Venta', '$' + Math.round(datosReporte.promedio_venta).toLocaleString('es-CO')],
        ['Tendencia', datosReporte.tendencia.toUpperCase()],
    ];
    const ws1 = XLSX.utils.aoa_to_sheet(resumen);
    XLSX.utils.book_append_sheet(wb, ws1, 'Resumen');
    
    // Hoja 2: Productos Más Vendidos
    const productosData = [
        ['#', 'Código', 'Producto', 'Cantidad Vendida', 'Ingresos'],
        ...datosReporte.productos_vendidos.map((p, i) => [
            i + 1,
            p.codigo,
            p.nombre,
            p.cantidad_vendida,
            parseFloat(p.ingresos)
        ])
    ];
    const ws2 = XLSX.utils.aoa_to_sheet(productosData);
    XLSX.utils.book_append_sheet(wb, ws2, 'Productos');
    
    // Hoja 3: Ventas por Usuario
    const usuariosData = [
        ['Usuario', 'Número de Ventas', 'Monto Total', 'Promedio por Venta'],
        ...datosReporte.ventas_por_usuario.map(u => [
            u.usuario || 'Sin asignar',
            u.num_ventas,
            parseFloat(u.monto_total),
            parseFloat(u.monto_total) / u.num_ventas
        ])
    ];
    const ws3 = XLSX.utils.aoa_to_sheet(usuariosData);
    XLSX.utils.book_append_sheet(wb, ws3, 'Ventas por Usuario');
    
    // Hoja 4: Ventas por Día
    const ventasDiaData = [
        ['Fecha', 'Número de Ventas', 'Monto'],
        ...datosReporte.ventas_por_dia.map(v => [
            v.fecha,
            v.num_ventas,
            parseFloat(v.monto)
        ])
    ];
    const ws4 = XLSX.utils.aoa_to_sheet(ventasDiaData);
    XLSX.utils.book_append_sheet(wb, ws4, 'Ventas por Día');
    
    // Hoja 5: Stock Bajo
    if (datosReporte.productos_stock_bajo.length > 0) {
        const stockData = [
            ['Código', 'Producto', 'Stock Actual', 'Stock Mínimo', 'Estado'],
            ...datosReporte.productos_stock_bajo.map(p => [
                p.codigo,
                p.nombre,
                p.stock,
                p.stock_minimo,
                p.stock == 0 ? 'Sin Stock' : 'Stock Bajo'
            ])
        ];
        const ws5 = XLSX.utils.aoa_to_sheet(stockData);
        XLSX.utils.book_append_sheet(wb, ws5, 'Stock Bajo');
    }
    
    // Guardar Excel
    XLSX.writeFile(wb, `Reporte_${datosReporte.empresa}_${new Date().toISOString().split('T')[0]}.xlsx`);
}
</script>

</body>
</html>