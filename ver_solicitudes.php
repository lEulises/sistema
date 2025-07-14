<?php
session_start();
require_once 'config.php'; // Asegúrate de que tu archivo config.php esté bien configurado

// **IMPORTANTE:** Verificar la autenticación y el rol de administrador
if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true || $_SESSION['user_role'] !== 'admin') {
    header("Location: login.php"); // Redirigir a una página de login si no es admin
    exit;
}

$message = '';
$message_type = ''; // Para mostrar mensajes de éxito/error

// Manejar mensajes de sesión (ej. después de una actualización de estado)
if (isset($_SESSION['message'])) {
    $message = $_SESSION['message'];
    $message_type = $_SESSION['message_type'];
    unset($_SESSION['message']); // Limpiar el mensaje después de mostrarlo
    unset($_SESSION['message_type']);
}

$solicitudes = [];
$search_term = $_GET['search'] ?? '';
$filter_estado = $_GET['estado'] ?? '';
$filter_grado = $_GET['grado'] ?? '';
$order_by = $_GET['order_by'] ?? 'fecha_solicitud';
$order_dir = $_GET['order_dir'] ?? 'DESC';

// Validar parámetros de ordenación para evitar inyección SQL
$allowed_order_by = [
    'id', 'madre_nombre_completo', 'madre_cedula', 'hijo1_nombre_completo',
    'hijo1_fecha_nacimiento', 'hijo1_grado_cursar', 'fecha_solicitud', 'estado_solicitud'
];
$allowed_order_dir = ['ASC', 'DESC'];

$order_by = in_array($order_by, $allowed_order_by) ? $order_by : 'fecha_solicitud';
$order_dir = in_array(strtoupper($order_dir), $allowed_order_dir) ? strtoupper($order_dir) : 'DESC';

$where_clauses = [];
$params = [];
$param_types = '';

if (!empty($search_term)) {
    $search_term_like = '%' . $search_term . '%';
    $where_clauses[] = "(madre_nombre_completo LIKE ? OR madre_cedula LIKE ? OR madre_email LIKE ? OR
                         padre_nombre_completo LIKE ? OR padre_cedula LIKE ? OR
                         hijo1_nombre_completo LIKE ? OR hijo1_cedula LIKE ?)";
    $params[] = $search_term_like; $params[] = $search_term_like; $params[] = $search_term_like;
    $params[] = $search_term_like; $params[] = $search_term_like; $params[] = $search_term_like;
    $params[] = $search_term_like;
    $param_types .= 'sssssss';
}

if (!empty($filter_estado) && in_array($filter_estado, ['pendiente', 'en_revision', 'aprobada', 'rechazada', 'inscrito'])) {
    $where_clauses[] = "estado_solicitud = ?";
    $params[] = $filter_estado;
    $param_types .= 's';
}

if (!empty($filter_grado)) {
    $where_clauses[] = "hijo1_grado_cursar = ?";
    $params[] = $filter_grado;
    $param_types .= 's';
}


$sql = "SELECT * FROM solicitudes_cupo";
if (count($where_clauses) > 0) {
    $sql .= " WHERE " . implode(' AND ', $where_clauses);
}
$sql .= " ORDER BY " . $order_by . " " . $order_dir;

$stmt = $conn->prepare($sql);

if ($stmt === false) {
    echo "Error al preparar la consulta: " . $conn->error;
} else {
    if (!empty($params)) {
        $stmt->bind_param($param_types, ...$params);
    }
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $solicitudes[] = $row;
        }
    }
    $stmt->close();
}

// Opciones para "Grado/Año a cursar" (debe ser el mismo listado que en el formulario)
$grados_disponibles = [
    '3er Nivel Preescolar', '1er Grado de Primaria', '2do Grado de Primaria', '3er Grado de Primaria',
    '4to Grado de Primaria', '5to Grado de Primaria', '6to Grado de Primaria', '1er Año de Bachillerato',
    '2do Año de Bachillerato', '3er Año de Bachillerato', '4to Año de Bachillerato', '5to Año de Bachillerato'
];

// Opciones de estado para el selector
$estados_solicitud_disponibles = [
    'pendiente', 'en_revision', 'aprobada', 'rechazada', 'inscrito'
];

// Configurar el título de la página y la clase del body
$page_title = 'Administrar Solicitudes de Cupo - Colegio María Auxiliadora';
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
<body>

