<?php
session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: ../login.php");
    exit();
}

require_once "../config/conexion.php";

$rol_usuario = $_SESSION['rol'] ?? 'Vendedor';
$es_admin = ($rol_usuario === 'Administrador');

// Mensajes de sesión
$mensaje = $_SESSION['mensaje_exito'] ?? $_SESSION['mensaje_error'] ?? '';
$tipo_mensaje = isset($_SESSION['mensaje_exito']) ? 'success' : 'error';
unset($_SESSION['mensaje_exito'], $_SESSION['mensaje_error']);

// Obtener productos
$productos = [];
try {
    $result = $mysqli->query("SELECT p.*, pr.nombre as proveedor_nombre FROM productos p LEFT JOIN proveedores pr ON p.proveedor_id = pr.id ORDER BY p.nombre ASC");
    while ($row = $result->fetch_assoc()) {
        $productos[] = $row;
    }
} catch (Exception $e) {
    $error = "Error al cargar productos";
}

// Obtener proveedores para el formulario de edición
$proveedores = [];
try {
    $result_proveedores = $mysqli->query("SELECT id, nombre FROM proveedores ORDER BY nombre ASC");
    while ($row = $result_proveedores->fetch_assoc()) {
        $proveedores[] = $row;
    }
} catch (Exception $e) {
    // Error al cargar proveedores
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>InModa - Productos</title>
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
    --color-button-yellow: #FFD700;
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
}

.container {
    max-width: 1400px;
    margin: 0 auto;
    padding: 30px;
}

.page-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 30px;
    padding: 25px;
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    border-radius: 15px;
    color: white;
    box-shadow: 0 5px 20px rgba(102, 126, 234, 0.3);
}

.page-header h1 {
    font-size: 2.2em;
    margin: 0;
    display: flex;
    align-items: center;
    gap: 15px;
}

.header-actions {
    display: flex;
    gap: 15px;
}

.btn-primary {
    background: white;
    color: #667eea;
    padding: 12px 25px;
    border: none;
    border-radius: 8px;
    cursor: pointer;
    font-size: 1em;
    font-weight: 600;
    transition: all 0.3s ease;
    text-decoration: none;
    display: inline-flex;
    align-items: center;
    gap: 10px;
}

.btn-primary:hover {
    transform: translateY(-2px);
    box-shadow: 0 6px 20px rgba(255, 255, 255, 0.3);
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
    transition: all 0.3s ease;
    margin-bottom: 20px;
}

.btn-back:hover {
    background: var(--color-hover-grey);
}

.alert {
    padding: 15px 20px;
    border-radius: 10px;
    margin-bottom: 25px;
    display: flex;
    align-items: center;
    gap: 10px;
    animation: slideDown 0.4s ease;
}

@keyframes slideDown {
    from { opacity: 0; transform: translateY(-20px); }
    to { opacity: 1; transform: translateY(0); }
}

.alert-success {
    background: #d4edda;
    color: #155724;
    border-left: 4px solid var(--color-green);
}

.alert-error {
    background: #f8d7da;
    color: #721c24;
    border-left: 4px solid var(--color-red);
}

.search-filter-container {
    background: var(--color-white);
    padding: 20px;
    border-radius: 12px;
    margin-bottom: 25px;
    box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
}

.search-box {
    display: flex;
    gap: 15px;
    align-items: center;
}

.search-box input {
    flex: 1;
    padding: 12px 20px;
    border: 2px solid var(--color-secondary-grey);
    border-radius: 8px;
    font-size: 1em;
    transition: all 0.3s ease;
}

.search-box input:focus {
    outline: none;
    border-color: #667eea;
    box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
}

.products-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(320px, 1fr));
    gap: 25px;
    margin-bottom: 30px;
}

.product-card {
    background: white;
    border-radius: 15px;
    padding: 25px;
    box-shadow: 0 3px 15px rgba(0, 0, 0, 0.08);
    transition: all 0.3s ease;
    border: 2px solid transparent;
}

.product-card:hover {
    transform: translateY(-5px);
    box-shadow: 0 8px 25px rgba(102, 126, 234, 0.2);
    border-color: #667eea;
}

.product-header {
    display: flex;
    justify-content: space-between;
    align-items: start;
    margin-bottom: 15px;
    padding-bottom: 15px;
    border-bottom: 2px solid #f0f0f0;
}

