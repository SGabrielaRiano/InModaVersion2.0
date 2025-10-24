<?php
// Activar reporte de errores para diagnóstico
error_reporting(E_ALL);
ini_set('display_errors', 1);

session_start();

// Redirigir al login si no hay sesión
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

// Verificar que el archivo de conexión existe
if (!file_exists("config/conexion.php")) {
    die("ERROR: No se encuentra el archivo config/conexion.php");
}

require_once "config/conexion.php";

// Verificar conexión a base de datos
if (!isset($mysqli) || $mysqli->connect_error) {
    die("ERROR: No se pudo conectar a la base de datos: " . ($mysqli->connect_error ?? 'Variable $mysqli no definida'));
}

// Obtener rol y empresa del usuario
$rol_usuario = $_SESSION['rol'] ?? 'Vendedor';
$empresa_usuario = $_SESSION['empresa'] ?? 'Sin empresa';
$es_admin = ($rol_usuario === 'Administrador');

// Obtener datos para el dashboard con manejo de errores
$totalProductos = 0;
$ventasMes = 0;
$totalProveedores = 0;
$productosStockBajo = 0;
$ultimasVentas = [];
$ventasPorDia = [];
$productosMasVendidos = [];

try {
    // Total productos
    $result = $mysqli->query("SELECT COUNT(*) as total FROM productos");
    if ($result) {
        $totalProductos = $result->fetch_assoc()['total'];
    }
} catch (Exception $e) {
    // Silenciar error pero continuar
}

try {
    // Ventas del mes
    $result = $mysqli->query("SELECT SUM(total) as total_ventas FROM ventas WHERE MONTH(fecha)=MONTH(CURDATE()) AND YEAR(fecha)=YEAR(CURDATE())");
    if ($result) {
        $row = $result->fetch_assoc();
        $ventasMes = $row['total_ventas'] ?? 0;
    }
} catch (Exception $e) {
    // Silenciar error pero continuar
}

try {
    // Total proveedores
    $result = $mysqli->query("SELECT COUNT(*) as total FROM proveedores");
    if ($result) {
        $totalProveedores = $result->fetch_assoc()['total'];
    }
} catch (Exception $e) {
    // Silenciar error pero continuar
}

try {
    // Productos con stock bajo (menos de 10)
    $result = $mysqli->query("SELECT COUNT(*) as total FROM productos WHERE stock < 10");
    if ($result) {
        $productosStockBajo = $result->fetch_assoc()['total'];
    }
} catch (Exception $e) {
    // Silenciar error pero continuar
}

try {
    // Últimas 5 ventas
    $result = $mysqli->query("SELECT v.*, u.usuario FROM ventas v LEFT JOIN usuarios u ON v.usuario_id = u.id ORDER BY v.fecha DESC LIMIT 5");
    if ($result) {
        while ($row = $result->fetch_assoc()) {
            $ultimasVentas[] = $row;
        }
    }
} catch (Exception $e) {
    // Silenciar error pero continuar
}

try {
    // Ventas por día del mes actual (para gráfico)
    $result = $mysqli->query("SELECT DAY(fecha) as dia, SUM(total) as total FROM ventas WHERE MONTH(fecha)=MONTH(CURDATE()) AND YEAR(fecha)=YEAR(CURDATE()) GROUP BY DAY(fecha) ORDER BY dia");
    if ($result) {
        while ($row = $result->fetch_assoc()) {
            $ventasPorDia[] = $row;
        }
    }
} catch (Exception $e) {
    // Silenciar error pero continuar
}

