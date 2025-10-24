<?php
session_start();

// Verificar sesión
if (!isset($_SESSION['user_id'])) {
    header("Location: ../login.php");
    exit();
}

require_once "../config/conexion.php";

$rol_usuario = $_SESSION['rol'] ?? 'Vendedor';
$es_admin = ($rol_usuario === 'Administrador');

// El vendedor puede ver pero no modificar
$puede_editar = $es_admin;

// Obtener productos ordenados por stock (los de menor stock primero)
$productos = [];
$result = $mysqli->query("SELECT p.*, pr.nombre as proveedor FROM productos p LEFT JOIN proveedores pr ON p.proveedor_id = pr.id ORDER BY p.stock ASC");
while ($row = $result->fetch_assoc()) {
    $productos[] = $row;
}

// Calcular estadísticas del inventario
$stats = [
    'total' => count($productos),
    'stock_bajo' => 0,
    'sin_stock' => 0,
    'valor_total' => 0
];

foreach ($productos as $p) {
    if ($p['stock'] == 0) {
        $stats['sin_stock']++;
    } elseif ($p['stock'] < 10) {
        $stats['stock_bajo']++;
    }
    $stats['valor_total'] += $p['precio'] * $p['stock'];
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>InModa - Inventario</title>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
<style>
:root {
    --color-primary-blue: #007bff;
    --color-background-main: #f5f5dc;
    --color-secondary-grey: #d3d3d3;
    --color-text-black: #000;
    --color-white: #FFF;
    --color-light-grey-text: #666;
    --color-hover-grey: #bfbfbf;
    --color-green: #28a745;
    --color-red: #dc3545;
    --color-orange: #fd7e14;
}

* {
    margin: 0;
    padding: 0;
    box-sizing: border-box;
}

body {
    font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
    background: var(--color-background-main);
    padding: 30px;
}

.container {
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
    text-align: center;
    margin-bottom: 30px;
    padding-bottom: 20px;
    border-bottom: 3px solid var(--color-primary-blue);
}

.page-header h1 {
    font-size: 2.2em;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 15px;
    color: var(--color-text-black);
}

.page-header i {
    color: var(--color-primary-blue);
}

/* Tarjetas de estadísticas */
.stats-grid {
    display: grid;
    grid-template-columns: repeat(4, 1fr);
    gap: 20px;
    margin-bottom: 30px;
}

.stat-card {
    background: var(--color-white);
    border-radius: 12px;
    padding: 25px;
    box-shadow: 0 2px 10px rgba(0, 0, 0, 0.08);
    text-align: center;
    transition: all 0.3s ease;
}

.stat-card:hover {
    transform: translateY(-5px);
    box-shadow: 0 5px 20px rgba(0, 0, 0, 0.15);
}

.stat-card .icon {
    font-size: 2.5em;
    margin-bottom: 10px;
}

.stat-card h3 {
    font-size: 1.8em;
    margin-bottom: 5px;
    color: var(--color-text-black);
}

.stat-card p {
    color: var(--color-light-grey-text);
    font-size: 0.9em;
}

.card-blue .icon {
    color: var(--color-primary-blue);
}

.card-green .icon {
    color: var(--color-green);
}

.card-orange .icon {
    color: var(--color-orange);
}

.card-red .icon {
    color: var(--color-red);
}

/* Buscador */
.search-box {
    margin-bottom: 20px;
    background: var(--color-white);
    padding: 15px;
    border-radius: 8px;
    box-shadow: 0 2px 5px rgba(0, 0, 0, 0.05);
}

.search-box input {
    width: 100%;
    padding: 12px 15px;
    border: 2px solid var(--color-secondary-grey);
    border-radius: 8px;
    font-size: 1em;
    transition: all 0.3s ease;
}

.search-box input:focus {
    outline: none;
    border-color: var(--color-primary-blue);
    box-shadow: 0 0 0 3px rgba(0, 123, 255, 0.1);
}

/* Tabla de inventario */
.inventory-table {
    background: var(--color-white);
    border-radius: 12px;
    overflow: hidden;
    box-shadow: 0 2px 10px rgba(0, 0, 0, 0.08);
}

table {
    width: 100%;
    border-collapse: collapse;
}

thead {
    background: linear-gradient(135deg, var(--color-primary-blue), #0056b3);
    color: var(--color-white);
}

thead th {
    padding: 15px;
    text-align: left;
    font-weight: 600;
    font-size: 0.95em;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

tbody td {
    padding: 15px;
    border-bottom: 1px solid #f0f0f0;
    color: var(--color-text-black);
}

tbody tr {
    transition: all 0.3s ease;
}

tbody tr:hover {
    background: #f8f9fa;
}

/* Badges de estado de stock */
.stock-badge {
    padding: 5px 10px;
    border-radius: 15px;
    font-size: 0.85em;
    font-weight: 600;
    display: inline-block;
}

.stock-ok {
    background: #d4edda;
    color: #155724;
}

.stock-low {
    background: #fff3cd;
    color: #856404;
}

.stock-out {
    background: #f8d7da;
    color: #721c24;
}

/* Responsive */
@media (max-width: 1200px) {
    .stats-grid {
        grid-template-columns: repeat(2, 1fr);
    }
}

@media (max-width: 768px) {
    .stats-grid {
        grid-template-columns: 1fr;
    }
    
    .inventory-table {
        overflow-x: auto;
    }
    
    table {
        min-width: 700px;
    }
}
</style>
</head>
<body>

<div class="container">
    <a href="../index.php" class="btn-back">
        <i class="fas fa-arrow-left"></i> Volver al Panel
    </a>
    
    <div class="page-header">
        <h1>
            <i class="fas fa-warehouse"></i> Control de Inventario
        </h1>
    </div>

    <!-- Estadísticas del inventario -->
    <div class="stats-grid">
        <div class="stat-card card-blue">
            <div class="icon"><i class="fas fa-boxes"></i></div>
            <h3><?= $stats['total'] ?></h3>
            <p>Productos Totales</p>
        </div>
        
        <div class="stat-card card-green">
            <div class="icon"><i class="fas fa-dollar-sign"></i></div>
            <h3>$<?= number_format($stats['valor_total'], 0, ',', '.') ?></h3>
            <p>Valor en Inventario</p>
        </div>
        
        <div class="stat-card card-orange">
            <div class="icon"><i class="fas fa-exclamation-triangle"></i></div>
            <h3><?= $stats['stock_bajo'] ?></h3>
            <p>Stock Bajo (&lt;10)</p>
        </div>
        
        <div class="stat-card card-red">
            <div class="icon"><i class="fas fa-times-circle"></i></div>
            <h3><?= $stats['sin_stock'] ?></h3>
            <p>Sin Stock</p>
        </div>
    </div>

    <!-- Buscador -->
    <div class="search-box">
        <input type="text" id="searchInput" placeholder="🔍 Buscar producto por nombre, código o categoría...">
    </div>

    <!-- Tabla de inventario -->
    <div class="inventory-table">
        <table id="inventoryTable">
            <thead>
                <tr>
                    <th>Código</th>
                    <th>Producto</th>
                    <th>Categoría</th>
                    <th>Precio</th>
                    <th>Stock</th>
                    <th>Estado</th>
                    <th>Proveedor</th>
                </tr>
            </thead>
            <tbody>
                <?php if (count($productos) > 0): ?>
                    <?php foreach($productos as $p): ?>
                    <tr>
                        <td><strong><?= htmlspecialchars($p['codigo']) ?></strong></td>
                        <td><?= htmlspecialchars($p['nombre']) ?></td>
                        <td><?= htmlspecialchars($p['categoria'] ?? '-') ?></td>
                        <td><strong>$<?= number_format($p['precio'], 0, ',', '.') ?></strong></td>
                        <td><strong><?= $p['stock'] ?></strong></td>
                        <td>
                            <?php
                            if($p['stock'] == 0) {
                                $class = 'stock-out'; 
                                $text = 'Sin Stock';
                            } elseif($p['stock'] < 10) {
                                $class = 'stock-low'; 
                                $text = 'Stock Bajo';
                            } else {
                                $class = 'stock-ok'; 
                                $text = 'Disponible';
                            }
                            ?>
                            <span class="stock-badge <?= $class ?>"><?= $text ?></span>
                        </td>
                        <td><?= htmlspecialchars($p['proveedor'] ?? '-') ?></td>
                    </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="7" style="text-align: center; padding: 40px; color: var(--color-light-grey-text);">
                            <i class="fas fa-inbox" style="font-size: 3em; opacity: 0.3; display: block; margin-bottom: 15px;"></i>
                            No hay productos en el inventario
                        </td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<script>
// Búsqueda en tiempo real
document.getElementById('searchInput').addEventListener('input', function() {
    const search = this.value.toLowerCase();
    const rows = document.querySelectorAll('#inventoryTable tbody tr');
    
    rows.forEach(row => {
        const text = row.textContent.toLowerCase();
        row.style.display = text.includes(search) ? '' : 'none';
    });
});

// Resaltar productos con stock crítico
document.addEventListener('DOMContentLoaded', function() {
    const rows = document.querySelectorAll('#inventoryTable tbody tr');
    rows.forEach(row => {
        const stockBadge = row.querySelector('.stock-badge');
        if (stockBadge && stockBadge.classList.contains('stock-out')) {
            row.style.backgroundColor = 'rgba(220, 53, 69, 0.05)';
        } else if (stockBadge && stockBadge.classList.contains('stock-low')) {
            row.style.backgroundColor = 'rgba(255, 193, 7, 0.05)';
        }
    });
});
</script>

</body>
</html>