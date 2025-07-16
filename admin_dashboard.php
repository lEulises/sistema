<?php
session_start();
require_once 'config.php';

// **IMPORTANTE:** Verificar la autenticación y el rol de administrador
if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true || $_SESSION['user_role'] !== 'admin') {
    header("Location: login.php"); // Redirigir a la página de login si no es admin
    exit;
}

$page_title = 'Panel de Administración - Colegio María Auxiliadora';
$body_class = 'admin-dashboard-page'; // Clase CSS específica para el dashboard
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $page_title; ?></title>
    <link rel="stylesheet" href="css/styles.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&family=Playfair+Display:700&display=swap" rel="stylesheet">
</head>
<body class="<?php echo $body_class; ?>">

<?php include 'includes/header.php'; ?>

    <div class="admin-container">
        <h2>Bienvenido al Panel de Administración, <?php echo htmlspecialchars($_SESSION['username']); ?></h2>
        <p style="text-align: center; margin-bottom: 40px; color: var(--dark-gray);">Desde aquí puedes gestionar el contenido y las solicitudes del sitio web del colegio.</p>

      <div class="dashboard-grid">
            <a href="ver_solicitudes.php" class="dashboard-card">
                <i class="fas fa-file-alt"></i>
                <h3>Gestionar Solicitudes de Cupo</h3>
                <p>Revisa, filtra y actualiza el estado de las solicitudes de admisión.</p>
            </a>
            <a href="crear_noticia.php" class="dashboard-card">
                <i class="fas fa-newspaper"></i>
                <h3>Crear Nueva Noticia</h3>
                <p>Publica anuncios importantes y novedades para la comunidad.</p>
            </a>
            <a href="gestionar_noticias.php" class="dashboard-card">
                <i class="fas fa-edit"></i>
                <h3>Gestionar Noticias Existentes</h3>
                <p>Edita, activa/desactiva o elimina noticias ya publicadas.</p>
            </a>
            <a href="register.php" class="dashboard-card"> <i class="fas fa-user-plus"></i>
                <h3>Registrar Nuevo Usuario</h3>
                <p>Crea cuentas de usuario para representantes o personal.</p>
            </a>
            <a href="manage_payments.php" class="dashboard-card">
                <i class="fas fa-dollar-sign"></i>
                <h3>Gestionar Pagos</h3>
                <p>Verifica, aprueba o rechaza los comprobantes de pago de los representantes.</p>
            </a>
        </div>
    </div>

<?php include 'includes/footer.php'; ?>

</body>
</html>