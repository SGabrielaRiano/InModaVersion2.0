<?php
session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: ../login.php");
    exit();
}

require_once "../config/conexion.php";

$mensaje = "";
$tipo_mensaje = "";

// Obtener proveedores para el select
$proveedores = [];
try {
    $result = $mysqli->query("SELECT id, nombre FROM proveedores ORDER BY nombre ASC");
    while ($row = $result->fetch_assoc()) {
        $proveedores[] = $row;
    }
} catch (Exception $e) {
    // Continuar sin proveedores
}

// Procesar formulario
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $codigo = trim($_POST['codigo']);
    $nombre = trim($_POST['nombre']);
    $categoria = trim($_POST['categoria']);
    $precio = floatval($_POST['precio']);
    $stock = intval($_POST['stock']);
    $proveedor_id = !empty($_POST['proveedor_id']) ? intval($_POST['proveedor_id']) : null;
    $descripcion = trim($_POST['descripcion']);

    if (empty($codigo) || empty($nombre) || $precio <= 0) {
        $mensaje = "Por favor completa todos los campos requeridos correctamente.";
        $tipo_mensaje = "error";
    } else {
        try {
            // Verificar si el código ya existe
            $stmt = $mysqli->prepare("SELECT id FROM productos WHERE codigo = ?");
            $stmt->bind_param("s", $codigo);
            $stmt->execute();
            $result = $stmt->get_result();

            if ($result->num_rows > 0) {
                $mensaje = "El código de producto ya existe. Por favor usa otro código.";
                $tipo_mensaje = "error";
            } else {
                // Insertar producto
                $stmt = $mysqli->prepare("INSERT INTO productos (codigo, nombre, categoria, precio, stock, proveedor_id, descripcion) VALUES (?, ?, ?, ?, ?, ?, ?)");
                $stmt->bind_param("sssdiis", $codigo, $nombre, $categoria, $precio, $stock, $proveedor_id, $descripcion);

                if ($stmt->execute()) {
                    $mensaje = "¡Producto agregado exitosamente!";
                    $tipo_mensaje = "success";
                    // Limpiar formulario
                    $_POST = array();
                } else {
                    $mensaje = "Error al agregar el producto. Inténtalo de nuevo.";
                    $tipo_mensaje = "error";
                }
            }
        } catch (Exception $e) {
            $mensaje = "Error: " . $e->getMessage();
            $tipo_mensaje = "error";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>InModa - Agregar Producto</title>
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
    padding: 30px;
}

.container {
    max-width: 900px;
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
    transition: all 0.3s ease;
    margin-bottom: 20px;
}

.btn-back:hover {
    background: var(--color-hover-grey);
}

.form-card {
    background: var(--color-white);
    border-radius: 15px;
    padding: 40px;
    box-shadow: 0 5px 20px rgba(0, 0, 0, 0.1);
}

.form-header {
    text-align: center;
    margin-bottom: 35px;
    padding-bottom: 20px;
    border-bottom: 3px solid var(--color-primary-blue);
}

.form-header h1 {
    font-size: 2.2em;
    color: var(--color-text-black);
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 15px;
}

.form-header i {
    color: var(--color-primary-blue);
}

.alert {
    padding: 15px 20px;
    border-radius: 8px;
    margin-bottom: 25px;
    display: flex;
    align-items: center;
    gap: 10px;
    animation: slideDown 0.4s ease;
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

.form-grid {
    display: grid;
    grid-template-columns: repeat(2, 1fr);
    gap: 25px;
}

.form-group {
    display: flex;
    flex-direction: column;
}

.form-group.full-width {
    grid-column: span 2;
}

.form-group label {
    margin-bottom: 8px;
    font-weight: 600;
    color: var(--color-text-black);
    display: flex;
    align-items: center;
    gap: 8px;
}

.form-group label i {
    color: var(--color-primary-blue);
}

.form-group input,
.form-group select,
.form-group textarea {
    padding: 12px 15px;
    border: 2px solid var(--color-secondary-grey);
    border-radius: 8px;
    font-size: 1em;
    transition: all 0.3s ease;
    font-family: inherit;
}

.form-group input:focus,
.form-group select:focus,
.form-group textarea:focus {
    outline: none;
    border-color: var(--color-primary-blue);
    box-shadow: 0 0 0 3px rgba(0, 123, 255, 0.1);
}

.form-group textarea {
    resize: vertical;
    min-height: 100px;
}

.form-buttons {
    display: flex;
    gap: 15px;
    margin-top: 30px;
}

.btn {
    flex: 1;
    padding: 14px 25px;
    border: none;
    border-radius: 8px;
    cursor: pointer;
    font-size: 1.05em;
    font-weight: 600;
    transition: all 0.3s ease;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 10px;
}

.btn-submit {
    background: linear-gradient(135deg, var(--color-primary-blue), #0056b3);
    color: var(--color-white);
    box-shadow: 0 4px 10px rgba(0, 123, 255, 0.3);
}

.btn-submit:hover {
    transform: translateY(-2px);
    box-shadow: 0 6px 15px rgba(0, 123, 255, 0.4);
}

.btn-cancel {
    background: var(--color-secondary-grey);
    color: var(--color-text-black);
}

.btn-cancel:hover {
    background: var(--color-hover-grey);
}

.required {
    color: var(--color-red);
}

@media (max-width: 768px) {
    .form-grid {
        grid-template-columns: 1fr;
    }
    
    .form-group.full-width {
        grid-column: span 1;
    }
}
</style>
</head>
<body>

<div class="container">
    <a href="productos.php" class="btn-back">
        <i class="fas fa-arrow-left"></i> Volver a Productos
    </a>

    <div class="form-card">
        <div class="form-header">
            <h1>
                <i class="fas fa-plus-circle"></i>
                Agregar Nuevo Producto
            </h1>
        </div>

        <?php if (!empty($mensaje)): ?>
        <div class="alert alert-<?= $tipo_mensaje ?>">
            <i class="fas fa-<?= $tipo_mensaje === 'success' ? 'check-circle' : 'exclamation-triangle' ?>"></i>
            <?= htmlspecialchars($mensaje) ?>
        </div>
        <?php endif; ?>

        <form method="POST" action="">
            <div class="form-grid">
                <div class="form-group">
                    <label>
                        <i class="fas fa-barcode"></i>
                        Código del Producto <span class="required">*</span>
                    </label>
                    <input type="text" name="codigo" placeholder="Ej: PROD001" required value="<?= htmlspecialchars($_POST['codigo'] ?? '') ?>">
                </div>

                <div class="form-group">
                    <label>
                        <i class="fas fa-tag"></i>
                        Nombre del Producto <span class="required">*</span>
                    </label>
                    <input type="text" name="nombre" placeholder="Ej: Camiseta Polo" required value="<?= htmlspecialchars($_POST['nombre'] ?? '') ?>">
                </div>

                <div class="form-group">
                    <label>
                        <i class="fas fa-list"></i>
                        Categoría
                    </label>
                    <input type="text" name="categoria" placeholder="Ej: Ropa, Accesorios" value="<?= htmlspecialchars($_POST['categoria'] ?? '') ?>">
                </div>

                <div class="form-group">
                    <label>
                        <i class="fas fa-dollar-sign"></i>
                        Precio <span class="required">*</span>
                    </label>
                    <input type="number" name="precio" placeholder="0.00" step="0.01" min="0" required value="<?= htmlspecialchars($_POST['precio'] ?? '') ?>">
                </div>

                <div class="form-group">
                    <label>
                        <i class="fas fa-boxes"></i>
                        Stock Inicial
                    </label>
                    <input type="number" name="stock" placeholder="0" min="0" value="<?= htmlspecialchars($_POST['stock'] ?? '0') ?>">
                </div>

                <div class="form-group">
                    <label>
                        <i class="fas fa-truck"></i>
                        Proveedor
                    </label>
                    <select name="proveedor_id">
                        <option value="">Sin proveedor</option>
                        <?php foreach ($proveedores as $proveedor): ?>
                            <option value="<?= $proveedor['id'] ?>" <?= (isset($_POST['proveedor_id']) && $_POST['proveedor_id'] == $proveedor['id']) ? 'selected' : '' ?>>
                                <?= htmlspecialchars($proveedor['nombre']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="form-group full-width">
                    <label>
                        <i class="fas fa-align-left"></i>
                        Descripción
                    </label>
                    <textarea name="descripcion" placeholder="Descripción detallada del producto..."><?= htmlspecialchars($_POST['descripcion'] ?? '') ?></textarea>
                </div>
            </div>

            <div class="form-buttons">
                <button type="submit" class="btn btn-submit">
                    <i class="fas fa-save"></i> Guardar Producto
                </button>
                <a href="productos.php" class="btn btn-cancel">
                    <i class="fas fa-times"></i> Cancelar
                </a>
            </div>
        </form>
    </div>
</div>

</body>
</html>