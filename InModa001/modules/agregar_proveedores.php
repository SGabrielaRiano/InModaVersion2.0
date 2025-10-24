<?php
session_start();

// Solo el administrador puede agregar proveedores
if (!isset($_SESSION['user_id']) || $_SESSION['rol'] !== 'Administrador') {
    header("Location: ../index.php");
    exit();
}

require_once "../config/conexion.php";

$mensaje = "";
$tipo_mensaje = "";

// Procesar el formulario cuando se envía
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nombre = trim($_POST['nombre']);
    $contacto = trim($_POST['contacto']);
    $telefono = trim($_POST['telefono']);
    $email = trim($_POST['email']);
    $direccion = trim($_POST['direccion']);

    if (empty($nombre)) {
        $mensaje = "El nombre del proveedor es requerido";
        $tipo_mensaje = "error";
    } else {
        try {
            $stmt = $mysqli->prepare("INSERT INTO proveedores (nombre, contacto, telefono, email, direccion) VALUES (?, ?, ?, ?, ?)");
            $stmt->bind_param("sssss", $nombre, $contacto, $telefono, $email, $direccion);
            
            if ($stmt->execute()) {
                $mensaje = "¡Proveedor agregado exitosamente!";
                $tipo_mensaje = "success";
                $_POST = array(); // Limpiar formulario
            } else {
                $mensaje = "Error al agregar proveedor";
                $tipo_mensaje = "error";
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
<title>InModa - Agregar Proveedor</title>
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

* { margin: 0; padding: 0; box-sizing: border-box; }

body {
    font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
    background-color: var(--color-background-main);
    padding: 30px;
}

.container { max-width: 900px; margin: 0 auto; }

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

.btn-back:hover { background: var(--color-hover-grey); }

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

.form-header i { color: var(--color-primary-blue); }

.alert {
    padding: 15px 20px;
    border-radius: 8px;
    margin-bottom: 25px;
    display: flex;
    align-items: center;
    gap: 10px;
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

.form-group.full-width { grid-column: span 2; }

.form-group label {
    margin-bottom: 8px;
    font-weight: 600;
    color: var(--color-text-black);
    display: flex;
    align-items: center;
    gap: 8px;
}

.form-group label i { color: var(--color-primary-blue); }

.form-group input,
.form-group textarea {
    padding: 12px 15px;
    border: 2px solid var(--color-secondary-grey);
    border-radius: 8px;
    font-size: 1em;
    transition: all 0.3s ease;
    font-family: inherit;
}

.form-group input:focus,
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
    text-decoration: none;
}

.btn-cancel:hover { background: var(--color-hover-grey); }

.required { color: var(--color-red); }

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
    <a href="proveedores.php" class="btn-back">
        <i class="fas fa-arrow-left"></i> Volver a Proveedores
    </a>

    <div class="form-card">
        <div class="form-header">
            <h1>
                <i class="fas fa-truck-loading"></i>
                Agregar Nuevo Proveedor
            </h1>
        </div>

        <?php if (!empty($mensaje)): ?>
        <div class="alert alert-<?= $tipo_mensaje ?>">
            <i class="fas fa-<?= $tipo_mensaje === 'success' ? 'check-circle' : 'exclamation-triangle' ?>"></i>
            <?= htmlspecialchars($mensaje) ?>
        </div>
        <?php endif; ?>

        <form method="POST">
            <div class="form-grid">
                <div class="form-group full-width">
                    <label>
                        <i class="fas fa-building"></i>
                        Nombre del Proveedor <span class="required">*</span>
                    </label>
                    <input type="text" name="nombre" placeholder="Ej: Textiles Colombia S.A.S" required value="<?= htmlspecialchars($_POST['nombre'] ?? '') ?>">
                </div>

                <div class="form-group">
                    <label>
                        <i class="fas fa-user"></i>
                        Persona de Contacto
                    </label>
                    <input type="text" name="contacto" placeholder="Ej: Juan Pérez" value="<?= htmlspecialchars($_POST['contacto'] ?? '') ?>">
                </div>

                <div class="form-group">
                    <label>
                        <i class="fas fa-phone"></i>
                        Teléfono
                    </label>
                    <input type="tel" name="telefono" placeholder="+57 300 123 4567" value="<?= htmlspecialchars($_POST['telefono'] ?? '') ?>">
                </div>

                <div class="form-group full-width">
                    <label>
                        <i class="fas fa-envelope"></i>
                        Email
                    </label>
                    <input type="email" name="email" placeholder="contacto@proveedor.com" value="<?= htmlspecialchars($_POST['email'] ?? '') ?>">
                </div>

                <div class="form-group full-width">
                    <label>
                        <i class="fas fa-map-marker-alt"></i>
                        Dirección
                    </label>
                    <textarea name="direccion" placeholder="Dirección completa del proveedor..."><?= htmlspecialchars($_POST['direccion'] ?? '') ?></textarea>
                </div>
            </div>

            <div class="form-buttons">
                <button type="submit" class="btn btn-submit">
                    <i class="fas fa-save"></i> Guardar Proveedor
                </button>
                <a href="proveedores.php" class="btn btn-cancel">
                    <i class="fas fa-times"></i> Cancelar
                </a>
            </div>
        </form>
    </div>
</div>

</body>
</html>