<?php
session_start();

// Verificar si ya hay sesión activa
if (isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit();
}

// Intentar conectar a la base de datos
$db_error = false;
try {
    require_once "config/conexion.php";
} catch (Exception $e) {
    $db_error = true;
}

$error = "";
$signup_message = "";

// Manejar envío de formulario de login
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'login') {
    if ($db_error) {
        $error = "Error de conexión con la base de datos.";
    } else {
        $usuario = trim($_POST['usuario']);
        $clave = $_POST['clave'];

        try {
            // Consulta insensible a mayúsculas - incluir empresa
            $stmt = $mysqli->prepare("SELECT id, usuario, clave, rol, empresa FROM usuarios WHERE LOWER(usuario) = LOWER(?)");
            $stmt->bind_param("s", $usuario);
            $stmt->execute();
            $res = $stmt->get_result();
            $row = $res->fetch_assoc();

            if ($row) {
                // Verificación de clave
                if ($clave === $row['clave']) {
                    $_SESSION['user_id'] = $row['id'];
                    $_SESSION['usuario'] = $row['usuario'];
                    $_SESSION['nombre'] = $row['usuario'];
                    $_SESSION['rol'] = $row['rol'];
                    $_SESSION['empresa'] = $row['empresa'];
                    
                    header("Location: index.php");
                    exit();
                } else {
                    $error = "Usuario o contraseña incorrectos.";
                }
            } else {
                $error = "Usuario o contraseña incorrectos.";
            }
            $stmt->close();
        } catch (Exception $e) {
            $error = "Error al procesar el inicio de sesión.";
        }
    }
}

