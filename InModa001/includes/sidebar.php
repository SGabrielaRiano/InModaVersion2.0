<?php
if (session_status() === PHP_SESSION_NONE) session_start();
$base = rtrim(dirname($_SERVER['SCRIPT_NAME']), '/\\');
if ($base === '') $base = '.';
$nombre = isset($_SESSION['nombre']) ? $_SESSION['nombre'] : 'Invitado';
$rol = isset($_SESSION['rol']) ? $_SESSION['rol'] : 'Vendedor';
$empresa = isset($_SESSION['empresa']) ? $_SESSION['empresa'] : 'InModa';
?>

<style>
/* Asegurar que body tenga scroll */
body {
    overflow-y: auto !important;
    overflow-x: hidden !important;
}

/* Sidebar Styles */
.sidebar {
    position: fixed;
    left: 0;
    top: 0;
    height: 100vh;
    width: 260px;
    background: linear-gradient(180deg, var(--color-secondary-grey, #d3d3d3) 0%, #c0c0c0 100%);
    box-shadow: 3px 0 15px rgba(0, 0, 0, 0.15);
    transition: all 0.3s ease;
    z-index: 1000;
    display: flex;
    flex-direction: column;
    overflow-y: auto;
}

.sidebar.collapsed {
    width: 80px;
}

.sidebar-header {
    text-align: center;
    position: relative;
    padding: 20px 15px;
    border-bottom: 2px solid rgba(0, 0, 0, 0.1);
}

.sidebar-header img {
    width: 90px;
    transition: all 0.3s ease;
    filter: drop-shadow(0 2px 5px rgba(0, 0, 0, 0.2));
}

.sidebar.collapsed .sidebar-header img {
    width: 45px;
}

.sidebar-header h2 {
    margin: 10px 0 0 0;
    font-size: 1.5em;
    transition: all 0.3s ease;
}

.sidebar.collapsed .sidebar-header h2 {
    display: none;
}

.toggle-btn {
    position: absolute;
    top: 20px;
    right: -15px;
    background: linear-gradient(135deg, var(--color-primary-blue, #007bff), #0056b3);
    color: white;
    border-radius: 50%;
    width: 32px;
    height: 32px;
    cursor: pointer;
    display: flex;
    justify-content: center;
    align-items: center;
    box-shadow: 0 3px 10px rgba(0, 0, 0, 0.3);
    transition: all 0.3s ease;
    border: 2px solid white;
}

.toggle-btn:hover {
    transform: scale(1.1);
}

.sidebar-nav {
    flex: 1;
    padding: 15px;
    overflow-y: auto;
}

.sidebar-nav::-webkit-scrollbar {
    width: 6px;
}

.sidebar-nav::-webkit-scrollbar-track {
    background: transparent;
}

.sidebar-nav::-webkit-scrollbar-thumb {
    background: rgba(0, 0, 0, 0.2);
    border-radius: 3px;
}

.sidebar-nav ul {
    list-style: none;
    padding: 0;
    margin: 0;
}

.sidebar-nav li {
    margin-bottom: 8px;
}

.sidebar-nav a {
    display: flex;
    align-items: center;
    text-decoration: none;
    color: var(--color-text-black, #000);
    background-color: white;
    padding: 12px 15px;
    border-radius: 12px;
    box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1);
    transition: all 0.3s ease;
    font-weight: 500;
}

.sidebar-nav a:hover,
.sidebar-nav a.active {
    background: linear-gradient(135deg, var(--color-primary-blue, #007bff), #0056b3);
    color: white;
    transform: translateX(5px);
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
    background-color: rgba(0, 0, 0, 0.05);
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
    background-color: rgba(255, 255, 255, 0.9);
    padding: 10px 20px 10px 45px;
    margin: 4px 8px;
    font-size: 0.95em;
}

.sidebar.collapsed .submenu {
    display: none !important;
}

.sidebar-footer-icons {
    text-align: center;
    padding: 15px 0;
    border-top: 2px solid rgba(0, 0, 0, 0.1);
}

.sidebar-footer-icons .icon {
    font-size: 24px;
    margin: 0 12px;
    cursor: pointer;
    opacity: 0.7;
    transition: all 0.3s ease;
    color: var(--color-text-black, #000);
}

.sidebar-footer-icons .icon:hover {
    opacity: 1;
    transform: scale(1.2);
    color: var(--color-primary-blue, #007bff);
}

/* User Modal */
.modal-overlay {
    display: none;
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background-color: rgba(0, 0, 0, 0.5);
    z-index: 1999;
}

.modal-overlay.active {
    display: block;
}

.user-modal {
    display: none;
    position: fixed;
    top: 50%;
    left: 50%;
    transform: translate(-50%, -50%);
    background: white;
    padding: 30px;
    border-radius: 20px;
    box-shadow: 0 10px 40px rgba(0, 0, 0, 0.3);
    text-align: center;
    z-index: 2000;
    max-width: 400px;
}

.user-modal.active {
    display: block;
}

.user-modal img {
    width: 100px;
    height: 100px;
    border-radius: 50%;
    border: 4px solid var(--color-primary-blue, #007bff);
    margin-bottom: 15px;
}

.user-modal h3 {
    margin: 10px 0 5px;
    color: var(--color-primary-blue, #007bff);
}

.user-modal p {
    color: var(--color-light-grey-text, #666);
    margin: 8px 0;
}

.status-indicator {
    display: inline-block;
    width: 12px;
    height: 12px;
    background-color: var(--color-green, #28a745);
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
    background: var(--color-primary-blue, #007bff);
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

@media (max-width: 768px) {
    .sidebar {
        width: 80px;
    }
    
    .sidebar .sidebar-header h2,
    .sidebar .sidebar-nav a span {
        display: none;
    }
}
</style>

<aside class="sidebar" id="sidebar">
    <div class="sidebar-header">
        <div class="toggle-btn" id="toggleSidebar">
            <i class="fas fa-angle-left"></i>
        </div>
        <img src="<?= $base ?>/assets/images/inmoda-logo.png" alt="Logo" onerror="this.style.display='none'">
        <h2>InModa</h2>
    </div>

    <nav class="sidebar-nav">
        <ul>
            <li><a href="<?= $base ?>/index.php"><i class="fas fa-home"></i> <span>Inicio</span></a></li>

            <li class="has-submenu sidebar-toggle">
                <a href="#"><i class="fas fa-tshirt"></i> <span>Productos</span> <i class="fas fa-chevron-down dropdown-arrow"></i></a>
                <ul class="submenu">
                    <li><a href="<?= $base ?>/modules/productos.php">Ver Productos</a></li>
                    <?php if ($rol === 'Administrador' || $rol === 'Vendedor'): ?>
                    <li><a href="<?= $base ?>/modules/agregar_producto.php">Agregar</a></li>
                    <?php endif; ?>
                </ul>
            </li>

            <li><a href="<?= $base ?>/modules/inventario.php"><i class="fas fa-boxes"></i> <span>Inventario</span></a></li>

            <li><a href="<?= $base ?>/modules/ventas.php"><i class="fas fa-cash-register"></i> <span>Ventas</span></a></li>

            <?php if ($rol === 'Administrador'): ?>
            <li class="has-submenu sidebar-toggle">
                <a href="#"><i class="fas fa-truck"></i> <span>Proveedores</span> <i class="fas fa-chevron-down dropdown-arrow"></i></a>
                <ul class="submenu">
                    <li><a href="<?= $base ?>/modules/proveedores.php">Ver Proveedores</a></li>
                    <li><a href="<?= $base ?>/modules/agregar_proveedores.php">Agregar</a></li>
                </ul>
            </li>

            <li><a href="<?= $base ?>/modules/reportes.php"><i class="fas fa-chart-line"></i> <span>Reportes</span></a></li>

            <li><a href="<?= $base ?>/modules/usuarios.php"><i class="fas fa-users"></i> <span>Usuarios</span></a></li>
            <?php endif; ?>

            <li><a href="<?= $base ?>/modules/notas.php"><i class="fas fa-sticky-note"></i> <span>Notas</span></a></li>

            <li><a href="<?= $base ?>/logout.php" style="border-left: 4px solid var(--color-red, #dc3545);"><i class="fas fa-sign-out-alt"></i> <span>Salir</span></a></li>
        </ul>
    </nav>

    <div class="sidebar-footer-icons">
        <i class="fas fa-cog icon" id="settingsIcon" title="Configuración"></i>
        <i class="fas fa-bell icon" id="notificationsIcon" title="Notificaciones"></i>
        <i class="fas fa-user-circle icon" id="userIcon" title="Perfil"></i>
    </div>
</aside>

<!-- Modal Overlay -->
<div class="modal-overlay" id="modalOverlay"></div>

<!-- User Modal -->
<div class="user-modal" id="userModal">
    <img src="<?= $base ?>/assets/images/profile-default.png" alt="Usuario" onerror="this.src='data:image/svg+xml,%3Csvg xmlns=%22http://www.w3.org/2000/svg%22 viewBox=%220 0 100 100%22%3E%3Ccircle cx=%2250%22 cy=%2250%22 r=%2250%22 fill=%22%23007bff%22/%3E%3Ctext x=%2250%22 y=%2265%22 font-size=%2250%22 text-anchor=%22middle%22 fill=%22white%22%3E👤%3C/text%3E%3C/svg%3E'">
    <h3><?= htmlspecialchars($nombre) ?></h3>
    <p><span class="status-indicator"></span> En línea</p>
    <p><strong>Rol:</strong> <?= htmlspecialchars($rol) ?></p>
    <p><strong>Empresa:</strong> <?= htmlspecialchars($empresa) ?></p>
    <button class="close-modal-btn" onclick="cerrarModalUsuario()">Cerrar</button>
</div>

<script>
document.addEventListener('DOMContentLoaded', () => {
    // Toggle Sidebar
    const toggleBtn = document.getElementById('toggleSidebar');
    const sidebar = document.getElementById('sidebar');
    
    if (toggleBtn) {
        toggleBtn.addEventListener('click', () => {
            sidebar.classList.toggle('collapsed');
            const icon = toggleBtn.querySelector('i');
            icon.classList.toggle('fa-angle-left');
            icon.classList.toggle('fa-angle-right');
        });
    }

    // Submenús desplegables
    document.querySelectorAll('.sidebar-toggle > a').forEach(link => {
        link.addEventListener('click', (e) => {
            e.preventDefault();
            const li = link.parentElement;
            const submenu = li.querySelector('.submenu');
            li.classList.toggle('active');
            submenu.classList.toggle('active');
        });
    });

    // Icono de configuración - ENLACE FUNCIONAL
    const settingsIcon = document.getElementById('settingsIcon');
    if (settingsIcon) {
        settingsIcon.addEventListener('click', () => {
            window.location.href = "<?= $base ?>/modules/configuracion.php";
        });
    }

    // Icono de notificaciones
    const notificationsIcon = document.getElementById('notificationsIcon');
    if (notificationsIcon) {
        notificationsIcon.addEventListener('click', () => {
            alert('🔔 Notificaciones\n\n• Sistema funcionando correctamente\n• Última sincronización: ' + new Date().toLocaleTimeString());
        });
    }

    // Panel de usuario
    const userIcon = document.getElementById('userIcon');
    const userModal = document.getElementById('userModal');
    const modalOverlay = document.getElementById('modalOverlay');
    
    if (userIcon) {
        userIcon.addEventListener('click', () => {
            userModal.classList.add('active');
            modalOverlay.classList.add('active');
        });
    }

    if (modalOverlay) {
        modalOverlay.addEventListener('click', cerrarModalUsuario);
    }
});

function cerrarModalUsuario() {
    const userModal = document.getElementById('userModal');
    const modalOverlay = document.getElementById('modalOverlay');
    if (userModal) userModal.classList.remove('active');
    if (modalOverlay) modalOverlay.classList.remove('active');
}
</script>