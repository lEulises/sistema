<?php
session_start();
require_once 'config.php'; // Asegúrate de que config.php establece $conn

// Verificar si el usuario está logueado y tiene un rol permitido (representante o administrador)
if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true || ($_SESSION['user_role'] !== 'representante' && $_SESSION['user_role'] !== 'admin')) {
    header("Location: login.php");
    exit;
}

// Si es administrador, puede ver todos los estudiantes (o filtrar por usuario si es necesario).
// Si es representante, solo puede ver sus propios estudiantes.
$user_id_filter = null;
if ($_SESSION['user_role'] === 'representante') {
    $user_id_filter = $_SESSION['user_id'];
}
// Si es admin, user_id_filter se mantiene en null, mostrando todos.

$user_id_logged_in = $_SESSION['user_id']; // ID del usuario (representante o admin) logueado
$message = '';
$message_type = '';
$student_to_edit = null; // Para pre-llenar el formulario si se está editando un estudiante

// Manejar mensajes de sesión (ej. después de crear/editar/eliminar)
if (isset($_SESSION['student_message'])) {
    $message = $_SESSION['student_message'];
    $message_type = $_SESSION['student_message_type'];
    unset($_SESSION['student_message']);
    unset($_SESSION['student_message_type']);
}

// --- PROCESAR FORMULARIO DE ADICIÓN/EDICIÓN DE ESTUDIANTE ---
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action'])) {
    $action = $_POST['action'];
    $student_id = $_POST['student_id'] ?? null;
    $nombre = trim($_POST['nombre'] ?? '');
    $apellido = trim($_POST['apellido'] ?? '');
    $grado = trim($_POST['grado'] ?? '');
    $fecha_nacimiento = $_POST['fecha_nacimiento'] ?? null;
    $genero = $_POST['genero'] ?? null;

    // Validación básica
    if (empty($nombre) || empty($apellido) || empty($grado)) {
        $_SESSION['student_message'] = 'Nombre, apellido y grado son obligatorios.';
        $_SESSION['student_message_type'] = 'error';
    } else {
        if ($action == 'add') {
            $stmt = $conn->prepare("INSERT INTO students (user_id, nombre, apellido, grado, fecha_nacimiento, genero) VALUES (?, ?, ?, ?, ?, ?)");
            if ($stmt) {
                // El user_id siempre será el del representante logueado, incluso si es un admin añadiendo.
                // Si un admin añade un estudiante para otro representante, necesitaría un campo de selección de representante.
                // Por ahora, asumo que el admin añade para sí mismo o el representante añade para sí mismo.
                $stmt->bind_param("isssss", $user_id_logged_in, $nombre, $apellido, $grado, $fecha_nacimiento, $genero);
                if ($stmt->execute()) {
                    $_SESSION['student_message'] = "Estudiante añadido con éxito.";
                    $_SESSION['student_message_type'] = "success";
                } else {
                    $_SESSION['student_message'] = "Error al añadir estudiante: " . $stmt->error;
                    $_SESSION['student_message_type'] = "error";
                }
                $stmt->close();
            } else {
                $_SESSION['student_message'] = "Error al preparar la adición del estudiante: " . $conn->error;
                $_SESSION['student_message_type'] = "error";
            }
        } elseif ($action == 'edit' && $student_id) {
            // Un representante solo puede editar sus propios estudiantes. Un admin puede editar cualquiera.
            $sql_update = "UPDATE students SET nombre = ?, apellido = ?, grado = ?, fecha_nacimiento = ?, genero = ? WHERE id = ?";
            $params_update = [$nombre, $apellido, $grado, $fecha_nacimiento, $genero, $student_id];
            $types_update = "sssssi";

            if ($_SESSION['user_role'] === 'representante') {
                $sql_update .= " AND user_id = ?";
                $params_update[] = $user_id_logged_in;
                $types_update .= "i";
            }

            $stmt = $conn->prepare($sql_update);
            if ($stmt) {
                $stmt->bind_param($types_update, ...$params_update);
                if ($stmt->execute()) {
                    $_SESSION['student_message'] = "Datos del estudiante actualizados con éxito.";
                    $_SESSION['student_message_type'] = "success";
                } else {
                    $_SESSION['student_message'] = "Error al actualizar estudiante: " . $stmt->error;
                    $_SESSION['student_message_type'] = "error";
                }
                $stmt->close();
            } else {
                $_SESSION['student_message'] = "Error al preparar la actualización del estudiante: " . $conn->error;
                $_SESSION['student_message_type'] = "error";
            }
        }
    }
    // Redirigir para evitar reenvío de formulario
    header("Location: manage_students.php");
    exit;
}

