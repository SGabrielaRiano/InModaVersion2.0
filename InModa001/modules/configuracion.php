<?php
session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: ../login.php");
    exit();
}

require_once "../config/conexion.php";

$usuario = $_SESSION['usuario'] ?? 'Usuario';
$rol = $_SESSION['rol'] ?? 'Vendedor';
$empresa = $_SESSION['empresa'] ?? 'InModa';
$user_id = $_SESSION['user_id'];
$es_admin = ($rol === 'Administrador');

$mensaje = '';
$tipo_mensaje = '';

// Obtener datos del usuario actual
$usuario_data = [
    'nombre' => $usuario,
    'email' => '',
    'usuario' => $usuario
];

try {
    $stmt = $mysqli->prepare("SELECT nombre, usuario, email FROM usuarios WHERE id = ?");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($row = $result->fetch_assoc()) {
        $usuario_data = $row;
    }
} catch (Exception $e) {
    // Continuar con valores por defecto
}

// Cambiar contraseña
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['cambiar_password'])) {
    $password_actual = $_POST['password_actual'];
    $password_nueva = $_POST['password_nueva'];
    $password_confirmar = $_POST['password_confirmar'];
    
    if (empty($password_actual) || empty($password_nueva) || empty($password_confirmar)) {
        $mensaje = "Todos los campos son obligatorios";
        $tipo_mensaje = "error";
    } elseif ($password_nueva !== $password_confirmar) {
        $mensaje = "Las contraseñas nuevas no coinciden";
        $tipo_mensaje = "error";
    } elseif (strlen($password_nueva) < 4) {
        $mensaje = "La contraseña debe tener al menos 4 caracteres";
        $tipo_mensaje = "error";
    } else {
        try {
            $stmt = $mysqli->prepare("SELECT clave FROM usuarios WHERE id = ?");
            $stmt->bind_param("i", $user_id);
            $stmt->execute();
            $result = $stmt->get_result();
            $user_data = $result->fetch_assoc();
            
            if ($user_data['clave'] === $password_actual) {
                $stmt = $mysqli->prepare("UPDATE usuarios SET clave = ? WHERE id = ?");
                $stmt->bind_param("si", $password_nueva, $user_id);
                
                if ($stmt->execute()) {
                    $mensaje = "Contraseña cambiada exitosamente";
                    $tipo_mensaje = "success";
                }
            } else {
                $mensaje = "La contraseña actual es incorrecta";
                $tipo_mensaje = "error";
            }
        } catch (Exception $e) {
            $mensaje = "Error al cambiar contraseña: " . $e->getMessage();
            $tipo_mensaje = "error";
        }
    }
}

