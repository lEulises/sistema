<?php
// includes/header.php
// ¡IMPORTANTE!: Iniciar la sesión al principio de cada archivo que use sesiones.
if (session_status() == PHP_SESSION_NONE) { // Solo inicia la sesión si no está ya iniciada
    session_start();
}
require_once 'config.php'; // Asegúrate de que config.php esté incluido y funcione correctamente

// Obtener información del usuario si ha iniciado sesión
$is_logged_in = isset($_SESSION['user_id']);
$logged_in_nombre = $is_logged_in ? htmlspecialchars($_SESSION['nombre'] ?? '') : '';
$logged_in_apellido = $is_logged_in ? htmlspecialchars($_SESSION['apellido'] ?? '') : '';
$logged_in_username_display = $is_logged_in ? htmlspecialchars($_SESSION['username'] ?? '') : ''; 
$user_rol = $is_logged_in ? htmlspecialchars($_SESSION['rol'] ?? '') : ''; // Usando 'rol' como en tu archivo

// Puedes cambiar el título dinámicamente si es necesario en cada página que lo incluya.
$page_title = isset($page_title) ? $page_title : 'Colegio | U.E Colegio María Auxiliadora';
$body_class = isset($body_class) ? $body_class : '';

// --- LÓGICA PARA MOSTRAR LA ALERTA EN EL FRONTEND ---
$current_alert = null;
// Establece la zona horaria a la de tu ubicación
date_default_timezone_set('America/Caracas'); 
$current_datetime = date('Y-m-d H:i:s');

// Consulta para obtener la alerta activa y dentro del rango de fechas
$stmt_alert = $conn->prepare("SELECT id, contenido, tipo FROM site_alerts WHERE activa = TRUE AND (fecha_inicio <= ? OR fecha_inicio IS NULL) AND (fecha_fin >= ? OR fecha_fin IS NULL) ORDER BY fecha_creacion DESC LIMIT 1");
if ($stmt_alert) {
    $stmt_alert->bind_param("ss", $current_datetime, $current_datetime);
    $stmt_alert->execute();
    $result_alert = $stmt_alert->get_result();
    if ($result_alert->num_rows > 0) {
        $current_alert = $result_alert->fetch_assoc();
    }
    $stmt_alert->close();
}
// La conexión $conn NO se cierra aquí, ya que se asume que las páginas que incluyen el header la usarán después.
// La conexión debe cerrarse al final del script principal de cada página.

?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $page_title; ?></title>
    <link rel="icon" href="assets/img/favicon.png" type="image/x-icon">

    <link rel="stylesheet" href="css/styles.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&family=Playfair+Display:700&display=swap" rel="stylesheet">
