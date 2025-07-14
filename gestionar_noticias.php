<?php
session_start();
require_once 'config.php';

// **IMPORTANTE:** Verificar la autenticación y el rol de administrador
if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true || $_SESSION['user_role'] !== 'admin') {
    header("Location: login.php");
    exit;
}

$message = '';
$message_type = '';

// Manejar mensajes de sesión (ej. después de crear/editar/eliminar)
// Este bloque fue movido para que los mensajes de sesión se muestren correctamente
if (isset($_SESSION['message'])) {
    $message = $_SESSION['message'];
    $message_type = $_SESSION['message_type'];
    unset($_SESSION['message']);
    unset($_SESSION['message_type']);
}


// Manejar acciones de eliminar o cambiar estado
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action'])) {
    $noticia_id = $_POST['noticia_id'] ?? null;

    if ($noticia_id) {
        if ($_POST['action'] == 'delete') {
            $stmt = $conn->prepare("DELETE FROM noticias WHERE id = ?");
            if ($stmt && $stmt->bind_param("i", $noticia_id) && $stmt->execute()) {
                $_SESSION['message'] = "Noticia eliminada con éxito."; // Usar sesión para el mensaje
                $_SESSION['message_type'] = 'success';
            } else {
                $_SESSION['message'] = "Error al eliminar la noticia: " . ($stmt ? $stmt->error : $conn->error);
                $_SESSION['message_type'] = 'error';
            }
            if ($stmt) $stmt->close();
        } elseif ($_POST['action'] == 'toggle_status') {
            $current_status = filter_var($_POST['current_status'], FILTER_VALIDATE_BOOLEAN);
            $new_status = !$current_status;
            $stmt = $conn->prepare("UPDATE noticias SET activo = ? WHERE id = ?");
            if ($stmt && $stmt->bind_param("ii", $new_status, $noticia_id) && $stmt->execute()) {
                $_SESSION['message'] = "Estado de la noticia actualizado con éxito."; // Usar sesión para el mensaje
                $_SESSION['message_type'] = 'success';
            } else {
                $_SESSION['message'] = "Error al actualizar el estado: " . ($stmt ? $stmt->error : $conn->error);
                $_SESSION['message_type'] = 'error';
            }
            if ($stmt) $stmt->close();
        }
    } else {
        $_SESSION['message'] = "ID de noticia inválido para la acción.";
        $_SESSION['message_type'] = 'error';
    }
    // Redirigir para evitar reenvío de formulario y mostrar mensaje en la recarga
    header("Location: gestionar_noticias.php");
    exit;
}

$noticias = [];
// Obtener todas las noticias para el panel de administración
$sql = "SELECT n.id, n.titulo, n.fecha_publicacion, n.activo, u.username AS autor_username 
        FROM noticias n 
        LEFT JOIN usuarios u ON n.autor_id = u.id 
        ORDER BY n.fecha_publicacion DESC";
$result = $conn->query($sql);

if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $noticias[] = $row;
    }
}


// Configurar el título de la página y la clase del body
$page_title = 'Gestionar Noticias - Colegio María Auxiliadora';
$body_class = 'admin-page';
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
        <h2>Gestionar Noticias</h2>

        <?php if ($message): ?>
            <div class="message <?php echo $message_type; ?>">
                <?php echo $message; ?>
            </div>
        <?php endif; ?>

        <p><a href="crear_noticia.php" class="btn btn-primary">Crear Nueva Noticia</a></p>

        <?php if (empty($noticias)): ?>
            <p class="no-records">No hay noticias registradas.</p>
        <?php else: ?>
            <div style="overflow-x: auto;">
                <table class="solicitudes-table"> <thead>
                        <tr>
                            <th>ID</th>
                            <th>Título</th>
                            <th>Autor</th>
                            <th>Fecha de Publicación</th>
                            <th>Activa</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($noticias as $noticia): ?>
                        <tr>
                            <td data-label="ID"><?php echo htmlspecialchars($noticia['id']); ?></td>
                            <td data-label="Título"><?php echo htmlspecialchars($noticia['titulo']); ?></td>
                            <td data-label="Autor"><?php echo htmlspecialchars($noticia['autor_username'] ?? 'N/A'); ?></td>
                            <td data-label="Fecha Publicación"><?php echo date('d/m/Y H:i', strtotime($noticia['fecha_publicacion'])); ?></td>
                            <td data-label="Activa">
                                <span class="status-badge <?php echo $noticia['activo'] ? 'aprobada' : 'rechazada'; ?>">
                                    <?php echo $noticia['activo'] ? 'Sí' : 'No'; ?>
                                </span>
                            </td>
                            <td data-label="Acciones">
                                <div class="action-buttons-group">
                                    <a href="editar_noticia.php?id=<?php echo htmlspecialchars($noticia['id']); ?>" class="btn btn-sm btn-secondary">Editar</a>
                                    
                                    <form action="gestionar_noticias.php" method="POST" style="display:inline-block; margin-left: 5px;">
                                        <input type="hidden" name="noticia_id" value="<?php echo htmlspecialchars($noticia['id']); ?>">
                                        <input type="hidden" name="action" value="toggle_status">
                                        <input type="hidden" name="current_status" value="<?php echo htmlspecialchars($noticia['activo'] ? 'true' : 'false'); ?>">
                                        <button type="submit" class="btn btn-sm <?php echo $noticia['activo'] ? 'btn-danger' : 'btn-success'; ?>">
                                            <?php echo $noticia['activo'] ? 'Desactivar' : 'Activar'; ?>
                                        </button>
                                    </form>

                                    <form action="gestionar_noticias.php" method="POST" onsubmit="return confirm('¿Estás seguro de que quieres eliminar esta noticia?');" style="display:inline-block; margin-left: 5px;">
                                        <input type="hidden" name="noticia_id" value="<?php echo htmlspecialchars($noticia['id']); ?>">
                                        <input type="hidden" name="action" value="delete">
                                        <button type="submit" class="btn btn-sm btn-danger"><i class="fas fa-trash-alt"></i> Eliminar</button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>

<?php include 'includes/footer.php'; ?>

</body>
</html>
<?php $conn->close(); // Mueve el cierre de conexión al final del archivo principal ?>