.product-icon {
    width: 60px;
    height: 60px;
    background: linear-gradient(135deg, #667eea, #764ba2);
    border-radius: 12px;
    display: flex;
    align-items: center;
    justify-content: center;
    color: white;
    font-size: 1.8em;
}

.product-info {
    flex: 1;
    margin-left: 15px;
}

.product-info h3 {
    font-size: 1.3em;
    margin-bottom: 5px;
    color: var(--color-text-black);
}

.product-code {
    color: var(--color-light-grey-text);
    font-size: 0.9em;
    font-weight: 500;
}

.product-details {
    margin: 15px 0;
}

.detail-row {
    display: flex;
    justify-content: space-between;
    margin-bottom: 10px;
    padding: 8px 12px;
    background: #f8f9fa;
    border-radius: 8px;
}

.detail-label {
    color: var(--color-light-grey-text);
    font-size: 0.9em;
    font-weight: 500;
}

.detail-value {
    font-weight: 600;
    color: var(--color-text-black);
}

.price-section {
    background: linear-gradient(135deg, #667eea, #764ba2);
    color: white;
    padding: 15px;
    border-radius: 10px;
    text-align: center;
    margin: 15px 0;
}

.price-label {
    font-size: 0.85em;
    opacity: 0.9;
    margin-bottom: 5px;
}

.price-value {
    font-size: 2em;
    font-weight: 700;
}

.stock-badge {
    padding: 6px 12px;
    border-radius: 20px;
    font-size: 0.85em;
    font-weight: 600;
    display: inline-block;
}

.stock-high {
    background: #d4edda;
    color: #155724;
}

.stock-medium {
    background: #fff3cd;
    color: #856404;
}

.stock-low {
    background: #f8d7da;
    color: #721c24;
}

.action-buttons {
    display: flex;
    gap: 10px;
    margin-top: 15px;
}

.btn-action {
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
    gap: 5px;
}

.btn-edit {
    background: var(--color-button-yellow);
    color: var(--color-text-black);
}

.btn-edit:hover {
    background: #ffc107;
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(255, 215, 0, 0.3);
}

.btn-delete {
    background: var(--color-red);
    color: var(--color-white);
}

.btn-delete:hover {
    background: #c82333;
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(220, 53, 69, 0.3);
}

.no-products {
    text-align: center;
    padding: 60px 20px;
    color: var(--color-light-grey-text);
    background: white;
    border-radius: 15px;
    grid-column: 1 / -1;
}

.no-products i {
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

@keyframes fadeIn {
    from { opacity: 0; }
    to { opacity: 1; }
}

.modal-content {
    background-color: white;
    margin: 5% auto;
    padding: 0;
    border-radius: 15px;
    width: 90%;
    max-width: 600px;
    box-shadow: 0 10px 40px rgba(0, 0, 0, 0.3);
    animation: slideIn 0.3s ease;
}

@keyframes slideIn {
    from { transform: translateY(-50px); opacity: 0; }
    to { transform: translateY(0); opacity: 1; }
}

.modal-header {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
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

.close {
    color: white;
    font-size: 28px;
    font-weight: bold;
    cursor: pointer;
    transition: all 0.3s ease;
}

.close:hover {
    transform: rotate(90deg);
}

.modal-body {
    padding: 25px;
}

.form-group {
    margin-bottom: 20px;
}

.form-group label {
    display: block;
    margin-bottom: 8px;
    color: var(--color-text-black);
    font-weight: 600;
    font-size: 0.95em;
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

.form-group input:focus,
.form-group select:focus,
.form-group textarea:focus {
    outline: none;
    border-color: #667eea;
    box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
}

.modal-footer {
    padding: 20px 25px;
    background: #f8f9fa;
    border-radius: 0 0 15px 15px;
    display: flex;
    gap: 10px;
    justify-content: flex-end;
}

.btn-modal {
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

.btn-save {
    background: var(--color-green);
    color: white;
}

.btn-save:hover {
    background: #218838;
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(40, 167, 69, 0.3);
}

.btn-cancel {
    background: var(--color-secondary-grey);
    color: var(--color-text-black);
}

.btn-cancel:hover {
    background: var(--color-hover-grey);
}

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
@media (max-width: 1200px) {
    .products-grid {
        grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
    }
}

@media (max-width: 768px) {
    .container {
        padding: 15px;
    }
    
    .products-grid {
        grid-template-columns: 1fr;
    }
    
    .page-header {
        flex-direction: column;
        gap: 15px;
        text-align: center;
    }
    
    .page-header h1 {
        font-size: 1.8em;
    }
    
    .modal-content {
        width: 95%;
        margin: 10% auto;
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
            <i class="fas fa-tshirt"></i> Gestión de Productos
        </h1>
        <div class="header-actions">
            <a href="agregar_producto.php" class="btn-primary">
                <i class="fas fa-plus-circle"></i> Agregar Producto
            </a>
        </div>
    </div>

    <?php if ($mensaje): ?>
    <div class="alert alert-<?= $tipo_mensaje ?>">
        <i class="fas fa-<?= $tipo_mensaje === 'success' ? 'check-circle' : 'exclamation-triangle' ?>"></i>
        <?= htmlspecialchars($mensaje) ?>
    </div>
    <?php endif; ?>

    <div class="search-filter-container">
        <div class="search-box">
            <i class="fas fa-search" style="color: var(--color-light-grey-text);"></i>
            <input type="text" id="searchInput" placeholder="Buscar por nombre, código o categoría...">
        </div>
    </div>

    <div class="products-grid" id="productsGrid">
        <?php if (count($productos) > 0): ?>
            <?php foreach ($productos as $producto): ?>
            <div class="product-card" data-search="<?= strtolower($producto['nombre'] . ' ' . $producto['codigo'] . ' ' . $producto['categoria']) ?>">
                <div class="product-header">
                    <div class="product-icon">
                        <i class="fas fa-tshirt"></i>
                    </div>
                    <div class="product-info">
                        <h3><?= htmlspecialchars($producto['nombre']) ?></h3>
                        <p class="product-code">Código: <?= htmlspecialchars($producto['codigo']) ?></p>
                    </div>
                </div>

                <div class="product-details">
                    <div class="detail-row">
                        <span class="detail-label"><i class="fas fa-tag"></i> Categoría:</span>
                        <span class="detail-value"><?= htmlspecialchars($producto['categoria'] ?? 'Sin categoría') ?></span>
                    </div>
                    <div class="detail-row">
                        <span class="detail-label"><i class="fas fa-boxes"></i> Stock:</span>
                        <span class="detail-value">
                            <?php
                            $stock = $producto['stock'];
                            $class = 'stock-high';
                            if ($stock < 10) $class = 'stock-low';
                            elseif ($stock < 30) $class = 'stock-medium';
                            ?>
                            <span class="stock-badge <?= $class ?>">
                                <?= $stock ?> unidades
                            </span>
                        </span>
                    </div>
                    <div class="detail-row">
                        <span class="detail-label"><i class="fas fa-truck"></i> Proveedor:</span>
                        <span class="detail-value"><?= htmlspecialchars($producto['proveedor_nombre'] ?? 'Sin proveedor') ?></span>
                    </div>
                </div>

                <div class="price-section">
                    <div class="price-label">Precio de Venta</div>
                    <div class="price-value">$<?= number_format($producto['precio_venta'] ?? $producto['precio'], 0, ',', '.') ?></div>
                </div>

                <div class="action-buttons">
                    <button onclick='abrirModalEditar(<?= json_encode($producto, JSON_HEX_APOS | JSON_HEX_QUOT) ?>)' class="btn-action btn-edit">
                        <i class="fas fa-edit"></i> Editar
                    </button>
                    <button onclick="abrirModalEliminar(<?= $producto['id'] ?>, '<?= htmlspecialchars($producto['nombre']) ?>')" class="btn-action btn-delete">
                        <i class="fas fa-trash"></i> Eliminar
                    </button>
                </div>
            </div>
            <?php endforeach; ?>
        <?php else: ?>
        <div class="no-products">
            <i class="fas fa-box-open"></i>
            <h2>No hay productos registrados</h2>
            <p>Comienza agregando tu primer producto</p>
            <a href="agregar_producto.php" class="btn-primary" style="margin-top: 20px;">
                <i class="fas fa-plus-circle"></i> Agregar Producto
            </a>
        </div>
        <?php endif; ?>
    </div>
</div>

<!-- Modal Editar Producto -->
<div id="modalEditar" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <h2><i class="fas fa-edit"></i> Editar Producto</h2>
            <span class="close" onclick="cerrarModalEditar()">&times;</span>
        </div>
        <form id="formEditarProducto" onsubmit="guardarEdicion(event)">
            <div class="modal-body">
                <input type="hidden" id="edit_id" name="id">
                
                <div class="form-group">
                    <label for="edit_nombre"><i class="fas fa-tag"></i> Nombre del Producto</label>
                    <input type="text" id="edit_nombre" name="nombre" required>
                </div>
                
                <div class="form-group">
                    <label for="edit_codigo"><i class="fas fa-barcode"></i> Código</label>
                    <input type="text" id="edit_codigo" name="codigo" required>
                </div>
                
                <div class="form-group">
                    <label for="edit_categoria"><i class="fas fa-list"></i> Categoría</label>
                    <input type="text" id="edit_categoria" name="categoria">
                </div>
                
                <div class="form-group">
                    <label for="edit_precio_venta"><i class="fas fa-dollar-sign"></i> Precio de Venta</label>
                    <input type="number" id="edit_precio_venta" name="precio_venta" step="0.01" required>
                </div>
                
                <div class="form-group">
                    <label for="edit_stock"><i class="fas fa-boxes"></i> Stock</label>
                    <input type="number" id="edit_stock" name="stock" required>
                </div>
                
                <div class="form-group">
                    <label for="edit_proveedor_id"><i class="fas fa-truck"></i> Proveedor</label>
                    <select id="edit_proveedor_id" name="proveedor_id">
                        <option value="">Sin proveedor</option>
                        <?php foreach ($proveedores as $proveedor): ?>
                        <option value="<?= $proveedor['id'] ?>"><?= htmlspecialchars($proveedor['nombre']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn-modal btn-cancel" onclick="cerrarModalEditar()">
                    <i class="fas fa-times"></i> Cancelar
                </button>
                <button type="submit" class="btn-modal btn-save">
                    <i class="fas fa-save"></i> Guardar Cambios
                </button>
            </div>
        </form>
    </div>
</div>

<!-- Modal Eliminar Producto -->
<div id="modalEliminar" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <h2><i class="fas fa-exclamation-triangle"></i> Confirmar Eliminación</h2>
            <span class="close" onclick="cerrarModalEliminar()">&times;</span>
        </div>
        <div class="delete-modal-body">
            <i class="fas fa-trash-alt"></i>
            <h3>¿Estás seguro?</h3>
            <p>¿Deseas eliminar el producto</p>
            <p><strong id="nombre_producto_eliminar"></strong>?</p>
            <p style="color: var(--color-red); margin-top: 15px;">Esta acción no se puede deshacer.</p>
        </div>
        <div class="modal-footer">
            <button type="button" class="btn-modal btn-cancel" onclick="cerrarModalEliminar()">
                <i class="fas fa-times"></i> Cancelar
            </button>
            <button type="button" class="btn-modal btn-confirm-delete" onclick="confirmarEliminacion()">
                <i class="fas fa-trash"></i> Sí, Eliminar
            </button>
        </div>
    </div>
</div>

<script>
let productoIdEliminar = null;

// Búsqueda en tiempo real
document.getElementById('searchInput').addEventListener('keyup', function() {
    const searchValue = this.value.toLowerCase();
    const cards = document.querySelectorAll('.product-card');
    
    let visibleCount = 0;
    
    cards.forEach(card => {
        const searchText = card.getAttribute('data-search');
        if (searchText.includes(searchValue)) {
            card.style.display = '';
            visibleCount++;
        } else {
            card.style.display = 'none';
        }
    });
    
    // Mostrar mensaje si no hay resultados
    const grid = document.getElementById('productsGrid');
    let noResults = document.getElementById('noResultsMessage');
    
    if (visibleCount === 0 && searchValue.length > 0) {
        if (!noResults) {
            noResults = document.createElement('div');
            noResults.id = 'noResultsMessage';
            noResults.className = 'no-products';
            noResults.innerHTML = `
                <i class="fas fa-search"></i>
                <h2>No se encontraron resultados</h2>
                <p>Intenta con otros términos de búsqueda</p>
            `;
            grid.appendChild(noResults);
        }
    } else if (noResults) {
        noResults.remove();
    }
});

// Funciones Modal Editar
function abrirModalEditar(producto) {
    document.getElementById('edit_id').value = producto.id;
    document.getElementById('edit_nombre').value = producto.nombre;
    document.getElementById('edit_codigo').value = producto.codigo;
    document.getElementById('edit_categoria').value = producto.categoria || '';
    document.getElementById('edit_precio_venta').value = producto.precio_venta || producto.precio;
    document.getElementById('edit_stock').value = producto.stock;
    document.getElementById('edit_proveedor_id').value = producto.proveedor_id || '';
    
    document.getElementById('modalEditar').style.display = 'block';
}

function cerrarModalEditar() {
    document.getElementById('modalEditar').style.display = 'none';
}

function guardarEdicion(event) {
    event.preventDefault();
    
    const formData = new FormData(event.target);
    
    fetch('actualizar_producto.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            cerrarModalEditar();
            location.reload();
        } else {
            alert('Error al actualizar el producto: ' + (data.message || 'Error desconocido'));
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('Error al actualizar el producto');
    });
}

// Funciones Modal Eliminar
function abrirModalEliminar(id, nombre) {
    productoIdEliminar = id;
    document.getElementById('nombre_producto_eliminar').textContent = nombre;
    document.getElementById('modalEliminar').style.display = 'block';
}

function cerrarModalEliminar() {
    document.getElementById('modalEliminar').style.display = 'none';
    productoIdEliminar = null;
}

function confirmarEliminacion() {
    if (productoIdEliminar) {
        fetch('eliminar_producto.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: 'id=' + productoIdEliminar
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                cerrarModalEliminar();
                location.reload();
            } else {
                alert('Error al eliminar el producto: ' + (data.message || 'Error desconocido'));
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('Error al eliminar el producto');
        });
    }
}

// Cerrar modales al hacer clic fuera de ellos
window.onclick = function(event) {
    if (event.target == document.getElementById('modalEditar')) {
        cerrarModalEditar();
    }
    if (event.target == document.getElementById('modalEliminar')) {
        cerrarModalEliminar();
    }
}
</script>

</body>
</html>