// Manejar envío de formulario de registro
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'signup') {
    $nombre_empresa = trim($_POST['nombre_empresa']);
    $email = trim($_POST['email']);
    $telefono = trim($_POST['telefono']);
    
    // Enviar correo
    $to = "sarita07ria@gmail.com";
    $subject = "Nueva Solicitud de Registro - InModa";
    $message = "Nueva solicitud de registro:\n\n";
    $message .= "Empresa: " . $nombre_empresa . "\n";
    $message .= "Email: " . $email . "\n";
    $message .= "Teléfono: " . $telefono . "\n";
    $message .= "\nFecha: " . date('Y-m-d H:i:s');
    
    $headers = "From: " . $email . "\r\n";
    $headers .= "Reply-To: " . $email . "\r\n";
    
    if(mail($to, $subject, $message, $headers)) {
        $signup_message = "¡Gracias! Tu solicitud ha sido enviada. Nos pondremos en contacto contigo pronto.";
    } else {
        $signup_message = "Error al enviar la solicitud. Por favor, intenta más tarde.";
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>InModa - Iniciar Sesión</title>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
<style>
* {
    margin: 0;
    padding: 0;
    box-sizing: border-box;
}

:root {
    --color-primary-blue: #007bff;
    --color-background-main: #f5f5dc;
    --color-secondary-grey: #d3d3d3;
    --color-text-black: #000000;
    --color-white: #FFFFFF;
}

body {
    font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
    background: linear-gradient(135deg, var(--color-primary-blue) 0%, #0056b3 100%);
    min-height: 100vh;
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 20px;
}

.container {
    display: flex;
    max-width: 1200px;
    width: 100%;
    background: var(--color-white);
    border-radius: 20px;
    box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
    overflow: hidden;
    animation: slideIn 0.6s ease;
}

@keyframes slideIn {
    from { opacity: 0; transform: translateY(-30px); }
    to { opacity: 1; transform: translateY(0); }
}

.left-section {
    flex: 1;
    padding: 50px;
    display: flex;
    flex-direction: column;
    justify-content: center;
}

.right-section {
    flex: 1;
    background: linear-gradient(135deg, var(--color-background-main) 0%, #e8e8ce 100%);
    padding: 50px;
    display: flex;
    flex-direction: column;
    justify-content: space-between;
    position: relative;
}

.logo {
    width: 120px;
    margin: 0 auto 20px auto;
    display: block;
}

.nav-links {
    position: absolute;
    top: 30px;
    right: 30px;
    display: flex;
    gap: 30px;
}

.nav-links a {
    color: var(--color-text-black);
    text-decoration: none;
    font-weight: 600;
    transition: all 0.3s ease;
    padding: 8px 16px;
    border-radius: 8px;
}

.nav-links a:hover {
    background: rgba(0, 123, 255, 0.1);
    transform: translateY(-2px);
}

.nav-links .signup-btn {
    background: var(--color-primary-blue);
    color: var(--color-white);
    padding: 10px 25px;
    border-radius: 25px;
    cursor: pointer;
}

.nav-links .signup-btn:hover {
    background: #0056b3;
    transform: translateY(-2px);
}

.about-content {
    display: flex;
    flex-direction: column;
    justify-content: center;
    align-items: center;
    text-align: center;
    height: 100%;
}

.about-content h2 {
    font-size: 2.8em;
    color: var(--color-text-black);
    margin-bottom: 30px;
    font-weight: 700;
}

.about-content p {
    color: #333;
    line-height: 1.8;
    font-size: 1.1em;
    max-width: 500px;
}

.about-features {
    margin-top: 40px;
    display: flex;
    flex-direction: column;
    gap: 20px;
}

.feature-item {
    display: flex;
    align-items: center;
    gap: 15px;
    background: var(--color-white);
    padding: 20px;
    border-radius: 15px;
    box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
    transition: all 0.3s ease;
}

.feature-item:hover {
    transform: translateY(-5px);
    box-shadow: 0 10px 25px rgba(0, 123, 255, 0.2);
}

.feature-icon {
    font-size: 2em;
    color: var(--color-primary-blue);
    min-width: 50px;
}

.welcome-section {
    text-align: center;
    margin-bottom: 40px;
}

.welcome-icon {
    font-size: 60px;
    animation: wave 1.5s ease infinite;
    display: inline-block;
    margin-bottom: 10px;
}

@keyframes wave {
    0%, 100% { transform: rotate(0deg); }
    25% { transform: rotate(20deg); }
    75% { transform: rotate(-20deg); }
}

.welcome-section h1 {
    font-size: 2.2em;
    color: var(--color-text-black);
    margin-bottom: 10px;
}

.welcome-section p {
    color: #666;
    font-size: 1.1em;
}

.alert {
    padding: 15px;
    border-radius: 10px;
    margin-bottom: 20px;
    animation: slideDown 0.5s ease;
}

@keyframes slideDown {
    from { opacity: 0; transform: translateY(-10px); }
    to { opacity: 1; transform: translateY(0); }
}

.alert-error {
    background: #ffe3e3;
    color: #c00;
    border-left: 4px solid #c00;
}

.alert-success {
    background: #d4edda;
    color: #155724;
    border-left: 4px solid #28a745;
}

.form-group {
    margin-bottom: 25px;
    position: relative;
}

.form-group label {
    display: block;
    margin-bottom: 8px;
    color: var(--color-text-black);
    font-weight: 600;
    font-size: 0.95em;
}

.input-wrapper {
    position: relative;
}

.form-group input {
    width: 100%;
    padding: 14px 45px 14px 15px;
    border: 2px solid var(--color-secondary-grey);
    border-radius: 10px;
    font-size: 1em;
    transition: all 0.3s ease;
}

.form-group input:focus {
    outline: none;
    border-color: var(--color-primary-blue);
    box-shadow: 0 0 0 3px rgba(0, 123, 255, 0.1);
    transform: translateY(-2px);
}

.input-icon {
    position: absolute;
    right: 15px;
    top: 50%;
    transform: translateY(-50%);
    color: #666;
    cursor: pointer;
    transition: all 0.3s ease;
    font-size: 1.1em;
}

.input-icon:hover {
    color: var(--color-primary-blue);
    transform: translateY(-50%) scale(1.1);
}

.example-hint {
    font-size: 0.85em;
    color: #666;
    margin-top: 5px;
    display: flex;
    align-items: center;
    gap: 5px;
}

.btn-login {
    width: 100%;
    padding: 16px;
    background: linear-gradient(135deg, var(--color-primary-blue) 0%, #0056b3 100%);
    color: var(--color-white);
    border: none;
    border-radius: 10px;
    font-size: 1.1em;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.3s ease;
}

.btn-login:hover {
    transform: translateY(-3px);
    box-shadow: 0 10px 25px rgba(0, 123, 255, 0.4);
}

.btn-login:active {
    transform: translateY(-1px);
}

.forgot-password {
    text-align: center;
    margin-top: 20px;
}

.forgot-password a {
    color: var(--color-primary-blue);
    text-decoration: none;
    font-weight: 600;
    transition: all 0.3s ease;
    cursor: pointer;
}

.forgot-password a:hover {
    text-decoration: underline;
    transform: scale(1.05);
    display: inline-block;
}

/* Modal Styles */
.modal {
    display: none;
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: rgba(0, 0, 0, 0.7);
    z-index: 1000;
    justify-content: center;
    align-items: center;
    animation: fadeIn 0.3s ease;
}

@keyframes fadeIn {
    from { opacity: 0; }
    to { opacity: 1; }
}

.modal.active {
    display: flex;
}

.modal-content {
    background: var(--color-white);
    padding: 40px;
    border-radius: 20px;
    max-width: 500px;
    width: 90%;
    position: relative;
    animation: slideUp 0.4s ease;
    max-height: 90vh;
    overflow-y: auto;
}

@keyframes slideUp {
    from { opacity: 0; transform: translateY(50px); }
    to { opacity: 1; transform: translateY(0); }
}

.close-modal {
    position: absolute;
    top: 20px;
    right: 20px;
    font-size: 28px;
    color: #666;
    cursor: pointer;
    transition: all 0.3s ease;
    width: 35px;
    height: 35px;
    display: flex;
    align-items: center;
    justify-content: center;
    border-radius: 50%;
}

.close-modal:hover {
    background: #f0f0f0;
    color: var(--color-text-black);
    transform: rotate(90deg);
}

.modal-content h2 {
    margin-bottom: 20px;
    color: var(--color-text-black);
}

.modal-content p {
    color: #666;
    line-height: 1.6;
    margin-bottom: 20px;
}

.support-info {
    background: var(--color-background-main);
    padding: 20px;
    border-radius: 10px;
    margin-top: 20px;
}

.support-info strong {
    color: var(--color-primary-blue);
}

/* Responsive */
@media (max-width: 968px) {
    .container {
        flex-direction: column;
    }
    
    .right-section {
        order: -1;
    }
    
    .nav-links {
        position: static;
        margin-top: 20px;
        justify-content: center;
    }
    
    .about-content h2 {
        font-size: 2em;
    }
}

@media (max-width: 480px) {
    .left-section, .right-section {
        padding: 30px 20px;
    }
    
    .welcome-section h1 {
        font-size: 1.8em;
    }
    
    .about-content h2 {
        font-size: 1.6em;
    }
}

/* Signup Modal Specific Styles */
.signup-form .form-group {
    margin-bottom: 20px;
}

.signup-form textarea {
    width: 100%;
    padding: 14px;
    border: 2px solid var(--color-secondary-grey);
    border-radius: 10px;
    font-size: 1em;
    font-family: inherit;
    resize: vertical;
    min-height: 100px;
    transition: all 0.3s ease;
}

.signup-form textarea:focus {
    outline: none;
    border-color: var(--color-primary-blue);
    box-shadow: 0 0 0 3px rgba(0, 123, 255, 0.1);
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
</style>
</head>
<body>

<div class="container">
    <!-- Left Section - Login Form -->
    <div class="left-section">
        <img src="assets/images/inmoda-logo.png" alt="InModa Logo" class="logo" onerror="this.style.display='none'">
        
        <div class="welcome-section">
            <div class="welcome-icon">👋</div>
            <h1>¡Bienvenido a InModa!</h1>
            <p>Ingresa tus credenciales para continuar</p>
        </div>

        <?php if($error): ?>
            <div class="alert alert-error">
                <i class="fas fa-exclamation-circle"></i> <?= htmlspecialchars($error) ?>
            </div>
        <?php endif; ?>

        <form method="post" id="loginForm">
            <input type="hidden" name="action" value="login">
            
            <div class="form-group">
                <label for="usuario">Usuario</label>
                <div class="input-wrapper">
                    <input id="usuario" name="usuario" type="text" placeholder="Ingresa tu usuario" required autofocus>
                    <i class="fas fa-user input-icon"></i>
                </div>
            </div>
            
            <div class="form-group">
                <label for="clave">Contraseña</label>
                <div class="input-wrapper">
                    <input id="clave" name="clave" type="password" placeholder="Ingresa tu contraseña" required>
                    <i class="fas fa-eye input-icon" id="togglePassword" onclick="togglePassword()"></i>
                </div>
            </div>
            
            <button type="submit" class="btn-login">
                <i class="fas fa-sign-in-alt"></i> Iniciar Sesión
            </button>
        </form>

        <div class="forgot-password">
            <a onclick="openForgotModal()">
                <i class="fas fa-question-circle"></i> ¿Olvidaste tu contraseña?
            </a>
        </div>
    </div>

    <!-- Right Section - About -->
    <div class="right-section">
        <div class="nav-links">
            <a href="#about">About us</a>
            <a class="signup-btn" onclick="openSignupModal()">Sign up</a>
        </div>

        <div class="about-content">
            <h2>🎨 Sobre InModa</h2>
            <p>
                InModa es la plataforma integral diseñada específicamente para empresas del sector moda. 
                Digitalizamos y modernizamos tu negocio textil con herramientas profesionales y accesibles.
            </p>
            
            <div class="about-features">
                <div class="feature-item">
                    <div class="feature-icon">📦</div>
                    <div>
                        <strong>Gestión de Inventarios</strong>
                        <p style="margin: 5px 0 0 0; font-size: 0.9em; color: #666;">Control total de tus productos</p>
                    </div>
                </div>
                
                <div class="feature-item">
                    <div class="feature-icon">💰</div>
                    <div>
                        <strong>Control de Ventas</strong>
                        <p style="margin: 5px 0 0 0; font-size: 0.9em; color: #666;">Seguimiento en tiempo real</p>
                    </div>
                </div>
                
                <div class="feature-item">
                    <div class="feature-icon">👥</div>
                    <div>
                        <strong>Clientes & Proveedores</strong>
                        <p style="margin: 5px 0 0 0; font-size: 0.9em; color: #666;">Todo en un solo lugar</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Forgot Password Modal -->
<div id="forgotModal" class="modal">
    <div class="modal-content">
        <span class="close-modal" onclick="closeForgotModal()">&times;</span>
        <h2><i class="fas fa-lock"></i> ¿Olvidaste tu contraseña?</h2>
        <p>
            Si has olvidado tu contraseña, usuario o cualquier información necesaria para iniciar sesión, 
            no te preocupes. Nuestro equipo está aquí para ayudarte.
        </p>
        <div class="support-info">
            <p><i class="fas fa-envelope"></i> <strong>Correo de soporte:</strong></p>
            <p>sarita07ria@gmail.com</p>
            <p style="margin-top: 15px;">
                <i class="fas fa-info-circle"></i> 
                Contáctanos y te responderemos lo antes posible para restablecer tu acceso.
            </p>
        </div>
    </div>
</div>

<!-- Signup Modal -->
<div id="signupModal" class="modal">
    <div class="modal-content">
        <span class="close-modal" onclick="closeSignupModal()">&times;</span>
        <h2><i class="fas fa-user-plus"></i> Solicitar Registro</h2>
        
        <?php if($signup_message): ?>
            <div class="alert alert-success">
                <i class="fas fa-check-circle"></i> <?= htmlspecialchars($signup_message) ?>
            </div>
        <?php endif; ?>
        
        <div class="info-box">
            <i class="fas fa-shield-alt"></i>
            <strong>Registro Seguro:</strong> Por temas de seguridad, todos los usuarios son creados 
            por nuestro equipo y serán asociados a tu empresa de moda.
        </div>

        <form method="post" class="signup-form">
            <input type="hidden" name="action" value="signup">
            
            <div class="form-group">
                <label for="nombre_empresa">Nombre de la Empresa</label>
                <input id="nombre_empresa" name="nombre_empresa" type="text" 
                       placeholder="Ej: Confecciones La Moda S.A.S" required>
            </div>
            
            <div class="form-group">
                <label for="email">Correo Electrónico</label>
                <div class="input-wrapper">
                    <input id="email" name="email" type="email" 
                           placeholder="tucorreo@empresa.com" required>
                    <i class="fas fa-envelope input-icon"></i>
                </div>
                <div class="example-hint">
                    <i class="fas fa-info-circle"></i>
                    Te contactaremos a este correo
                </div>
            </div>
            
            <div class="form-group">
                <label for="telefono">Teléfono de Contacto</label>
                <div class="input-wrapper">
                    <input id="telefono" name="telefono" type="tel" 
                           placeholder="+57 300 123 4567" required>
                    <i class="fas fa-phone input-icon"></i>
                </div>
            </div>
            
            <button type="submit" class="btn-login">
                <i class="fas fa-paper-plane"></i> Enviar Solicitud
            </button>
        </form>
    </div>
</div>

<script>
function togglePassword() {
    const input = document.getElementById('clave');
    const icon = document.getElementById('togglePassword');
    
    if (input.type === 'password') {
        input.type = 'text';
        icon.classList.remove('fa-eye');
        icon.classList.add('fa-eye-slash');
    } else {
        input.type = 'password';
        icon.classList.remove('fa-eye-slash');
        icon.classList.add('fa-eye');
    }
}

function openForgotModal() {
    document.getElementById('forgotModal').classList.add('active');
}

function closeForgotModal() {
    document.getElementById('forgotModal').classList.remove('active');
}

function openSignupModal() {
    document.getElementById('signupModal').classList.add('active');
}

function closeSignupModal() {
    document.getElementById('signupModal').classList.remove('active');
}

// Cerrar modal al hacer clic fuera
window.onclick = function(event) {
    const forgotModal = document.getElementById('forgotModal');
    const signupModal = document.getElementById('signupModal');
    
    if (event.target === forgotModal) {
        closeForgotModal();
    }
    if (event.target === signupModal) {
        closeSignupModal();
    }
}

// Cerrar modales con tecla Escape
document.addEventListener('keydown', function(event) {
    if (event.key === 'Escape') {
        closeForgotModal();
        closeSignupModal();
    }
});

// Animación de entrada para inputs
document.querySelectorAll('input').forEach(input => {
    input.addEventListener('focus', function() {
        this.parentElement.style.transform = 'scale(1.02)';
    });
    
    input.addEventListener('blur', function() {
        this.parentElement.style.transform = 'scale(1)';
    });
});
</script>

</body>
</html>
