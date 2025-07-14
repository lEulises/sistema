<?php
session_start();
require_once 'config.php';

// Verificar que el usuario esté logueado
if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    header("Location: login.php"); // Redirigir a la página de login si no está logueado
    exit;
}

// Obtener el ID del usuario logueado
$current_user_id = $_SESSION['user_id'];
$solicitudes = [];

// Consulta para obtener solo las solicitudes de este usuario
// Asegúrate de que la columna 'user_id' exista en tu tabla 'solicitudes_cupo'
$sql = "SELECT id, hijo1_nombre_completo, hijo1_grado_cursar, fecha_solicitud, estado_solicitud FROM solicitudes_cupo WHERE user_id = ? ORDER BY fecha_solicitud DESC"; 

$stmt = $conn->prepare($sql);

if ($stmt === false) {
    echo "Error al preparar la consulta: " . $conn->error;
} else {
    $stmt->bind_param("i", $current_user_id);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $solicitudes[] = $row;
        }
    }
    $stmt->close();
}

// Configurar el título de la página y la clase del body ANTES de incluir el header
$page_title = 'Mis Solicitudes de Cupo - Colegio María Auxiliadora';
$body_class = 'user-dashboard-page'; // Puedes definir un estilo específico para esta página
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
<body>

<?php include 'includes/header.php'; ?>

    <div class="admin-container"> <h2>Mis Solicitudes de Cupo</h2>

        <?php if (empty($solicitudes)): ?>
            <p class="no-records">No has enviado ninguna solicitud de cupo aún.</p>
            <p>Puedes enviar una nueva solicitud haciendo clic <a href="solicitud_cupo.php">aquí</a>.</p>
        <?php else: ?>
            <div style="overflow-x: auto;">
                <table class="solicitudes-table"> <thead>
                        <tr>
                            <th>ID Solicitud</th>
                            <th>Nombre Hijo/a</th>
                            <th>Grado Solicitado</th>
                            <th>Fecha de Solicitud</th>
                            <th>Estado Actual</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($solicitudes as $solicitud): ?>
                        <tr>
                            <td data-label="ID Solicitud"><?php echo htmlspecialchars($solicitud['id']); ?></td>
                            <td data-label="Nombre Hijo/a"><?php echo htmlspecialchars($solicitud['hijo1_nombre_completo']); ?></td>
                            <td data-label="Grado Solicitado"><?php echo htmlspecialchars($solicitud['hijo1_grado_cursar']); ?></td>
                            <td data-label="Fecha de Solicitud"><?php echo date('d/m/Y H:i', strtotime($solicitud['fecha_solicitud'])); ?></td>
                            <td data-label="Estado Actual">
                                <span class="status-badge <?php echo htmlspecialchars($solicitud['estado_solicitud']); ?>">
                                    <?php echo htmlspecialchars(ucfirst(str_replace('_', ' ', $solicitud['estado_solicitud']))); ?>
                                </span>
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