// --- PROCESAR ACCIONES DE ELIMINAR ESTUDIANTE ---
if (isset($_GET['action']) && $_GET['action'] == 'delete') {
    $student_id = $_GET['id'] ?? null;

    if ($student_id) {
        // Un representante solo puede eliminar sus propios estudiantes. Un admin puede eliminar cualquiera.
        $sql_delete = "DELETE FROM students WHERE id = ?";
        $params_delete = [$student_id];
        $types_delete = "i";

        if ($_SESSION['user_role'] === 'representante') {
            $sql_delete .= " AND user_id = ?";
            $params_delete[] = $user_id_logged_in;
            $types_delete .= "i";
        }

        $stmt = $conn->prepare($sql_delete);
        if ($stmt) {
            $stmt->bind_param($types_delete, ...$params_delete);
            if ($stmt->execute()) {
                $_SESSION['student_message'] = "Estudiante eliminado con éxito.";
                $_SESSION['student_message_type'] = "success";
            } else {
                $_SESSION['student_message'] = "Error al eliminar estudiante: " . $stmt->error;
                $_SESSION['student_message_type'] = "error";
            }
            $stmt->close();
        } else {
            $_SESSION['student_message'] = "Error al preparar la eliminación del estudiante: " . $conn->error;
            $_SESSION['student_message_type'] = "error";
        }
    } else {
        $_SESSION['student_message'] = "ID de estudiante inválido para eliminar.";
        $_SESSION['student_message_type'] = "error";
    }
    header("Location: manage_students.php");
    exit;
}

// --- CARGAR ESTUDIANTE PARA EDICIÓN ---
if (isset($_GET['edit_id'])) {
    $edit_id = $_GET['edit_id'];
    // Un representante solo puede editar sus propios estudiantes. Un admin puede editar cualquiera.
    $sql_select_edit = "SELECT id, nombre, apellido, grado, fecha_nacimiento, genero FROM students WHERE id = ?";
    $params_select_edit = [$edit_id];
    $types_select_edit = "i";

    if ($_SESSION['user_role'] === 'representante') {
        $sql_select_edit .= " AND user_id = ?";
        $params_select_edit[] = $user_id_logged_in;
        $types_select_edit .= "i";
    }

    $stmt = $conn->prepare($sql_select_edit);
    if ($stmt) {
        $stmt->bind_param($types_select_edit, ...$params_select_edit);
        $stmt->execute();
        $result = $stmt->get_result();
        if ($result->num_rows == 1) {
            $student_to_edit = $result->fetch_assoc();
        } else {
            $message = "Estudiante no encontrado o no pertenece a su cuenta.";
            $message_type = 'error';
        }
        $stmt->close();
    }
}

// --- OBTENER TODOS LOS ESTUDIANTES (PARA ADMIN) O LOS DEL REPRESENTANTE (PARA REPRESENTANTE) ---
$students = [];
// Incluimos user_id en el SELECT para que el admin pueda verlo
$sql = "SELECT s.id, s.nombre, s.apellido, s.grado, s.fecha_nacimiento, s.genero, s.user_id, u.username as representative_username FROM students s JOIN usuarios u ON s.user_id = u.id";
$params = [];
$types = "";

// Si es un representante, filtra por su user_id
if ($_SESSION['user_role'] === 'representante') {
    $sql .= " WHERE s.user_id = ?";
    $params[] = $user_id_logged_in;
    $types .= "i";
}

$sql .= " ORDER BY s.apellido, s.nombre";

$stmt = $conn->prepare($sql);

if ($stmt) {
    if (!empty($params)) { // Solo bind_param si hay parámetros
        $stmt->bind_param($types, ...$params);
    }
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $students[] = $row;
    }
    $stmt->close();
}