// Actualizar información personal
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['actualizar_info'])) {
    $nuevo_nombre = trim($_POST['nombre']);
    $nuevo_email = trim($_POST['email']);
    
    if (empty($nuevo_nombre)) {
        $mensaje = "El nombre es obligatorio";
        $tipo_mensaje = "error";
    } else {
        try {
            $stmt = $mysqli->prepare("UPDATE usuarios SET nombre = ?, email = ? WHERE id = ?");
            $stmt->bind_param("ssi", $nuevo_nombre, $nuevo_email, $user_id);
            
            if ($stmt->execute()) {
                $mensaje = "Información actualizada exitosamente";
                $tipo_mensaje = "success";
                $usuario_data['nombre'] = $nuevo_nombre;
                $usuario_data['email'] = $nuevo_email;
            }
        } catch (Exception $e) {
            $mensaje = "Error al actualizar información";
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
    <title>InModa - Configuración</title>
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
        }

        .config-container {
            padding: 30px;
            min-height: 100vh;
            max-width: 1200px;
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
            font-weight: 500;
        }

        .btn-back:hover {
            background: var(--color-hover-grey);
            transform: translateX(-5px);
        }

        .page-header {
            background: linear-gradient(135deg, #6f42c1, #5a32a3);
            color: white;
            padding: 25px 30px;
            border-radius: 15px;
            margin-bottom: 30px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.15);
        }

        .page-header h1 {
            margin: 0;
            font-size: 2em;
            display: flex;
            align-items: center;
            gap: 15px;
        }

        .page-header p {
            margin: 10px 0 0 0;
            opacity: 0.9;
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

        .config-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(450px, 1fr));
            gap: 25px;
        }

        .config-card {
            background: white;
            padding: 30px;
            border-radius: 15px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.08);
            transition: all 0.3s ease;
        }

        .config-card:hover {
            box-shadow: 0 5px 20px rgba(0,0,0,0.12);
            transform: translateY(-3px);
        }

        .config-card h3 {
            margin: 0 0 20px 0;
            font-size: 1.3em;
            color: #6f42c1;
            display: flex;
            align-items: center;
            gap: 10px;
            padding-bottom: 15px;
            border-bottom: 2px solid var(--color-secondary-grey);
        }

        .form-group {
            margin-bottom: 20px;
        }

        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
            color: var(--color-text-black);
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .form-group input,
        .form-group select {
            width: 100%;
            padding: 12px;
            border: 2px solid var(--color-secondary-grey);
            border-radius: 8px;
            font-size: 1em;
            transition: all 0.3s ease;
        }

        .form-group input:focus,
        .form-group select:focus {
            outline: none;
            border-color: #6f42c1;
            box-shadow: 0 0 0 3px rgba(111, 66, 193, 0.1);
        }

        .btn-submit {
            background: linear-gradient(135deg, #6f42c1, #5a32a3);
            color: white;
            border: none;
            padding: 12px 30px;
            border-radius: 8px;
            cursor: pointer;
            font-size: 1em;
            font-weight: 600;
            transition: all 0.3s ease;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            width: 100%;
            justify-content: center;
        }

        .btn-submit:hover {
            background: linear-gradient(135deg, #5a32a3, #4a2783);
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(111, 66, 193, 0.3);
        }

        .info-box {
            background: #e3f2fd;
            padding: 15px;
            border-radius: 10px;
            margin-bottom: 20px;
            border-left: 4px solid var(--color-primary-blue);
        }

        .info-box i {
            color: var(--color-primary-blue);
            margin-right: 10px;
        }

        .info-box p {
            margin: 5px 0;
            color: #1565c0;
        }

        .user-info-display {
            background: var(--color-background-main);
            padding: 15px;
            border-radius: 10px;
            margin-bottom: 15px;
        }

        .user-info-display strong {
            color: #6f42c1;
        }

        .password-strength {
            height: 5px;
            border-radius: 3px;
            margin-top: 5px;
            transition: all 0.3s ease;
        }

        .password-strength.weak {
            background: var(--color-red);
            width: 33%;
        }

        .password-strength.medium {
            background: orange;
            width: 66%;
        }

        .password-strength.strong {
            background: var(--color-green);
            width: 100%;
        }

        @media (max-width: 768px) {
            .config-grid {
                grid-template-columns: 1fr;
            }

            .config-container {
                padding: 15px;
            }

            .page-header h1 {
                font-size: 1.5em;
            }
        }
    </style>
</head>
<body>

<div class="config-container">
    <a href="../index.php" class="btn-back">
        <i class="fas fa-arrow-left"></i> Volver al Dashboard
    </a>

    <div class="page-header">
        <h1><i class="fas fa-cog"></i> Configuración de Usuario</h1>
        <p>Gestiona tu información personal y seguridad de la cuenta</p>
    </div>

    <?php if ($mensaje): ?>
        <div class="alert <?= $tipo_mensaje ?>">
            <i class="fas fa-<?= $tipo_mensaje === 'success' ? 'check-circle' : 'exclamation-circle' ?>"></i>
            <?= htmlspecialchars($mensaje) ?>
        </div>
    <?php endif; ?>

    <div class="config-grid">
        <!-- Información Personal -->
        <div class="config-card">
            <h3><i class="fas fa-user"></i> Información Personal</h3>
            
            <div class="user-info-display">
                <p><strong>Usuario:</strong> <?= htmlspecialchars($usuario_data['usuario']) ?></p>
                <p><strong>Rol:</strong> <?= htmlspecialchars($rol) ?></p>
                <p><strong>Empresa:</strong> <?= htmlspecialchars($empresa) ?></p>
            </div>

            <form method="POST">
                <div class="form-group">
                    <label><i class="fas fa-id-card"></i> Nombre Completo *</label>
                    <input type="text" name="nombre" value="<?= htmlspecialchars($usuario_data['nombre']) ?>" required>
                </div>

                <div class="form-group">
                    <label><i class="fas fa-envelope"></i> Email</label>
                    <input type="email" name="email" value="<?= htmlspecialchars($usuario_data['email']) ?>" placeholder="correo@ejemplo.com">
                </div>

                <button type="submit" name="actualizar_info" class="btn-submit">
                    <i class="fas fa-save"></i> Guardar Cambios
                </button>
            </form>
        </div>

        <!-- Cambiar Contraseña -->
        <div class="config-card">
            <h3><i class="fas fa-lock"></i> Seguridad</h3>
            
            <div class="info-box">
                <i class="fas fa-shield-alt"></i>
                <p><strong>Importante:</strong> Tu contraseña debe tener al menos 4 caracteres.</p>
            </div>

            <form method="POST" id="passwordForm">
                <div class="form-group">
                    <label><i class="fas fa-key"></i> Contraseña Actual *</label>
                    <input type="password" name="password_actual" id="passwordActual" required>
                </div>

                <div class="form-group">
                    <label><i class="fas fa-lock"></i> Nueva Contraseña *</label>
                    <input type="password" name="password_nueva" id="passwordNueva" required>
                    <div class="password-strength" id="passwordStrength"></div>
                </div>

                <div class="form-group">
                    <label><i class="fas fa-check-circle"></i> Confirmar Nueva Contraseña *</label>
                    <input type="password" name="password_confirmar" id="passwordConfirmar" required>
                </div>

                <button type="submit" name="cambiar_password" class="btn-submit">
                    <i class="fas fa-shield-alt"></i> Cambiar Contraseña
                </button>
            </form>
        </div>

        <!-- Información del Sistema -->
        <div class="config-card">
            <h3><i class="fas fa-info-circle"></i> Información del Sistema</h3>
            
            <div class="user-info-display">
                <p><strong>Versión:</strong> InModa v1.0</p>
                <p><strong>ID de Sesión:</strong> #<?= $user_id ?></p>
                <p><strong>Última Conexión:</strong> <?= date('d/m/Y H:i') ?></p>
            </div>

            <div class="info-box">
                <i class="fas fa-question-circle"></i>
                <p><strong>¿Necesitas ayuda?</strong></p>
                <p>Contacta al administrador del sistema para soporte técnico.</p>
            </div>
        </div>

        <!-- Preferencias -->
        <div class="config-card">
            <h3><i class="fas fa-sliders-h"></i> Preferencias</h3>
            
            <div class="info-box">
                <i class="fas fa-paint-brush"></i>
                <p><strong>Próximamente:</strong></p>
                <p>• Tema claro/oscuro</p>
                <p>• Idioma del sistema</p>
                <p>• Notificaciones por email</p>
            </div>

            <div class="user-info-display">
                <p><strong>Tema:</strong> Claro (Por defecto)</p>
                <p><strong>Idioma:</strong> Español</p>
                <p><strong>Notificaciones:</strong> Activadas</p>
            </div>
        </div>
    </div>
</div>

<script>
// Validación de contraseña en tiempo real
document.getElementById('passwordNueva')?.addEventListener('input', function() {
    const password = this.value;
    const strengthBar = document.getElementById('passwordStrength');
    
    if (password.length < 4) {
        strengthBar.className = 'password-strength weak';
    } else if (password.length < 8) {
        strengthBar.className = 'password-strength medium';
    } else {
        strengthBar.className = 'password-strength strong';
    }
});

// Validar que las contraseñas coincidan
document.getElementById('passwordForm')?.addEventListener('submit', function(e) {
    const nueva = document.getElementById('passwordNueva').value;
    const confirmar = document.getElementById('passwordConfirmar').value;
    
    if (nueva !== confirmar) {
        e.preventDefault();
        alert('❌ Las contraseñas no coinciden. Por favor verifica.');
        document.getElementById('passwordConfirmar').focus();
    }
});

// Animación de entrada
document.addEventListener('DOMContentLoaded', function() {
    const cards = document.querySelectorAll('.config-card');
    cards.forEach((card, index) => {
        card.style.opacity = '0';
        card.style.transform = 'translateY(20px)';
        setTimeout(() => {
            card.style.transition = 'all 0.5s ease';
            card.style.opacity = '1';
            card.style.transform = 'translateY(0)';
        }, index * 100);
    });
});
</script>

</body>
</html>