try {
    // Productos más vendidos
    $result = $mysqli->query("SELECT p.nombre, SUM(dv.cantidad) as total_vendido FROM detalle_venta dv INNER JOIN productos p ON dv.producto_id = p.id GROUP BY dv.producto_id ORDER BY total_vendido DESC LIMIT 5");
    if ($result) {
        while ($row = $result->fetch_assoc()) {
            $productosMasVendidos[] = $row;
        }
    }
} catch (Exception $e) {
    // Silenciar error pero continuar
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>InModa - Panel Principal</title>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<style>
:root {
    --color-primary-blue: #007bff;
    --color-background-main: #f5f5dc;
    --color-secondary-grey: #d3d3d3;
    --color-text-black: #000000;
    --color-white: #FFFFFF;
    --color-light-grey-text: #666666;
    --color-hover-grey: #bfbfbf;
    --color-button-yellow: #FFD700;
    --color-red: #dc3545;
    --color-green: #28a745;
    --color-orange: #fd7e14;
}

* {
    margin: 0;
    padding: 0;
    box-sizing: border-box;
}

body {
    font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
    background-color: var(--color-background-main);
    color: var(--color-text-black);
    overflow-x: auto;
    overflow-y: auto;
}

/* ===== SIDEBAR ===== */
.sidebar {
    width: 260px;
    background: linear-gradient(180deg, var(--color-secondary-grey) 0%, #c0c0c0 100%);
    display: flex;
    flex-direction: column;
    justify-content: space-between;
    position: fixed;
    height: 100vh;
    padding: 20px 0;
    box-shadow: 3px 0 15px rgba(0,0,0,0.15);
    transition: all 0.3s ease;
    z-index: 1000;
}

.sidebar.collapsed { width: 80px; }

.sidebar-header {
    text-align: center;
    position: relative;
    padding: 0 15px;
    margin-bottom: 20px;
    display: flex;
    flex-direction: column;
    align-items: center;
}

.sidebar-header img {
    width: 90px;
    transition: all 0.3s ease;
    filter: drop-shadow(0 2px 5px rgba(0,0,0,0.2));
    margin: 0 auto;
}

.sidebar.collapsed .sidebar-header img { 
    width: 45px;
}

.sidebar-header h2 {
    display: none;
}

.toggle-btn {
    position: absolute;
    top: 15px;
    right: -15px;
    background: linear-gradient(135deg, var(--color-primary-blue), #0056b3);
    color: var(--color-white);
    border-radius: 50%;
    width: 32px;
    height: 32px;
    cursor: pointer;
    display: flex;
    justify-content: center;
    align-items: center;
    box-shadow: 0 3px 10px rgba(0,0,0,0.3);
    transition: all 0.3s ease;
    border: 2px solid var(--color-white);
}

.toggle-btn:hover { 
    transform: scale(1.1);
    background: linear-gradient(135deg, #0056b3, var(--color-primary-blue));
}

.sidebar-nav { 
    flex-grow: 1; 
    padding: 0 15px; 
    overflow-y: auto;
    scrollbar-width: thin;
}

.sidebar-nav::-webkit-scrollbar { width: 6px; }
.sidebar-nav::-webkit-scrollbar-track { background: transparent; }
.sidebar-nav::-webkit-scrollbar-thumb { 
    background: rgba(0,0,0,0.2); 
    border-radius: 3px; 
}

.sidebar-nav ul { list-style: none; padding: 0; margin: 0; }
.sidebar-nav li { margin-bottom: 8px; }

.sidebar-nav a {
    display: flex;
    align-items: center;
    text-decoration: none;
    color: var(--color-text-black);
    background-color: var(--color-white);
    padding: 12px 15px;
    border-radius: 12px;
    box-shadow: 0 2px 5px rgba(0,0,0,0.1);
    transition: all 0.3s ease;
    font-weight: 500;
    position: relative;
    overflow: hidden;
}

.sidebar-nav a::before {
    content: '';
    position: absolute;
    top: 0;
    left: -100%;
    width: 100%;
    height: 100%;
    background: linear-gradient(90deg, transparent, rgba(255,255,255,0.3), transparent);
    transition: left 0.5s;
}

.sidebar-nav a:hover::before {
    left: 100%;
}

.sidebar-nav a:hover {
    background: linear-gradient(135deg, var(--color-primary-blue), #0056b3);
    color: var(--color-white);
    transform: translateX(5px);
    box-shadow: 0 4px 12px rgba(0,123,255,0.3);
}

.sidebar-nav a i { 
    margin-right: 12px; 
    font-size: 1.1em;
    min-width: 20px;
}

.sidebar.collapsed .sidebar-nav a span { 
    display: none; 
}

.sidebar.collapsed .sidebar-nav a {
    justify-content: center;
    padding: 12px 8px;
}

.sidebar.collapsed .sidebar-nav a i { 
    margin-right: 0;
    font-size: 1.3em;
}

.sidebar.collapsed .dropdown-arrow {
    display: none;
}

.sidebar.collapsed .submenu {
    display: none !important;
}

.dropdown-arrow {
    margin-left: auto;
    transition: transform 0.3s ease;
    font-size: 0.9em;
}

.sidebar-toggle.active .dropdown-arrow {
    transform: rotate(180deg);
}

.submenu {
    list-style: none;
    max-height: 0;
    overflow: hidden;
    background-color: rgba(0,0,0,0.05);
    border-radius: 10px;
    margin-top: 5px;
    padding: 0;
    transition: all 0.3s ease;
}

.submenu.active { 
    max-height: 300px; 
    padding: 8px 0; 
}

.submenu li a {
    background-color: rgba(255,255,255,0.9);
    padding: 10px 20px 10px 45px;
    margin: 4px 8px;
    font-size: 0.95em;
}

.submenu li a:hover {
    background: linear-gradient(135deg, var(--color-primary-blue), #0056b3);
    color: var(--color-white);
}

.calendar-section {
    text-align: center;
    background: linear-gradient(135deg, var(--color-white), #f8f8f8);
    margin: 15px;
    padding: 15px;
    border-radius: 15px;
    box-shadow: 0 3px 10px rgba(0,0,0,0.15);
    transition: all 0.3s ease;
}

.sidebar.collapsed .calendar-section {
    display: none;
}

.calendar-section h4 {
    font-size: 1em;
    margin-bottom: 10px;
    color: var(--color-primary-blue);
    font-weight: 600;
}

#calendar {
    border: 2px solid var(--color-secondary-grey);
    border-radius: 10px;
    min-height: 120px;
    padding: 10px;
    background-color: var(--color-background-main);
    overflow-y: auto;
    max-height: 200px;
    font-size: 0.9em;
}

#editCalendar {
    background: linear-gradient(135deg, var(--color-button-yellow), #e6c700);
    border: none;
    border-radius: 10px;
    padding: 8px 15px;
    margin-top: 10px;
    cursor: pointer;
    transition: all 0.3s ease;
    font-weight: 600;
    box-shadow: 0 2px 5px rgba(0,0,0,0.2);
}

#editCalendar:hover { 
    transform: translateY(-2px);
    box-shadow: 0 4px 10px rgba(0,0,0,0.3);
}

.sidebar-footer-icons {
    text-align: center;
    padding: 15px 0;
    border-top: 2px solid rgba(0,0,0,0.1);
}

.sidebar-footer-icons .icon {
    font-size: 24px;
    margin: 0 12px;
    cursor: pointer;
    opacity: 0.7;
    transition: all 0.3s ease;
    color: var(--color-text-black);
}

.sidebar-footer-icons .icon:hover {
    opacity: 1;
    transform: scale(1.2) rotate(10deg);
    color: var(--color-primary-blue);
}

/* ===== MAIN CONTENT ===== */
.main-content {
    margin-left: 260px;
    padding: 30px;
    background-color: var(--color-background-main);
    min-height: 100vh;
    transition: margin-left 0.3s ease;
    width: calc(100% - 260px);
    overflow-x: auto;
}

.sidebar.collapsed ~ .main-content { 
    margin-left: 80px;
    width: calc(100% - 80px);
}

.main-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 30px;
    background: linear-gradient(135deg, var(--color-white), #f8f8f8);
    padding: 20px 25px;
    border-radius: 15px;
    box-shadow: 0 3px 15px rgba(0,0,0,0.1);
}

.main-header h1 {
    font-size: 2em;
    color: var(--color-primary-blue);
    display: flex;
    align-items: center;
    gap: 10px;
}

.header-actions {
    display: flex;
    align-items: center;
    gap: 15px;
}

.notas-btn {
    background: linear-gradient(135deg, var(--color-button-yellow), #e6c700);
    color: var(--color-text-black);
    padding: 12px 20px;
    border-radius: 10px;
    text-decoration: none;
    font-weight: 600;
    transition: all 0.3s ease;
    box-shadow: 0 3px 8px rgba(0,0,0,0.15);
    display: flex;
    align-items: center;
    gap: 8px;
}

.notas-btn:hover { 
    transform: translateY(-3px);
    box-shadow: 0 5px 15px rgba(0,0,0,0.25);
}

.user-info {
    background-color: var(--color-white);
    padding: 10px 18px;
    border-radius: 10px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.1);
    font-weight: 600;
}

/* ===== DASHBOARD CARDS ===== */
.dashboard-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
    gap: 20px;
    margin-bottom: 30px;
}

.stat-card {
    background: linear-gradient(135deg, var(--color-white), #f8f8f8);
    border-radius: 15px;
    padding: 25px;
    box-shadow: 0 5px 15px rgba(0,0,0,0.1);
    transition: all 0.3s ease;
    position: relative;
    overflow: hidden;
}

.stat-card::before {
    content: '';
    position: absolute;
    top: -50%;
    right: -50%;
    width: 200%;
    height: 200%;
    background: radial-gradient(circle, rgba(0,123,255,0.1), transparent);
    transition: all 0.5s ease;
    opacity: 0;
}

.stat-card:hover::before {
    opacity: 1;
    top: -25%;
    right: -25%;
}

.stat-card:hover {
    transform: translateY(-10px) scale(1.02);
    box-shadow: 0 10px 30px rgba(0,123,255,0.2);
}

.stat-card-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 15px;
}

.stat-card h3 {
    margin: 0;
    font-size: 0.95em;
    color: var(--color-light-grey-text);
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

.stat-icon {
    width: 50px;
    height: 50px;
    border-radius: 12px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.5em;
    box-shadow: 0 3px 10px rgba(0,0,0,0.15);
}

.stat-card-body p {
    font-size: 2.5em;
    font-weight: 700;
    margin: 0;
    color: var(--color-text-black);
}

.stat-card-footer {
    margin-top: 10px;
    font-size: 0.85em;
    color: var(--color-light-grey-text);
}

/* Colores específicos para cada tarjeta */
.card-productos .stat-icon { background: linear-gradient(135deg, #667eea, #764ba2); color: white; }
.card-ventas .stat-icon { background: linear-gradient(135deg, #f093fb, #f5576c); color: white; }
.card-proveedores .stat-icon { background: linear-gradient(135deg, #4facfe, #00f2fe); color: white; }
.card-stock .stat-icon { background: linear-gradient(135deg, #fa709a, #fee140); color: white; }

/* ===== CHART SECTION ===== */
.chart-section {
    background: linear-gradient(135deg, var(--color-white), #f8f8f8);
    padding: 25px;
    border-radius: 15px;
    box-shadow: 0 5px 15px rgba(0,0,0,0.1);
    margin-bottom: 30px;
}

.chart-section h2 {
    margin: 0 0 20px 0;
    color: var(--color-primary-blue);
    display: flex;
    align-items: center;
    gap: 10px;
}

.chart-container {
    position: relative;
    height: 300px;
}

/* ===== ACTIVITY SECTION ===== */
.activity-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(400px, 1fr));
    gap: 20px;
    margin-bottom: 30px;
}

.activity-card {
    background: linear-gradient(135deg, var(--color-white), #f8f8f8);
    padding: 25px;
    border-radius: 15px;
    box-shadow: 0 5px 15px rgba(0,0,0,0.1);
}

.activity-card h2 {
    margin: 0 0 20px 0;
    color: var(--color-primary-blue);
    display: flex;
    align-items: center;
    gap: 10px;
}

.activity-item {
    background-color: var(--color-white);
    padding: 15px;
    border-radius: 10px;
    margin-bottom: 10px;
    border-left: 4px solid var(--color-primary-blue);
    transition: all 0.3s ease;
    box-shadow: 0 2px 5px rgba(0,0,0,0.05);
}

.activity-item:hover {
    transform: translateX(5px);
    box-shadow: 0 4px 12px rgba(0,0,0,0.15);
}

.activity-item-header {
    display: flex;
    justify-content: space-between;
    margin-bottom: 5px;
}

.activity-item strong {
    color: var(--color-primary-blue);
}

.activity-item small {
    color: var(--color-light-grey-text);
}

.no-data {
    text-align: center;
    padding: 30px;
    color: var(--color-light-grey-text);
    font-style: italic;
}

/* ===== QUICK ACTIONS ===== */
.quick-actions {
    background: linear-gradient(135deg, var(--color-white), #f8f8f8);
    padding: 25px;
    border-radius: 15px;
    box-shadow: 0 5px 15px rgba(0,0,0,0.1);
    margin-bottom: 30px;
}

.quick-actions h2 {
    margin: 0 0 20px 0;
    color: var(--color-primary-blue);
    display: flex;
    align-items: center;
    gap: 10px;
}

.actions-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 15px;
}

.action-btn {
    background: linear-gradient(135deg, var(--color-primary-blue), #0056b3);
    color: var(--color-white);
    padding: 18px 25px;
    text-decoration: none;
    border-radius: 12px;
    display: flex;
    align-items: center;
    gap: 12px;
    font-weight: 600;
    transition: all 0.3s ease;
    box-shadow: 0 4px 10px rgba(0,123,255,0.3);
    justify-content: center;
}

.action-btn:hover {
    transform: translateY(-5px);
    box-shadow: 0 8px 20px rgba(0,123,255,0.4);
}

.action-btn i {
    font-size: 1.3em;
}

/* ===== ALERTS ===== */
.alert-section {
    background: linear-gradient(135deg, #fff3cd, #ffe69c);
    padding: 20px 25px;
    border-radius: 15px;
    box-shadow: 0 5px 15px rgba(0,0,0,0.1);
    margin-bottom: 30px;
    border-left: 5px solid var(--color-orange);
    display: flex;
    align-items: center;
    gap: 15px;
}

.alert-section i {
    font-size: 2em;
    color: var(--color-orange);
}

.alert-content h3 {
    margin: 0 0 5px 0;
    color: var(--color-text-black);
}

.alert-content p {
    margin: 0;
    color: var(--color-light-grey-text);
}

/* ===== MODALS ===== */
.user-modal {
    display: none;
    position: fixed;
    top: 50%;
    left: 50%;
    transform: translate(-50%, -50%);
    background: var(--color-white);
    padding: 30px;
    border-radius: 20px;
    box-shadow: 0 10px 40px rgba(0,0,0,0.3);
    text-align: center;
    z-index: 2000;
    animation: modalSlideIn 0.3s ease;
}

@keyframes modalSlideIn {
    from { opacity: 0; transform: translate(-50%, -60%); }
    to { opacity: 1; transform: translate(-50%, -50%); }
}

.user-modal.active { display: block; }

.modal-overlay {
    display: none;
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background-color: rgba(0,0,0,0.5);
    z-index: 1999;
}

.modal-overlay.active { display: block; }

.user-modal img {
    width: 100px;
    height: 100px;
    border-radius: 50%;
    border: 4px solid var(--color-primary-blue);
    margin-bottom: 15px;
}

.user-modal h3 { 
    margin: 10px 0 5px; 
    color: var(--color-primary-blue);
}

.user-modal p { 
    color: var(--color-light-grey-text); 
    margin: 8px 0; 
}

.status-indicator {
    display: inline-block;
    width: 12px;
    height: 12px;
    background-color: var(--color-green);
    border-radius: 50%;
    margin-right: 5px;
    animation: pulse 2s infinite;
}

@keyframes pulse {
    0%, 100% { opacity: 1; }
    50% { opacity: 0.5; }
}

.close-modal-btn {
    margin-top: 20px;
    background: var(--color-primary-blue);
    color: white;
    border: none;
    padding: 10px 30px;
    border-radius: 10px;
    cursor: pointer;
    font-weight: 600;
    transition: all 0.3s ease;
}

.close-modal-btn:hover {
    background: #0056b3;
    transform: scale(1.05);
}

/* ===== PANEL DE NOTIFICACIONES ===== */
.notifications-panel {
    display: none;
    position: fixed;
    bottom: 80px;
    left: 20px;
    width: 380px;
    max-height: 500px;
    background: var(--color-white);
    border-radius: 15px;
    box-shadow: 0 10px 40px rgba(0,0,0,0.2);
    z-index: 2001;
    animation: slideUp 0.3s ease;
    overflow: hidden;
}

@keyframes slideUp {
    from { opacity: 0; transform: translateY(20px); }
    to { opacity: 1; transform: translateY(0); }
}

.notifications-panel.active {
    display: flex;
    flex-direction: column;
}

.notifications-header {
    background: linear-gradient(135deg, var(--color-primary-blue), #0056b3);
    color: white;
    padding: 20px;
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.notifications-header h3 {
    margin: 0;
    font-size: 1.2em;
    display: flex;
    align-items: center;
    gap: 10px;
}

.notifications-header .close-notif {
    background: none;
    border: none;
    color: white;
    font-size: 1.5em;
    cursor: pointer;
    transition: transform 0.2s ease;
}

.notifications-header .close-notif:hover {
    transform: rotate(90deg);
}

.notifications-body {
    flex: 1;
    overflow-y: auto;
    padding: 10px;
    max-height: 400px;
}

.notifications-body::-webkit-scrollbar {
    width: 6px;
}

.notifications-body::-webkit-scrollbar-track {
    background: #f1f1f1;
    border-radius: 10px;
}

.notifications-body::-webkit-scrollbar-thumb {
    background: var(--color-primary-blue);
    border-radius: 10px;
}

.notification-item {
    background: var(--color-background-main);
    padding: 15px;
    border-radius: 10px;
    margin-bottom: 10px;
    border-left: 4px solid var(--color-primary-blue);
    transition: all 0.3s ease;
    cursor: pointer;
}

.notification-item:hover {
    background: #e8e8ce;
    transform: translateX(5px);
}

.notification-item.warning {
    border-left-color: var(--color-orange);
}

.notification-item.success {
    border-left-color: var(--color-green);
}

.notification-item.danger {
    border-left-color: var(--color-red);
}

.notification-item .notif-icon {
    font-size: 1.5em;
    margin-right: 10px;
}

.notification-item .notif-title {
    font-weight: 600;
    color: var(--color-text-black);
    margin-bottom: 5px;
    display: flex;
    align-items: center;
    gap: 8px;
}

.notification-item .notif-message {
    color: var(--color-light-grey-text);
    font-size: 0.9em;
    margin: 5px 0;
}

.notification-item .notif-time {
    font-size: 0.75em;
    color: #999;
    margin-top: 5px;
}

.no-notifications {
    text-align: center;
    padding: 40px 20px;
    color: var(--color-light-grey-text);
}

.no-notifications i {
    font-size: 3em;
    margin-bottom: 15px;
    opacity: 0.3;
}

.notification-badge {
    position: absolute;
    top: -5px;
    right: -5px;
    background: var(--color-red);
    color: white;
    border-radius: 50%;
    width: 18px;
    height: 18px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 0.7em;
    font-weight: bold;
}

.sidebar-footer-icons .icon {
    position: relative;
}

/* ===== RESPONSIVE ===== */
@media (max-width: 768px) {
    .sidebar {
        width: 80px;
    }
    
    .sidebar .sidebar-header img {
        width: 45px;
    }
    
    .sidebar .sidebar-nav a span,
    .calendar-section {
        display: none;
    }
    
    .main-content {
        margin-left: 80px;
        padding: 15px;
        width: calc(100% - 80px);
    }
    
    .main-header {
        flex-direction: column;
        gap: 15px;
        text-align: center;
    }
    
    .main-header h1 {
        font-size: 1.5em;
    }
    
    .dashboard-grid,
    .activity-grid,
    .actions-grid {
        grid-template-columns: 1fr;
    }
    
    .header-actions {
        flex-direction: column;
        width: 100%;
    }
    
    .user-info {
        font-size: 0.9em;
    }
}

@media (max-width: 480px) {
    .main-content {
        padding: 10px;
    }
    
    .stat-card {
        padding: 15px;
    }
    
    .stat-card-body p {
        font-size: 2em;
    }
}

/* ===== LOADING ANIMATION ===== */
@keyframes fadeIn {
    from { opacity: 0; transform: translateY(20px); }
    to { opacity: 1; transform: translateY(0); }
}

.fade-in {
    animation: fadeIn 0.5s ease forwards;
}
/* ===== MODAL TURNOS ===== */
.modal-turnos {
    display: none;
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: rgba(0,0,0,0.7);
    z-index: 2000;
    overflow-y: auto;
}

.modal-turnos.active {
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 20px;
}

.modal-turnos-content {
    background: white;
    border-radius: 20px;
    width: 95%;
    max-width: 1200px;
    max-height: 90vh;
    overflow-y: auto;
    box-shadow: 0 20px 60px rgba(0,0,0,0.4);
    animation: modalSlideIn 0.4s ease;
}

@keyframes modalSlideIn {
    from {
        opacity: 0;
        transform: scale(0.9) translateY(-50px);
    }
    to {
        opacity: 1;
        transform: scale(1) translateY(0);
    }
}

.modal-turnos-header {
    background: linear-gradient(135deg, var(--color-primary-blue), #0056b3);
    color: white;
    padding: 25px 30px;
    border-radius: 20px 20px 0 0;
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.modal-turnos-header h2 {
    margin: 0;
    font-size: 1.8em;
    display: flex;
    align-items: center;
    gap: 15px;
}

.close-modal-turnos {
    background: rgba(255,255,255,0.2);
    border: 2px solid white;
    color: white;
    width: 45px;
    height: 45px;
    border-radius: 50%;
    font-size: 1.5em;
    cursor: pointer;
    transition: all 0.3s ease;
    display: flex;
    align-items: center;
    justify-content: center;
}

.close-modal-turnos:hover {
    background: white;
    color: var(--color-primary-blue);
    transform: rotate(90deg);
}

.modal-turnos-body {
    padding: 30px;
}

.turnos-header-actions {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 25px;
    flex-wrap: wrap;
    gap: 15px;
}

.turnos-stats {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
    gap: 15px;
    margin-bottom: 25px;
}

.turno-stat {
    background: linear-gradient(135deg, #f8f9fa, #e9ecef);
    padding: 15px;
    border-radius: 12px;
    text-align: center;
    border-left: 4px solid var(--color-primary-blue);
}

.turno-stat h4 {
    font-size: 0.85em;
    color: var(--color-light-grey-text);
    margin-bottom: 8px;
}

.turno-stat p {
    font-size: 1.8em;
    font-weight: 700;
    color: var(--color-text-black);
    margin: 0;
}

.btn-nuevo-turno {
    background: linear-gradient(135deg, var(--color-green), #1e7e34);
    color: white;
    padding: 12px 25px;
    border: none;
    border-radius: 10px;
    cursor: pointer;
    font-size: 1em;
    font-weight: 600;
    transition: all 0.3s ease;
    display: flex;
    align-items: center;
    gap: 10px;
}

.btn-nuevo-turno:hover {
    transform: translateY(-2px);
    box-shadow: 0 5px 20px rgba(40, 167, 69, 0.4);
}

.btn-nuevo-turno:disabled {
    opacity: 0.5;
    cursor: not-allowed;
}

.turnos-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(320px, 1fr));
    gap: 20px;
    margin-top: 20px;
}

.turno-card {
    background: white;
    border: 2px solid #e9ecef;
    border-radius: 15px;
    padding: 20px;
    transition: all 0.3s ease;
    position: relative;
    overflow: hidden;
}

.turno-card::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    width: 5px;
    height: 100%;
    background: var(--color-primary-blue);
}

.turno-card:hover {
    transform: translateY(-5px);
    box-shadow: 0 10px 30px rgba(0,0,0,0.15);
    border-color: var(--color-primary-blue);
}

.turno-card-header {
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    margin-bottom: 15px;
}

.turno-titulo {
    font-size: 1.3em;
    font-weight: 700;
    color: var(--color-text-black);
    margin: 0;
    flex: 1;
}

.turno-descripcion {
    color: var(--color-light-grey-text);
    margin: 10px 0;
    line-height: 1.5;
    font-size: 0.95em;
}

.turno-fechas {
    background: #f8f9fa;
    padding: 12px;
    border-radius: 10px;
    margin: 15px 0;
}

.turno-fecha-item {
    display: flex;
    align-items: center;
    gap: 10px;
    margin: 5px 0;
    font-size: 0.9em;
}

.turno-fecha-item i {
    color: var(--color-primary-blue);
    width: 20px;
}

.turno-actions {
    display: flex;
    gap: 10px;
    margin-top: 15px;
    padding-top: 15px;
    border-top: 1px solid #e9ecef;
}

.btn-turno-action {
    flex: 1;
    padding: 10px;
    border: none;
    border-radius: 8px;
    cursor: pointer;
    font-size: 0.9em;
    font-weight: 600;
    transition: all 0.3s ease;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
}

.btn-turno-editar {
    background: #e3f2fd;
    color: #1976d2;
}

.btn-turno-editar:hover {
    background: #1976d2;
    color: white;
}

.btn-turno-eliminar {
    background: #ffebee;
    color: #c62828;
}

.btn-turno-eliminar:hover {
    background: #c62828;
    color: white;
}

.btn-turno-action:disabled {
    opacity: 0.4;
    cursor: not-allowed;
}

.no-turnos {
    text-align: center;
    padding: 60px 20px;
    color: var(--color-light-grey-text);
}

.no-turnos i {
    font-size: 5em;
    margin-bottom: 20px;
    color: var(--color-secondary-grey);
}

/* Form Turno */
.form-turno {
    background: #f8f9fa;
    padding: 25px;
    border-radius: 15px;
    margin-bottom: 25px;
    display: none;
}

.form-turno.active {
    display: block;
    animation: slideDown 0.3s ease;
}

@keyframes slideDown {
    from {
        opacity: 0;
        transform: translateY(-20px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

.form-turno-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 20px;
}

.form-turno-header h3 {
    margin: 0;
    color: var(--color-text-black);
    display: flex;
    align-items: center;
    gap: 10px;
}

.form-group-turno {
    margin-bottom: 20px;
}

.form-group-turno label {
    display: block;
    margin-bottom: 8px;
    font-weight: 600;
    color: var(--color-text-black);
    font-size: 0.95em;
}

.form-group-turno input,
.form-group-turno textarea {
    width: 100%;
    padding: 12px 15px;
    border: 2px solid #dee2e6;
    border-radius: 10px;
    font-size: 1em;
    transition: all 0.3s ease;
}

.form-group-turno textarea {
    resize: vertical;
    min-height: 100px;
}

.form-group-turno input:focus,
.form-group-turno textarea:focus {
    outline: none;
    border-color: var(--color-primary-blue);
    box-shadow: 0 0 0 3px rgba(0, 123, 255, 0.1);
}

.form-grid-turno {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 20px;
}

.form-actions-turno {
    display: flex;
    gap: 15px;
    justify-content: flex-end;
    padding-top: 20px;
}

.btn-form-turno {
    padding: 12px 30px;
    border: none;
    border-radius: 10px;
    font-size: 1em;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.3s ease;
    display: flex;
    align-items: center;
    gap: 10px;
}

.btn-cancelar-turno {
    background: var(--color-secondary-grey);
    color: var(--color-text-black);
}

.btn-cancelar-turno:hover {
    background: var(--color-hover-grey);
}

.btn-guardar-turno {
    background: linear-gradient(135deg, var(--color-primary-blue), #0056b3);
    color: white;
}

.btn-guardar-turno:hover {
    transform: translateY(-2px);
    box-shadow: 0 5px 20px rgba(0, 123, 255, 0.4);
}

@media (max-width: 768px) {
    .modal-turnos-content {
        width: 100%;
        max-height: 95vh;
    }
    
    .turnos-grid {
        grid-template-columns: 1fr;
    }
    
    .form-grid-turno {
        grid-template-columns: 1fr;
    }
    
    .turnos-header-actions {
        flex-direction: column;
        align-items: stretch;
    }
}

</style>
</head>

<body>
<div class="app-container">
    <!-- Sidebar -->
    <div class="sidebar" id="sidebar">
        <div class="sidebar-header">
            <img src="assets/images/inmoda-logo.png" alt="Logo InModa" onerror="this.style.display='none'">
            <div class="toggle-btn" id="toggleSidebar">
                <i class="fas fa-angle-left"></i>
            </div>
        </div>

        <nav class="sidebar-nav">
            <ul>
                <li>
                    <a href="index.php" style="background: linear-gradient(135deg, var(--color-primary-blue), #0056b3); color: white;">
                        <i class="fas fa-home"></i><span>Inicio</span>
                    </a>
                </li>
                
                <li class="has-submenu">
                    <a href="#" class="sidebar-toggle">
                        <i class="fas fa-tshirt"></i><span>Productos</span>
                        <i class="fas fa-chevron-down dropdown-arrow"></i>
                    </a>
                    <ul class="submenu">
                        <li><a href="modules/productos.php"><i class="fas fa-list"></i> Ver Productos</a></li>
                        <?php if ($_SESSION['rol'] === 'Administrador' || $_SESSION['rol'] === 'Vendedor'): ?>
                        <li><a href="modules/agregar_producto.php"><i class="fas fa-plus"></i> Agregar</a></li>
                        <?php endif; ?>
                    </ul>
                </li>
                
                <?php if ($_SESSION['rol'] === 'Administrador'): ?>
                <li class="has-submenu">
                    <a href="#" class="sidebar-toggle">
                        <i class="fas fa-truck"></i><span>Proveedores</span>
                        <i class="fas fa-chevron-down dropdown-arrow"></i>
                    </a>
                    <ul class="submenu">
                        <li><a href="modules/proveedores.php"><i class="fas fa-list"></i> Ver Proveedores</a></li>
                        <li><a href="modules/agregar_proveedores.php"><i class="fas fa-plus"></i> Agregar</a></li>
                    </ul>
                </li>
                <?php endif; ?>
                
                <li><a href="modules/ventas.php"><i class="fas fa-cash-register"></i><span>Ventas</span></a></li>
                
                <?php if ($_SESSION['rol'] === 'Administrador'): ?>
                <li><a href="modules/reportes.php"><i class="fas fa-chart-line"></i><span>Reportes</span></a></li>
                <?php endif; ?>
                
                <li><a href="modules/inventario.php"><i class="fas fa-boxes"></i><span>Inventario</span></a></li>
                
                <?php if ($_SESSION['rol'] === 'Administrador'): ?>
                <li><a href="modules/usuarios.php"><i class="fas fa-users"></i><span>Usuarios</span></a></li>
                <?php endif; ?>
                
                <li><a href="logout.php" style="border-left: 4px solid var(--color-red);"><i class="fas fa-sign-out-alt"></i><span>Salir</span></a></li>
            </ul>
        </nav>

        <div class="calendar-section">
            <h4>📅 Calendario de Turnos</h4>
            <div id="calendar" style="max-height: 200px; overflow-y: auto;">
                <p style="margin: 5px 0; color: var(--color-light-grey-text); font-size: 0.85em;">
                    <i class="fas fa-spinner fa-spin"></i> Cargando turnos...
                </p>
            </div>
            <?php if ($_SESSION['rol'] === 'Administrador'): ?>
            <button id="editCalendar" onclick="abrirModalTurnos()">
                <i class="fas fa-pencil-alt"></i> Modificar
            </button>
            <?php else: ?>
            <button id="editCalendar" onclick="abrirModalTurnos()" style="opacity: 0.7;">
                <i class="fas fa-eye"></i> Ver Turnos
            </button>
            <?php endif; ?>
        </div>

        <div class="sidebar-footer-icons">
            <i class="fas fa-cog icon" id="settings" title="Configuración"></i>
            <i class="fas fa-bell icon" id="notifications" title="Notificaciones"></i>
            <i class="fas fa-user-circle icon" id="userInfo" title="Perfil"></i>
        </div>
    </div>

    <!-- Main Content -->
    <main class="main-content">
        <!-- Header -->
        <div class="main-header fade-in">
            <h1><i class="fas fa-home"></i> Panel Principal</h1>
            <div class="header-actions">
                <?php if ($_SESSION['rol'] === 'Administrador'): ?>
                <a href="modules/notas.php" class="notas-btn">
                    <i class="fas fa-sticky-note"></i> Notas
                </a>
                <?php else: ?>
                <a href="modules/notas.php" class="notas-btn" style="opacity: 0.7;" title="Solo puedes ver notas">
                    <i class="fas fa-sticky-note"></i> Ver Notas
                </a>
                <?php endif; ?>
                <div class="user-info">
                    <i class="fas fa-building"></i> <strong><?= htmlspecialchars($empresa_usuario) ?></strong><br>
                    <small style="font-size: 0.9em;"><i class="fas fa-user"></i> <?= htmlspecialchars($_SESSION['usuario'] ?? 'Usuario') ?> — <?= htmlspecialchars($rol_usuario) ?></small>
                </div>
            </div>
        </div>

        <!-- Alert for Low Stock -->
        <?php if ($productosStockBajo > 0): ?>
        <div class="alert-section fade-in">
            <i class="fas fa-exclamation-triangle"></i>
            <div class="alert-content">
                <h3>⚠️ Alerta de Inventario</h3>
                <p>Tienes <strong><?= $productosStockBajo ?></strong> producto(s) con stock bajo. <a href="modules/inventario.php" style="color: var(--color-primary-blue); font-weight: 600;">Ver detalles</a></p>
            </div>
        </div>
        <?php endif; ?>

        <!-- Dashboard Stats -->
        <div class="dashboard-grid fade-in">
            <div class="stat-card card-productos">
                <div class="stat-card-header">
                    <h3>Total Productos</h3>
                    <div class="stat-icon">
                        <i class="fas fa-box"></i>
                    </div>
                </div>
                <div class="stat-card-body">
                    <p><?= number_format($totalProductos) ?></p>
                </div>
                <div class="stat-card-footer">
                    <i class="fas fa-arrow-up"></i> Inventario actual
                </div>
            </div>

            <div class="stat-card card-ventas">
                <div class="stat-card-header">
                    <h3>Ventas del Mes</h3>
                    <div class="stat-icon">
                        <i class="fas fa-dollar-sign"></i>
                    </div>
                </div>
                <div class="stat-card-body">
                    <p>$<?= number_format($ventasMes, 0, ',', '.') ?></p>
                </div>
                <div class="stat-card-footer">
                    <i class="fas fa-calendar"></i> <?= date('F Y') ?>
                </div>
            </div>

            <div class="stat-card card-proveedores">
                <div class="stat-card-header">
                    <h3>Proveedores</h3>
                    <div class="stat-icon">
                        <i class="fas fa-truck-loading"></i>
                    </div>
                </div>
                <div class="stat-card-body">
                    <p><?= number_format($totalProveedores) ?></p>
                </div>
                <div class="stat-card-footer">
                    <i class="fas fa-handshake"></i> Asociados activos
                </div>
            </div>

            <div class="stat-card card-stock">
                <div class="stat-card-header">
                    <h3>Stock Bajo</h3>
                    <div class="stat-icon">
                        <i class="fas fa-exclamation-circle"></i>
                    </div>
                </div>
                <div class="stat-card-body">
                    <p><?= number_format($productosStockBajo) ?></p>
                </div>
                <div class="stat-card-footer">
                    <i class="fas fa-box-open"></i> Requieren atención
                </div>
            </div>
        </div>

        <!-- Chart Section (Solo Admin) -->
        <?php if ($es_admin): ?>
        <div class="chart-section fade-in">
            <h2><i class="fas fa-chart-area"></i> Ventas del Mes</h2>
            <div class="chart-container">
                <canvas id="salesChart"></canvas>
            </div>
        </div>
        <?php endif; ?>

        <!-- Activity Grid (Solo Admin) -->
        <?php if ($es_admin): ?>
        <div class="activity-grid fade-in">
            <!-- Recent Sales -->
            <div class="activity-card">
                <h2><i class="fas fa-shopping-cart"></i> Últimas Ventas</h2>
                <?php if (count($ultimasVentas) > 0): ?>
                    <?php foreach ($ultimasVentas as $venta): ?>
                        <div class="activity-item">
                            <div class="activity-item-header">
                                <strong>Venta #<?= $venta['id'] ?></strong>
                                <small><?= date('d/m/Y H:i', strtotime($venta['fecha'])) ?></small>
                            </div>
                            <p style="margin: 5px 0; color: var(--color-light-grey-text);">
                                <i class="fas fa-user"></i> <?= htmlspecialchars($venta['usuario'] ?? 'Sistema') ?>
                            </p>
                            <p style="margin: 0; font-weight: 600; color: var(--color-green);">
                                <i class="fas fa-dollar-sign"></i> $<?= number_format($venta['total'], 0, ',', '.') ?>
                            </p>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div class="no-data">
                        <i class="fas fa-inbox" style="font-size: 3em; opacity: 0.3;"></i>
                        <p>No hay ventas registradas</p>
                    </div>
                <?php endif; ?>
            </div>

            <!-- Top Products -->
            <div class="activity-card">
                <h2><i class="fas fa-star"></i> Productos Más Vendidos</h2>
                <?php if (count($productosMasVendidos) > 0): ?>
                    <?php foreach ($productosMasVendidos as $index => $producto): ?>
                        <div class="activity-item">
                            <div class="activity-item-header">
                                <strong><?= ($index + 1) ?>. <?= htmlspecialchars($producto['nombre']) ?></strong>
                                <span style="background: var(--color-primary-blue); color: white; padding: 4px 12px; border-radius: 20px; font-size: 0.85em; font-weight: 600;">
                                    <?= $producto['total_vendido'] ?> vendidos
                                </span>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div class="no-data">
                        <i class="fas fa-chart-bar" style="font-size: 3em; opacity: 0.3;"></i>
                        <p>No hay datos de ventas</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
        <?php endif; ?>

        <!-- Quick Actions -->
        <div class="quick-actions fade-in">
            <h2><i class="fas fa-bolt"></i> Accesos Rápidos</h2>
            <div class="actions-grid">
                <a class="action-btn" href="modules/productos.php">
                    <i class="fas fa-tshirt"></i> Administrar Productos
                </a>
                <a class="action-btn" href="modules/ventas.php">
                    <i class="fas fa-cash-register"></i> Registrar Venta
                </a>
                <?php if ($es_admin): ?>
                <a class="action-btn" href="modules/reportes.php">
                    <i class="fas fa-chart-pie"></i> Ver Reportes
                </a>
                <?php endif; ?>
                <a class="action-btn" href="modules/inventario.php">
                    <i class="fas fa-warehouse"></i> Gestionar Inventario
                </a>
            </div>
        </div>
    </main>
</div>

<!-- Modal Overlay -->
<div class="modal-overlay" id="modalOverlay"></div>

<!-- User Modal -->
<div class="user-modal" id="userModal">
    <img src="assets/images/user-avatar.png" alt="Usuario" onerror="this.src='data:image/svg+xml,%3Csvg xmlns=%22http://www.w3.org/2000/svg%22 viewBox=%220 0 100 100%22%3E%3Ccircle cx=%2250%22 cy=%2250%22 r=%2250%22 fill=%22%23007bff%22/%3E%3Ctext x=%2250%22 y=%2265%22 font-size=%2250%22 text-anchor=%22middle%22 fill=%22white%22%3E👤%3C/text%3E%3C/svg%3E'">
    <h3><?= htmlspecialchars($_SESSION['usuario'] ?? 'Usuario') ?></h3>
    <p>
        <span class="status-indicator"></span> En línea
    </p>
    <p><strong>Rol:</strong> <?= htmlspecialchars($_SESSION['rol'] ?? 'Empleado') ?></p>
    <p><strong>Empresa:</strong> <?= htmlspecialchars($_SESSION['empresa'] ?? 'Sin empresa') ?></p>
    <p><strong>ID:</strong> #<?= htmlspecialchars($_SESSION['user_id'] ?? '000') ?></p>
    <button class="close-modal-btn" onclick="cerrarModal()">Cerrar</button>
</div>

<!-- Panel de Notificaciones -->
<div class="notifications-panel" id="notificationsPanel">
    <div class="notifications-header">
        <h3><i class="fas fa-bell"></i> Notificaciones</h3>
        <button class="close-notif" onclick="cerrarNotificaciones()">&times;</button>
    </div>
    <div class="notifications-body" id="notificationsBody">
        <!-- Las notificaciones se cargarán dinámicamente aquí -->
    </div>
</div>

<!-- Modal Turnos -->
<div class="modal-turnos" id="modalTurnos">
    <div class="modal-turnos-content">
        <div class="modal-turnos-header">
            <h2>
                <i class="fas fa-calendar-alt"></i>
                Gestión de Turnos de Trabajo
            </h2>
            <button class="close-modal-turnos" onclick="cerrarModalTurnos()">&times;</button>
        </div>

        <div class="modal-turnos-body">
            <!-- Estadísticas -->
            <div class="turnos-stats">
                <div class="turno-stat">
                    <h4>Total Turnos</h4>
                    <p id="statTotalTurnos">0</p>
                </div>
                <div class="turno-stat">
                    <h4>Esta Semana</h4>
                    <p id="statTurnosSemana">0</p>
                </div>
                <div class="turno-stat">
                    <h4>Este Mes</h4>
                    <p id="statTurnosMes">0</p>
                </div>
            </div>

            <!-- Header con botón agregar -->
            <div class="turnos-header-actions">
                <h3><i class="fas fa-list"></i> Turnos Registrados</h3>
                <?php if ($_SESSION['rol'] === 'Administrador'): ?>
                <button class="btn-nuevo-turno" onclick="mostrarFormTurno()">
                    <i class="fas fa-plus"></i> Nuevo Turno
                </button>
                <?php endif; ?>
            </div>

            <!-- Formulario para agregar/editar turno -->
            <?php if ($_SESSION['rol'] === 'Administrador'): ?>
            <div class="form-turno" id="formTurnoContainer">
                <div class="form-turno-header">
                    <h3 id="formTurnoTitulo"><i class="fas fa-plus-circle"></i> Nuevo Turno</h3>
                    <button class="close-modal-turnos" onclick="ocultarFormTurno()" style="width: 35px; height: 35px; font-size: 1.2em;">
                        &times;
                    </button>
                </div>

                <form id="formTurno" onsubmit="guardarTurno(event)">
                    <input type="hidden" id="turnoId" name="id">
                    
                    <div class="form-group-turno">
                        <label><i class="fas fa-heading"></i> Título del Turno *</label>
                        <input type="text" id="turnoTitulo" name="titulo" required placeholder="Ej: Turno Mañana - Juan Pérez">
                    </div>

                    <div class="form-group-turno">
                        <label><i class="fas fa-align-left"></i> Descripción</label>
                        <textarea id="turnoDescripcion" name="descripcion" placeholder="Descripción del turno (opcional)"></textarea>
                    </div>

                    <div class="form-grid-turno">
                        <div class="form-group-turno">
                            <label><i class="fas fa-clock"></i> Hora Inicio *</label>
                            <input type="datetime-local" id="turnoFechaInicio" name="fecha_inicio" required>
                        </div>

                        <div class="form-group-turno">
                            <label><i class="fas fa-clock"></i> Hora Fin</label>
                            <input type="datetime-local" id="turnoFechaFin" name="fecha_fin">
                        </div>
                    </div>

                    <div class="form-actions-turno">
                        <button type="button" class="btn-form-turno btn-cancelar-turno" onclick="ocultarFormTurno()">
                            <i class="fas fa-times"></i> Cancelar
                        </button>
                        <button type="submit" class="btn-form-turno btn-guardar-turno">
                            <i class="fas fa-save"></i> <span id="btnGuardarTexto">Guardar Turno</span>
                        </button>
                    </div>
                </form>
            </div>
            <?php endif; ?>

            <!-- Grid de turnos -->
            <div id="turnosGridContainer">
                <div class="turnos-grid" id="turnosGrid">
                    <div class="no-turnos">
                        <i class="fas fa-spinner fa-spin"></i>
                        <p>Cargando turnos...</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
// ===== SIDEBAR FUNCTIONALITY =====
// Toggle Sidebar
document.getElementById('toggleSidebar').addEventListener('click', () => {
    const sidebar = document.getElementById('sidebar');
    sidebar.classList.toggle('collapsed');
    const icon = document.querySelector('#toggleSidebar i');
    icon.classList.toggle('fa-angle-left');
    icon.classList.toggle('fa-angle-right');
});

// Submenu Toggle
document.querySelectorAll('.sidebar-toggle').forEach(btn => {
    btn.addEventListener('click', e => {
        e.preventDefault();
        btn.classList.toggle('active');
        const submenu = btn.nextElementSibling;
        submenu.classList.toggle('active');
    });
});

// ===== FOOTER ICONS =====
document.getElementById('settings').addEventListener('click', () => {
    window.location.href = 'modules/configuracion.php';
});

document.getElementById('notifications').addEventListener('click', () => {
    const panel = document.getElementById('notificationsPanel');
    panel.classList.toggle('active');
    if (panel.classList.contains('active')) {
        cargarNotificaciones();
    }
});

// Función para cerrar el panel de notificaciones
function cerrarNotificaciones() {
    document.getElementById('notificationsPanel').classList.remove('active');
}

// Función para cargar notificaciones
function cargarNotificaciones() {
    const notificationsBody = document.getElementById('notificationsBody');
    const notificaciones = [];
    
    <?php if ($productosStockBajo > 0): ?>
    notificaciones.push({
        type: 'warning',
        icon: 'fa-exclamation-triangle',
        title: 'Stock Bajo Detectado',
        message: '<?= $productosStockBajo ?> producto(s) necesitan reabastecimiento',
        time: 'Ahora',
        action: () => window.location.href = 'modules/productos.php'
    });
    <?php endif; ?>
    
    <?php 
    $ventasHoy = 0;
    try {
        $result = $mysqli->query("SELECT COUNT(*) as total FROM ventas WHERE DATE(fecha) = CURDATE()");
        if ($result) {
            $ventasHoy = $result->fetch_assoc()['total'];
        }
    } catch (Exception $e) {}
    
    if ($ventasHoy > 0): 
    ?>
    notificaciones.push({
        type: 'success',
        icon: 'fa-check-circle',
        title: 'Ventas del Día',
        message: '<?= $ventasHoy ?> venta(s) realizadas hoy',
        time: 'Hoy',
        action: () => window.location.href = 'modules/ventas.php'
    });
    <?php endif; ?>
    
    <?php if ($totalProductos > 0): ?>
    notificaciones.push({
        type: 'info',
        icon: 'fa-info-circle',
        title: 'Sistema Activo',
        message: 'Inventario con <?= $totalProductos ?> productos registrados',
        time: new Date().toLocaleTimeString('es-ES', {hour: '2-digit', minute: '2-digit'}),
        action: null
    });
    <?php endif; ?>
    
    notificaciones.push({
        type: 'info',
        icon: 'fa-sync',
        title: 'Última Sincronización',
        message: 'Sistema actualizado correctamente',
        time: new Date().toLocaleTimeString('es-ES', {hour: '2-digit', minute: '2-digit'}),
        action: null
    });
    
    if (notificaciones.length === 0) {
        notificationsBody.innerHTML = `
            <div class="no-notifications">
                <i class="fas fa-bell-slash"></i>
                <p>No hay notificaciones nuevas</p>
            </div>
        `;
        return;
    }
    
    notificationsBody.innerHTML = notificaciones.map(notif => `
        <div class="notification-item ${notif.type}" ${notif.action ? 'onclick="' + notif.action + '"' : ''} style="${notif.action ? 'cursor: pointer;' : 'cursor: default;'}">
            <div class="notif-title">
                <i class="fas ${notif.icon} notif-icon"></i>
                ${notif.title}
            </div>
            <div class="notif-message">${notif.message}</div>
            <div class="notif-time"><i class="fas fa-clock"></i> ${notif.time}</div>
        </div>
    `).join('');
    
    // Actualizar badge de notificaciones
    actualizarBadgeNotificaciones(notificaciones.length);
}

// Función para actualizar el badge de notificaciones
function actualizarBadgeNotificaciones(count) {
    const bellIcon = document.getElementById('notifications');
    let badge = bellIcon.querySelector('.notification-badge');
    
    if (count > 0) {
        if (!badge) {
            badge = document.createElement('span');
            badge.className = 'notification-badge';
            bellIcon.appendChild(badge);
        }
        badge.textContent = count > 9 ? '9+' : count;
    } else if (badge) {
        badge.remove();
    }
}

// Cargar notificaciones al inicio y actualizar badge
document.addEventListener('DOMContentLoaded', () => {
    // Cargar notificaciones inicialmente para contar
    const notificacionesIniciales = [];
    <?php if ($productosStockBajo > 0): ?>
    notificacionesIniciales.push(1);
    <?php endif; ?>
    <?php if (isset($ventasHoy) && $ventasHoy > 0): ?>
    notificacionesIniciales.push(1);
    <?php endif; ?>
    
    actualizarBadgeNotificaciones(notificacionesIniciales.length);
});

// Cerrar panel al hacer clic fuera
document.addEventListener('click', (e) => {
    const panel = document.getElementById('notificationsPanel');
    const bellIcon = document.getElementById('notifications');
    
    if (panel.classList.contains('active') && 
        !panel.contains(e.target) && 
        !bellIcon.contains(e.target)) {
        cerrarNotificaciones();
    }
});


document.getElementById('userInfo').addEventListener('click', () => {
    document.getElementById('userModal').classList.add('active');
    document.getElementById('modalOverlay').classList.add('active');
});

function cerrarModal() {
    document.getElementById('userModal').classList.remove('active');
    document.getElementById('modalOverlay').classList.remove('active');
}

document.getElementById('modalOverlay').addEventListener('click', cerrarModal);

// ===== CALENDAR / TURNOS SYSTEM =====
const esAdmin = <?= $_SESSION['rol'] === 'Administrador' ? 'true' : 'false' ?>;
let turnosData = [];
let turnoEditandoId = null;

// Abrir modal de turnos
function abrirModalTurnos() {
    document.getElementById('modalTurnos').classList.add('active');
    document.getElementById('modalOverlay').classList.add('active');
    cargarTurnos();
}

// Cerrar modal de turnos
function cerrarModalTurnos() {
    document.getElementById('modalTurnos').classList.remove('active');
    document.getElementById('modalOverlay').classList.remove('active');
    ocultarFormTurno();
}

// Cargar turnos desde la API
async function cargarTurnos() {
    try {
        const response = await fetch('turnos_api.php?action=listar');
        const data = await response.json();
        
        if (data.success) {
            turnosData = data.turnos;
            actualizarEstadisticas();
            renderizarTurnos();
            actualizarCalendarioSidebar();
        } else {
            mostrarError('Error al cargar turnos: ' + data.message);
        }
    } catch (error) {
        mostrarError('Error de conexión: ' + error.message);
    }
}

// Actualizar estadísticas
function actualizarEstadisticas() {
    const ahora = new Date();
    const inicioSemana = new Date(ahora);
    inicioSemana.setDate(ahora.getDate() - ahora.getDay());
    const inicioMes = new Date(ahora.getFullYear(), ahora.getMonth(), 1);
    
    const totalTurnos = turnosData.length;
    const turnosSemana = turnosData.filter(t => new Date(t.fecha_inicio) >= inicioSemana).length;
    const turnosMes = turnosData.filter(t => new Date(t.fecha_inicio) >= inicioMes).length;
    
    document.getElementById('statTotalTurnos').textContent = totalTurnos;
    document.getElementById('statTurnosSemana').textContent = turnosSemana;
    document.getElementById('statTurnosMes').textContent = turnosMes;
}

// Renderizar lista de turnos
function renderizarTurnos() {
    const grid = document.getElementById('turnosGrid');
    
    if (turnosData.length === 0) {
        grid.innerHTML = `
            <div class="no-turnos">
                <i class="fas fa-calendar-times"></i>
                <p>No hay turnos registrados</p>
                ${esAdmin ? '<button class="btn-nuevo-turno" onclick="mostrarFormTurno()" style="margin-top: 20px;"><i class="fas fa-plus"></i> Crear Primer Turno</button>' : ''}
            </div>
        `;
        return;
    }
    
    grid.innerHTML = turnosData.map(turno => `
        <div class="turno-card">
            <div class="turno-card-header">
                <h3 class="turno-titulo">${escapeHtml(turno.titulo)}</h3>
            </div>
            
            ${turno.descripcion ? `<p class="turno-descripcion">${escapeHtml(turno.descripcion)}</p>` : ''}
            
            <div class="turno-fechas">
                <div class="turno-fecha-item">
                    <i class="fas fa-play-circle"></i>
                    <strong>Inicio:</strong> ${formatearFecha(turno.fecha_inicio)}
                </div>
                ${turno.fecha_fin ? `
                <div class="turno-fecha-item">
                    <i class="fas fa-stop-circle"></i>
                    <strong>Fin:</strong> ${formatearFecha(turno.fecha_fin)}
                </div>
                ` : ''}
            </div>
            
            ${esAdmin ? `
            <div class="turno-actions">
                <button class="btn-turno-action btn-turno-editar" onclick="editarTurno(${turno.id})">
                    <i class="fas fa-edit"></i> Editar
                </button>
                <button class="btn-turno-action btn-turno-eliminar" onclick="eliminarTurno(${turno.id}, '${escapeHtml(turno.titulo)}')">
                    <i class="fas fa-trash"></i> Eliminar
                </button>
            </div>
            ` : ''}
        </div>
    `).join('');
}

// Actualizar el calendario del sidebar
function actualizarCalendarioSidebar() {
    const calendar = document.getElementById('calendar');
    
    if (turnosData.length === 0) {
        calendar.innerHTML = '<p style="margin: 5px 0; color: var(--color-light-grey-text); font-size: 0.85em;"><i class="fas fa-info-circle"></i> No hay turnos programados</p>';
        return;
    }
    
    // Mostrar solo los próximos 3 turnos
    const proximosTurnos = turnosData.slice(0, 3);
    calendar.innerHTML = proximosTurnos.map(turno => {
        const fecha = new Date(turno.fecha_inicio);
        const dia = fecha.toLocaleDateString('es-ES', { weekday: 'long' });
        const hora = fecha.toLocaleTimeString('es-ES', { hour: '2-digit', minute: '2-digit' });
        return `<p style="margin: 5px 0; font-size: 0.85em;">🕐 ${dia}: ${hora}</p>`;
    }).join('');
}

// Mostrar formulario de turno
function mostrarFormTurno() {
    if (!esAdmin) {
        alert('⚠️ Solo administradores pueden agregar turnos');
        return;
    }
    
    turnoEditandoId = null;
    document.getElementById('formTurnoTitulo').innerHTML = '<i class="fas fa-plus-circle"></i> Nuevo Turno';
    document.getElementById('btnGuardarTexto').textContent = 'Guardar Turno';
    document.getElementById('formTurno').reset();
    document.getElementById('turnoId').value = '';
    document.getElementById('formTurnoContainer').classList.add('active');
    document.getElementById('turnoTitulo').focus();
}

// Ocultar formulario de turno
function ocultarFormTurno() {
    document.getElementById('formTurnoContainer').classList.remove('active');
    document.getElementById('formTurno').reset();
    turnoEditandoId = null;
}

// Editar turno
function editarTurno(id) {
    if (!esAdmin) {
        alert('⚠️ Solo administradores pueden editar turnos');
        return;
    }
    
    const turno = turnosData.find(t => t.id == id);
    if (!turno) return;
    
    turnoEditandoId = id;
    document.getElementById('formTurnoTitulo').innerHTML = '<i class="fas fa-edit"></i> Editar Turno';
    document.getElementById('btnGuardarTexto').textContent = 'Actualizar Turno';
    document.getElementById('turnoId').value = turno.id;
    document.getElementById('turnoTitulo').value = turno.titulo;
    document.getElementById('turnoDescripcion').value = turno.descripcion || '';
    document.getElementById('turnoFechaInicio').value = turno.fecha_inicio.replace(' ', 'T');
    document.getElementById('turnoFechaFin').value = turno.fecha_fin ? turno.fecha_fin.replace(' ', 'T') : '';
    document.getElementById('formTurnoContainer').classList.add('active');
    document.getElementById('turnoTitulo').focus();
}

// Guardar turno (crear o actualizar)
async function guardarTurno(event) {
    event.preventDefault();
    
    if (!esAdmin) {
        alert('⚠️ Solo administradores pueden guardar turnos');
        return;
    }
    
    const formData = new FormData(event.target);
    const action = turnoEditandoId ? 'editar' : 'agregar';
    formData.append('action', action);
    
    try {
        const response = await fetch('turnos_api.php', {
            method: 'POST',
            body: formData
        });
        
        const data = await response.json();
        
        if (data.success) {
            mostrarExito(data.message);
            ocultarFormTurno();
            cargarTurnos();
        } else {
            mostrarError(data.message);
        }
    } catch (error) {
        mostrarError('Error de conexión: ' + error.message);
    }
}

// Eliminar turno
async function eliminarTurno(id, titulo) {
    if (!esAdmin) {
        alert('⚠️ Solo administradores pueden eliminar turnos');
        return;
    }
    
    if (!confirm(`¿Estás seguro de eliminar el turno "${titulo}"?\n\nEsta acción no se puede deshacer.`)) {
        return;
    }
    
    try {
        const formData = new FormData();
        formData.append('action', 'eliminar');
        formData.append('id', id);
        
        const response = await fetch('turnos_api.php', {
            method: 'POST',
            body: formData
        });
        
        const data = await response.json();
        
        if (data.success) {
            mostrarExito(data.message);
            cargarTurnos();
        } else {
            mostrarError(data.message);
        }
    } catch (error) {
        mostrarError('Error de conexión: ' + error.message);
    }
}

// Funciones auxiliares
function formatearFecha(fechaStr) {
    const fecha = new Date(fechaStr);
    return fecha.toLocaleString('es-ES', {
        year: 'numeric',
        month: 'long',
        day: 'numeric',
        hour: '2-digit',
        minute: '2-digit'
    });
}

function escapeHtml(text) {
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}

function mostrarExito(mensaje) {
    alert('✅ ' + mensaje);
}

function mostrarError(mensaje) {
    alert('❌ ' + mensaje);
}

// Cargar turnos al iniciar la página
document.addEventListener('DOMContentLoaded', () => {
    cargarTurnos();
});

// ===== CHART.JS - Sales Chart (Solo Admin) =====
<?php if ($es_admin): ?>
const ctx = document.getElementById('salesChart');
if (ctx) {
    const salesData = <?= json_encode($ventasPorDia) ?>;
    
    const labels = salesData.map(item => 'Día ' + item.dia);
    const data = salesData.map(item => parseFloat(item.total));
    
    // Si no hay datos, mostrar ejemplo
    if (data.length === 0) {
        labels.push('Día 1', 'Día 2', 'Día 3', 'Día 4', 'Día 5');
        data.push(0, 0, 0, 0, 0);
    }
    
    const salesChart = new Chart(ctx.getContext('2d'), {
    type: 'line',
    data: {
        labels: labels,
        datasets: [{
            label: 'Ventas ($)',
            data: data,
            borderColor: '#007bff',
            backgroundColor: 'rgba(0, 123, 255, 0.1)',
            borderWidth: 3,
            fill: true,
            tension: 0.4,
            pointRadius: 5,
            pointHoverRadius: 7,
            pointBackgroundColor: '#007bff',
            pointBorderColor: '#fff',
            pointBorderWidth: 2
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
            legend: {
                display: true,
                position: 'top',
                labels: {
                    font: { size: 14, weight: 'bold' },
                    color: '#000'
                }
            },
            tooltip: {
                backgroundColor: 'rgba(0, 0, 0, 0.8)',
                padding: 12,
                titleFont: { size: 14 },
                bodyFont: { size: 13 },
                callbacks: {
                    label: function(context) {
                        return 'Ventas: $' + context.parsed.y.toLocaleString();
                    }
                }
            }
        },
        scales: {
            y: {
                beginAtZero: true,
                ticks: {
                    callback: function(value) {
                        return '$' + value.toLocaleString();
                    },
                    font: { size: 12 }
                },
                grid: {
                    color: 'rgba(0, 0, 0, 0.05)'
                }
            },
            x: {
                ticks: {
                    font: { size: 12 }
                },
                grid: {
                    display: false
                }
            }
        }
    }
});
}
<?php endif; ?>

// ===== FADE IN ANIMATION ON SCROLL =====
const observerOptions = {
    threshold: 0.1,
    rootMargin: '0px 0px -50px 0px'
};

const observer = new IntersectionObserver((entries) => {
    entries.forEach(entry => {
        if (entry.isIntersecting) {
            entry.target.style.opacity = '1';
            entry.target.style.transform = 'translateY(0)';
        }
    });
}, observerOptions);

document.querySelectorAll('.fade-in').forEach(el => {
    el.style.opacity = '0';
    el.style.transform = 'translateY(20px)';
    el.style.transition = 'opacity 0.6s ease, transform 0.6s ease';
    observer.observe(el);
});

// Welcome message
console.log('%c🎉 Bienvenido a InModa! ', 'background: #007bff; color: white; font-size: 20px; padding: 10px; border-radius: 5px;');
console.log('%cSistema cargado correctamente ✅', 'color: #28a745; font-size: 14px;');
</script>

</body>
</html>