<?php include 'includes/header.php'; ?>

    <div class="admin-container">
        <h2>Gestión de Solicitudes de Cupo</h2>

        <?php if ($message): ?>
            <div class="message <?php echo htmlspecialchars($message_type); ?>">
                <?php echo htmlspecialchars($message); ?>
            </div>
        <?php endif; ?>

        <form action="ver_solicitudes.php" method="GET" class="filter-sort-form">
            <div class="form-group">
                <label for="search">Buscar:</label>
                <input type="text" id="search" name="search" placeholder="Nombre, Cédula, Email..." value="<?php echo htmlspecialchars($search_term); ?>">
            </div>
            <div class="form-group">
                <label for="estado">Filtrar por Estado:</label>
                <select id="estado" name="estado">
                    <option value="">Todos</option>
                    <?php foreach ($estados_solicitud_disponibles as $estado_opcion): ?>
                        <option value="<?php echo htmlspecialchars($estado_opcion); ?>" <?php echo ($filter_estado == $estado_opcion ? 'selected' : ''); ?>>
                            <?php echo htmlspecialchars(ucfirst(str_replace('_', ' ', $estado_opcion))); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-group">
                <label for="grado">Filtrar por Grado:</label>
                <select id="grado" name="grado">
                    <option value="">Todos</option>
                    <?php foreach ($grados_disponibles as $grado): ?>
                        <option value="<?php echo htmlspecialchars($grado); ?>" <?php echo ($filter_grado == $grado ? 'selected' : ''); ?>>
                            <?php echo htmlspecialchars($grado); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-group">
                <label for="order_by">Ordenar por:</label>
                <select id="order_by" name="order_by">
                    <option value="fecha_solicitud" <?php echo ($order_by == 'fecha_solicitud' ? 'selected' : ''); ?>>Fecha de Solicitud</option>
                    <option value="hijo1_nombre_completo" <?php echo ($order_by == 'hijo1_nombre_completo' ? 'selected' : ''); ?>>Nombre Alumno</option>
                    <option value="estado_solicitud" <?php echo ($order_by == 'estado_solicitud' ? 'selected' : ''); ?>>Estado</option>
                    <option value="hijo1_grado_cursar" <?php echo ($order_by == 'hijo1_grado_cursar' ? 'selected' : ''); ?>>Grado Solicitado</option>
                </select>
            </div>
            <div class="form-group">
                <label for="order_dir">Dirección:</label>
                <select id="order_dir" name="order_dir">
                    <option value="DESC" <?php echo ($order_dir == 'DESC' ? 'selected' : ''); ?>>Descendente</option>
                    <option value="ASC" <?php echo ($order_dir == 'ASC' ? 'selected' : ''); ?>>Ascendente</option>
                </select>
            </div>
            <button type="submit">Aplicar Filtros</button>
        </form>

        <?php if (empty($solicitudes)): ?>
            <p class="no-records">No hay solicitudes de cupo que coincidan con los criterios de búsqueda/filtro.</p>
        <?php else: ?>
            <div style="overflow-x: auto;">
                <table class="solicitudes-table">
                    <thead>
                        <tr>
                            <th onclick="window.location.href='ver_solicitudes.php?<?php echo http_build_query(array_merge($_GET, ['order_by' => 'id', 'order_dir' => ($order_by == 'id' && $order_dir == 'ASC' ? 'DESC' : 'ASC')])); ?>'">ID <i class="fas fa-sort<?php echo ($order_by == 'id' ? ($order_dir == 'ASC' ? '-up' : '-down') : ''); ?>"></i></th>
                            <th onclick="window.location.href='ver_solicitudes.php?<?php echo http_build_query(array_merge($_GET, ['order_by' => 'fecha_solicitud', 'order_dir' => ($order_by == 'fecha_solicitud' && $order_dir == 'ASC' ? 'DESC' : 'ASC')])); ?>'">Fecha Solicitud <i class="fas fa-sort<?php echo ($order_by == 'fecha_solicitud' ? ($order_dir == 'ASC' ? '-up' : '-down') : ''); ?>"></i></th>
                            <th onclick="window.location.href='ver_solicitudes.php?<?php echo http_build_query(array_merge($_GET, ['order_by' => 'hijo1_nombre_completo', 'order_dir' => ($order_by == 'hijo1_nombre_completo' && $order_dir == 'ASC' ? 'DESC' : 'ASC')])); ?>'">Alumno <i class="fas fa-sort<?php echo ($order_by == 'hijo1_nombre_completo' ? ($order_dir == 'ASC' ? '-up' : '-down') : ''); ?>"></i></th>
                            <th onclick="window.location.href='ver_solicitudes.php?<?php echo http_build_query(array_merge($_GET, ['order_by' => 'hijo1_grado_cursar', 'order_dir' => ($order_by == 'hijo1_grado_cursar' && $order_dir == 'ASC' ? 'DESC' : 'ASC')])); ?>'">Grado Solicitado <i class="fas fa-sort<?php echo ($order_by == 'hijo1_grado_cursar' ? ($order_dir == 'ASC' ? '-up' : '-down') : ''); ?>"></i></th>
                            <th>Madre (Cédula / Email)</th>
                            <th>Padre (Cédula)</th>
                            <th>Colegio Actual</th>
                            <th>Cómo se Enteró</th>
                            <th>Miembro Inst.</th>
                            <th onclick="window.location.href='ver_solicitudes.php?<?php echo http_build_query(array_merge($_GET, ['order_by' => 'estado_solicitud', 'order_dir' => ($order_by == 'estado_solicitud' && $order_dir == 'ASC' ? 'DESC' : 'ASC')])); ?>'">Estado <i class="fas fa-sort<?php echo ($order_by == 'estado_solicitud' ? ($order_dir == 'ASC' ? '-up' : '-down') : ''); ?>"></i></th>
                            <th>Acciones</th> </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($solicitudes as $solicitud): ?>
                        <tr>
                            <td data-label="ID"><?php echo htmlspecialchars($solicitud['id']); ?></td>
                            <td data-label="Fecha Solicitud"><?php echo date('d/m/Y H:i', strtotime($solicitud['fecha_solicitud'])); ?></td>
                            <td data-label="Alumno"><?php echo htmlspecialchars($solicitud['hijo1_nombre_completo']); ?><br><small>(Nac: <?php echo htmlspecialchars($solicitud['hijo1_fecha_nacimiento']); ?>)</small></td>
                            <td data-label="Grado Solicitado"><?php echo htmlspecialchars($solicitud['hijo1_grado_cursar']); ?></td>
                            <td data-label="Madre">
                                <?php echo htmlspecialchars($solicitud['madre_nombre_completo']); ?><br>
                                <small>(CI: <?php echo htmlspecialchars($solicitud['madre_cedula']); ?>)</small><br>
                                <small>(Email: <?php echo htmlspecialchars($solicitud['madre_email']); ?>)</small><br>
                                <small>(Tel: <?php echo htmlspecialchars($solicitud['madre_telefono_movil']); ?>)</small>
                            </td>
                            <td data-label="Padre">
                                <?php echo !empty($solicitud['padre_nombre_completo']) ? htmlspecialchars($solicitud['padre_nombre_completo']) : 'N/A'; ?><br>
                                <?php echo !empty($solicitud['padre_cedula']) ? '<small>(CI: ' . htmlspecialchars($solicitud['padre_cedula']) . ')</small>' : ''; ?>
                            </td>
                            <td data-label="Colegio Actual"><?php echo !empty($solicitud['hijo1_colegio_actual']) ? htmlspecialchars($solicitud['hijo1_colegio_actual']) : 'N/A'; ?></td>
                            <td data-label="Cómo se Enteró">
                                <?php echo htmlspecialchars($solicitud['como_se_entero']); ?>
                                <?php echo !empty($solicitud['como_se_entero_otro']) ? '<br><small>(' . htmlspecialchars($solicitud['como_se_entero_otro']) . ')</small>' : ''; ?>
                            </td>
                            <td data-label="Miembro Inst.">
                                <?php echo htmlspecialchars($solicitud['conoce_miembro_institucion']); ?>
                                <?php echo !empty($solicitud['miembro_institucion_info']) ? '<br><small>(' . htmlspecialchars($solicitud['miembro_institucion_info']) . ')</small>' : ''; ?>
                            </td>
                            <td data-label="Estado">
                                <span class="status-badge <?php echo htmlspecialchars($solicitud['estado_solicitud']); ?>">
                                    <?php echo htmlspecialchars(ucfirst(str_replace('_', ' ', $solicitud['estado_solicitud']))); ?>
                                </span>
                            </td>
                            <td data-label="Acciones">
                                <form action="update_solicitud_status.php" method="POST" class="status-update-form">
                                    <input type="hidden" name="solicitud_id" value="<?php echo htmlspecialchars($solicitud['id']); ?>">
                                    <select name="new_status" onchange="this.form.submit()">
                                        <?php foreach ($estados_solicitud_disponibles as $estado_opcion): ?>
                                            <option value="<?php echo htmlspecialchars($estado_opcion); ?>" <?php echo ($solicitud['estado_solicitud'] == $estado_opcion ? 'selected' : ''); ?>>
                                                <?php echo htmlspecialchars(ucfirst(str_replace('_', ' ', $estado_opcion))); ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </form>
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