</head>
<body class="<?php echo $body_class; ?>">

    <header class="main-header">
        <div class="container header-content">
            <a href="index.php" class="site-logo">
                <img src="assets/img/logo.png" alt="Logo Colegio María Auxiliadora">
                <div class="site-title-container">
                    <h1 class="site-title">Colegio María Auxiliadora</h1>
                    <p class="site-subtitle">"Fe, Ciencia y Alegría"</p>
                </div>
            </a>

            <nav class="main-nav">
                <ul class="nav-list">
                    <li class="active"><a href="index.php">Inicio</a></li>
                    <li class="has-submenu">
                        <a href="#">Colegio<i class="fas fa-chevron-down arrow-down"></i></a>
                        <ul class="submenu">
                            <li><a href="mision_vision.php">Misión y Visión</a></li>
                            <li><a href="ubicacion.php">Ubícanos</a></li>
                            <li><a href="#">Instalaciones</a></li>
                        </ul>
                    </li>
                    <li class="has-submenu">
                        <a href="#">Manual de Convivencia <i class="fas fa-chevron-down arrow-down"></i></a>
                        <ul class="submenu">
                            <li><a href="assets/docs/1  DISPOSICIONES FUNDAMENTALES.pdf" target="_blank">I. Disposiciones Fundamentales</a></li>
                            <li><a href="assets/docs/2  RESPONSABILIDADES Y DEBERES DE LOS ALUMNOS.pdf" target="_blank">II. Responsabilidades y deberes de los alumnos</a></li>
                            <li><a href="assets/docs/3  DERECHOS Y GARANTÍAS DE LOS ESTUDIANTES.pdf" target="_blank">III. DERECHOS Y GARANTÍAS DE LOS ESTUDIANTES</a></li>
                            <li><a href="assets/docs/4  RESPONSABILIDADES Y DEBERES DE LOS REPRESENTANTES.pdf" target="_blank">IV. DEBERES Y DERECHOS PP.RR</a></li>
                            <li><a href="assets/docs/5  DERECHOS Y GARANTÍAS DE LOS REPRESENTANTES.pdf" target="_blank">V. DERECHOS Y GARANTÍAS DE LOS REPRESENTANTES</a></li>
                            <li><a href="assets/docs/6  COMPROMISO DE INSCRIPCION REPRESENTANTES.pdf" target="_blank">VI. COMPROMISO</a></li>
                        </ul>
                    </li>
                    <li><a href="noticias.php">Noticias</a></li> <?php if ($is_logged_in && $user_rol === 'representante'): ?>
                        <li class="has-submenu">
                            <a href="#" onclick="return false;">Admisión <i class="fas fa-caret-down arrow-down"></i></a>
                            <ul class="submenu">
                                <li><a href="solicitud_cupo.php">Crear Nueva Solicitud</a></li>
                                <li><a href="mis_solicitudes.php">Ver Mis Solicitudes</a></li>
                            </ul>
                        </li>
                    <?php else: ?>
                        <li><a href="solicitud_cupo.php">Solicitud de Cupo</a></li>
                    <?php endif; ?>

                    <li><a href="contacto.php">Contacto</a></li>
                </ul>
            </nav>
            
            <div class="header-auth">
                <?php if ($is_logged_in): // Si el usuario ha iniciado sesión ?>
                    <div class="user-info-display">
                        <span class="user-full-name">¡Hola, <?php echo $logged_in_username_display; ?>!</span>
                    </div>
                    <a href="logout.php" class="btn btn-logout">Cerrar Sesión</a>
                <?php else: // Si no ha iniciado sesión ?>
                    <a href="login.php" class="btn btn-primary">Acceder</a>
                    <a href="register.php" class="btn btn-secondary">Registrarse</a>
                <?php endif; ?>
            </div>

            <button class="hamburger-menu" aria-label="Abrir menú">
                <span class="icon-bar"></span>
                <span class="icon-bar"></span>
                <span class="icon-bar"></span>
            </button>
        </div>
    </header>

    <?php if ($is_logged_in && $user_rol === 'admin'): ?>
        <?php endif; ?>

    <?php if ($current_alert): ?>
    <div id="alertModal" class="alert-modal-overlay">
        <div class="alert-modal-content alert-modal-<?php echo htmlspecialchars($current_alert['tipo']); ?>">
            <span class="close-alert-button">&times;</span>
            <div class="alert-modal-body">
                <?php echo $current_alert['contenido']; // El contenido ya es HTML de TinyMCE ?>
            </div>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const alertModal = document.getElementById('alertModal');
            const closeButton = document.querySelector('.close-alert-button');
            const alertId = <?php echo json_encode($current_alert['id']); ?>;
            const localStorageKey = 'alertSeen_' + alertId;
            const oneHour = 60 * 60 * 1000; // 1 hora en milisegundos

            // Función para mostrar la alerta
            function showAlert() {
                alertModal.style.display = 'flex';
                // Añadir clase para deshabilitar scroll en el body si es necesario
                document.body.classList.add('no-scroll'); 
            }

            // Función para ocultar la alerta y registrar que fue vista
            function hideAlert() {
                alertModal.style.display = 'none';
                localStorage.setItem(localStorageKey, Date.now()); // Guarda el timestamp actual
                document.body.classList.remove('no-scroll');
            }

            // Comprobar si la alerta ya fue vista en la última hora
            const lastSeen = localStorage.getItem(localStorageKey);
            // Mostrar la alerta si:
            // 1. Nunca ha sido vista, O
            // 2. Ha pasado más de una hora desde la última vez que fue vista
            if (!lastSeen || (Date.now() - lastSeen > oneHour)) {
                showAlert();
            }

            // Event listener para el botón de cerrar
            closeButton.addEventListener('click', hideAlert);

            // También puedes cerrar haciendo clic fuera de la alerta (opcional)
            alertModal.addEventListener('click', function(event) {
                if (event.target === alertModal) {
                    hideAlert();
                }
            });
        });
    </script>
    <?php endif; ?>