$page_title = 'Gestionar Hijos/Estudiantes - Colegio María Auxiliadora';
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
        <h2 style="text-align: center; margin-bottom: 20px;"><?php echo $student_to_edit ? 'Editar Datos del Estudiante' : 'Añadir Nuevo Estudiante'; ?></h2>

        <?php if ($message): ?>
            <div class="message <?php echo htmlspecialchars($message_type); ?>">
                <?php echo htmlspecialchars($message); ?>
            </div>
        <?php endif; ?>

        <form action="manage_students.php" method="POST">
            <input type="hidden" name="action" value="<?php echo $student_to_edit ? 'edit' : 'add'; ?>">
            <?php if ($student_to_edit): ?>
                <input type="hidden" name="student_id" value="<?php echo htmlspecialchars($student_to_edit['id']); ?>">
            <?php endif; ?>

            <div class="form-group">
                <label for="nombre">Nombre del Estudiante:</label>
                <input type="text" id="nombre" name="nombre" value="<?php echo htmlspecialchars($student_to_edit['nombre'] ?? ''); ?>" required>
            </div>

            <div class="form-group">
                <label for="apellido">Apellido del Estudiante:</label>
                <input type="text" id="apellido" name="apellido" value="<?php echo htmlspecialchars($student_to_edit['apellido'] ?? ''); ?>" required>
            </div>

            <div class="form-group">
                <label for="grado">Grado/Nivel:</label>
                <select id="grado" name="grado" required>
                    <option value="">Seleccionar Grado</option>
                    <optgroup label="Primaria">
                        <option value="1er Grado" <?php echo ($student_to_edit['grado'] ?? '') == '1er Grado' ? 'selected' : ''; ?>>1er Grado</option>
                        <option value="2do Grado" <?php echo ($student_to_edit['grado'] ?? '') == '2do Grado' ? 'selected' : ''; ?>>2do Grado</option>
                        <option value="3er Grado" <?php echo ($student_to_edit['grado'] ?? '') == '3er Grado' ? 'selected' : ''; ?>>3er Grado</option>
                        <option value="4to Grado" <?php echo ($student_to_edit['grado'] ?? '') == '4to Grado' ? 'selected' : ''; ?>>4to Grado</option>
                        <option value="5to Grado" <?php echo ($student_to_edit['grado'] ?? '') == '5to Grado' ? 'selected' : ''; ?>>5to Grado</option>
                        <option value="6to Grado" <?php echo ($student_to_edit['grado'] ?? '') == '6to Grado' ? 'selected' : ''; ?>>6to Grado</option>
                    </optgroup>
                    <optgroup label="Bachillerato">
                        <option value="1er Año" <?php echo ($student_to_edit['grado'] ?? '') == '1er Año' ? 'selected' : ''; ?>>1er Año</option>
                        <option value="2do Año" <?php echo ($student_to_edit['grado'] ?? '') == '2do Año' ? 'selected' : ''; ?>>2do Año</option>
                        <option value="3er Año" <?php echo ($student_to_edit['grado'] ?? '') == '3er Año' ? 'selected' : ''; ?>>3er Año</option>
                        <option value="4to Año" <?php echo ($student_to_edit['grado'] ?? '') == '4to Año' ? 'selected' : ''; ?>>4to Año</option>
                        <option value="5to Año" <?php echo ($student_to_edit['grado'] ?? '') == '5to Año' ? 'selected' : ''; ?>>5to Año</option>
                    </optgroup>
                    </select>
            </div>

            <div class="form-group">
                <label for="fecha_nacimiento">Fecha de Nacimiento:</label>
                <input type="date" id="fecha_nacimiento" name="fecha_nacimiento" value="<?php echo htmlspecialchars($student_to_edit['fecha_nacimiento'] ?? ''); ?>">
            </div>

            <div class="form-group">
                <label for="genero">Género:</label>
                <select id="genero" name="genero">
                    <option value="">Seleccionar</option>
                    <option value="Masculino" <?php echo ($student_to_edit['genero'] ?? '') == 'Masculino' ? 'selected' : ''; ?>>Masculino</option>
                    <option value="Femenino" <?php echo ($student_to_edit['genero'] ?? '') == 'Femenino' ? 'selected' : ''; ?>>Femenino</option>
                    <option value="Otro" <?php echo ($student_to_edit['genero'] ?? '') == 'Otro' ? 'selected' : ''; ?>>Otro</option>
                </select>
            </div>

            <button type="submit" class="form-submit-btn"><?php echo $student_to_edit ? 'Guardar Cambios' : 'Añadir Estudiante'; ?></button>
            <?php if ($student_to_edit): ?>
                <a href="manage_students.php" class="btn btn-secondary" style="margin-top: 10px; display: inline-block;">Cancelar Edición</a>
            <?php endif; ?>
        </form>

        <h3 style="margin-top: 50px; text-align: center;">Mis Hijos/Estudiantes Registrados</h3>
        <?php if (empty($students)): ?>
            <p class="no-records" style="text-align: center;">Aún no has registrado ningún estudiante.</p>
        <?php else: ?>
            <div style="overflow-x: auto;">
                <table class="solicitudes-table">
                    <thead>
                        <tr>
                            <th>Nombre Completo</th>
                            <th>Grado</th>
                            <th>Fecha Nac.</th>
                            <th>Género</th>
                            <?php if ($_SESSION['user_role'] === 'admin'): ?>
                                <th>Representante</th>
                            <?php endif; ?>
                            <th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($students as $student): ?>
                        <tr>
                            <td data-label="Nombre Completo"><?php echo htmlspecialchars($student['nombre'] . ' ' . $student['apellido']); ?></td>
                            <td data-label="Grado"><?php echo htmlspecialchars($student['grado']); ?></td>
                            <td data-label="Fecha Nac."><?php echo $student['fecha_nacimiento'] ? htmlspecialchars(date('d/m/Y', strtotime($student['fecha_nacimiento']))) : 'N/A'; ?></td>
                            <td data-label="Género"><?php echo htmlspecialchars($student['genero'] ?? 'N/A'); ?></td>
                            <?php if ($_SESSION['user_role'] === 'admin'): ?>
                                <td data-label="Representante"><?php echo htmlspecialchars($student['representative_username'] ?? 'N/A'); ?></td>
                            <?php endif; ?>
                            <td data-label="Acciones">
                                <div class="action-buttons-group">
                                    <a href="manage_students.php?edit_id=<?php echo htmlspecialchars($student['id']); ?>" class="btn btn-sm btn-secondary">Editar</a>
                                    <a href="manage_students.php?action=delete&id=<?php echo htmlspecialchars($student['id']); ?>"
                                       class="btn btn-sm btn-danger" onclick="return confirm('¿Estás seguro de que quieres eliminar a este estudiante? Esto también eliminará sus cargos y pagos asociados.');">
                                        Eliminar
                                    </a>
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
<?php $conn->close(); ?>