<?php
session_start();
require_once 'config.php';

// Verificar si el usuario está logueado y tiene un rol permitido (representante o administrador)
if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true || ($_SESSION['user_role'] !== 'representante' && $_SESSION['user_role'] !== 'admin')) {
    header("Location: login.php");
    exit;
}

$user_id_logged_in = $_SESSION['user_id'];
$user_role = $_SESSION['user_role'];

// Determinar qué tipo de recursos mostrar
$show_primaria_resources = false;
$show_bachillerato_resources = false;

// Obtener los grados de los estudiantes asociados al usuario logueado
$students_grades = [];
$sql_get_grades = "SELECT DISTINCT grado FROM students WHERE user_id = ?";
$stmt_get_grades = $conn->prepare($sql_get_grades);
if ($stmt_get_grades) {
    $stmt_get_grades->bind_param("i", $user_id_logged_in);
    $stmt_get_grades->execute();
    $result_grades = $stmt_get_grades->get_result();
    while ($row = $result_grades->fetch_assoc()) {
        $students_grades[] = $row['grado'];
    }
    $stmt_get_grades->close();
}

// Clasificar los grados para determinar qué recursos mostrar
foreach ($students_grades as $grado) {
    // Patrones flexibles para Primaria (ej. "1er Grado", "6to Grado")
    if (preg_match('/^(\d)(?:er|do|ro|to|to)? Grado$/i', $grado, $matches)) {
        $grade_number = (int)$matches[1];
        if ($grade_number >= 1 && $grade_number <= 6) {
            $show_primaria_resources = true;
        }
    }
    // Patrones flexibles para Bachillerato (ej. "1er Año", "5to Año")
    if (preg_match('/^(\d)(?:er|do|ro|to|to)? Año$/i', $grado, $matches)) {
        $year_number = (int)$matches[1];
        if ($year_number >= 1 && $year_number <= 5) {
            $show_bachillerato_resources = true;
        }
    }
    
    // Si eres admin, puedes ver todos los recursos sin importar los estudiantes asociados
    if ($user_role === 'admin') {
        $show_primaria_resources = true;
        $show_bachillerato_resources = true;
        break; // No es necesario seguir revisando grados si es admin
    }
}

// Enlaces a Google Drive (USA ESTOS COMO PLANTILLA Y REEMPLAZA CON TUS ENLACES REALES)
$google_drive_links = [
    'primaria' => [
        'utiles_textos' => 'https://drive.google.com/drive/folders/ID_CARPETA_UTILES_PRIMARIA', // Reemplazar con URL real
        'tareas' => 'https://drive.google.com/drive/folders/ID_CARPETA_TAREAS_PRIMARIA', // Reemplazar con URL real
        'cronograma' => 'https://drive.google.com/drive/folders/ID_CARPETA_CRONOGRAMA_PRIMARIA', // Reemplazar con URL real
    ],
    'bachillerato' => [
        'utiles' => 'https://drive.google.com/drive/folders/ID_CARPETA_UTILES_BACHILLERATO', // Reemplazar con URL real
        'planificaciones' => 'https://drive.google.com/drive/folders/ID_CARPETA_PLANIFICACIONES_BACHILLERATO', // Reemplazar con URL real
        'calendario_examenes' => 'https://drive.google.com/drive/folders/ID_CARPETA_CALENDARIO_EXAMENES_BACHILLERATO', // Reemplazar con URL real
    ],
];

$page_title = 'Recursos Estudiantiles - Colegio María Auxiliadora';
$body_class = 'representante-page'; // O la clase CSS que uses para paneles de representante
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

    <div class="container section-padding">
        <h2 style="text-align: center; margin-bottom: 40px;">Recursos Estudiantiles</h2>

        <?php if ($show_primaria_resources || $show_bachillerato_resources): ?>
            <div class="resources-display-grid">
                <?php if ($show_primaria_resources): ?>
                    <div class="resources-grid-item">
                        <h3 class="grade-level-title">Recursos para Primaria</h3>
                        <div class="resources-section">
                            <ul class="resources-list">
                                <li><a href="<?php echo htmlspecialchars($google_drive_links['primaria']['utiles_textos']); ?>" target="_blank"><i class="fas fa-book"></i><br>Lista de Útiles y Textos Escolares</a></li>
                                <li><a href="<?php echo htmlspecialchars($google_drive_links['primaria']['tareas']); ?>" target="_blank"><i class="fas fa-tasks"></i><br>Tareas</a></li>
                                <li><a href="<?php echo htmlspecialchars($google_drive_links['primaria']['cronograma']); ?>" target="_blank"><i class="fas fa-calendar-alt"></i><br>Cronograma de Actividades</a></li>
                            </ul>
                        </div>
                    </div>
                <?php endif; ?>

                <?php if ($show_bachillerato_resources): ?>
                    <div class="resources-grid-item">
                        <h3 class="grade-level-title">Recursos para Bachillerato</h3>
                        <div class="resources-section">
                            <ul class="resources-list">
                                <li><a href="<?php echo htmlspecialchars($google_drive_links['bachillerato']['utiles']); ?>" target="_blank"><i class="fas fa-pencil-alt"></i><br>Lista de Útiles Escolares</a></li>
                                <li><a href="<?php echo htmlspecialchars($google_drive_links['bachillerato']['planificaciones']); ?>" target="_blank"><i class="fas fa-clipboard-list"></i><br>Planificaciones</a></li>
                                <li><a href="<?php echo htmlspecialchars($google_drive_links['bachillerato']['calendario_examenes']); ?>" target="_blank"><i class="fas fa-calendar-check"></i><br>Calendario Exámenes de Lapso</a></li>
                            </ul>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        <?php else: ?>
            <p class="no-resources-msg">No hay recursos disponibles para los grados de tus estudiantes. Si eres administrador, contacta al soporte.</p>
        <?php endif; ?>
    </div>

<?php include 'includes/footer.php'; ?>

</body>
</html>
<?php $conn->close(); ?>