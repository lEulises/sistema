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
$alert_to_edit = null; // Para pre-llenar el formulario si se está editando

// Manejar mensajes de sesión (ej. después de crear/editar/eliminar)
if (isset($_SESSION['alert_message'])) {
    $message = $_SESSION['alert_message'];
    $message_type = $_SESSION['alert_message_type'];
    unset($_SESSION['alert_message']);
    unset($_SESSION['alert_message_type']);
}

// Opciones de tipos de alerta
$alert_types = ['info', 'success', 'warning', 'danger'];

// --- PROCESAR FORMULARIO DE CREACIÓN/EDICIÓN ---
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action'])) {
    $action = $_POST['action'];
    $alert_id = $_POST['alert_id'] ?? null;
    $titulo = trim($_POST['titulo'] ?? '');
    // Asegurarse de que el contenido del editor TinyMCE se capture correctamente.
    // El 'name' del textarea es 'contenido', por lo que se accede vía $_POST['contenido'].
    $contenido = $_POST['contenido'] ?? '';
    $alert_type = $_POST['alert_type'] ?? 'info';
    $start_date = $_POST['start_date'] ?? null;
    $end_date = $_POST['end_date'] ?? null;
    $is_active = isset($_POST['is_active']) ? 1 : 0;

    // Validación básica: El contenido y el tipo de alerta son obligatorios y válidos.
    if (empty($contenido) || !in_array($alert_type, $alert_types)) {
        $_SESSION['alert_message'] = 'El contenido y el tipo de alerta son obligatorios y válidos.';
        $_SESSION['alert_message_type'] = 'error';
    } else {
        if ($action == 'create') {
            // Se incluye 'titulo' y 'contenido' en la sentencia INSERT
            $stmt = $conn->prepare("INSERT INTO site_alerts (titulo, contenido, tipo, fecha_inicio, fecha_fin, activa) VALUES (?, ?, ?, ?, ?, ?)");
            if ($stmt) {
                $stmt->bind_param("sssssi", $titulo, $contenido, $alert_type, $start_date, $end_date, $is_active);
                if ($stmt->execute()) {
                    $_SESSION['alert_message'] = "Alerta creada con éxito.";
                    $_SESSION['alert_message_type'] = "success";
                } else {
                    $_SESSION['alert_message'] = "Error al crear la alerta: " . $stmt->error;
                    $_SESSION['alert_message_type'] = "error";
                }
                $stmt->close();
            } else {
                $_SESSION['alert_message'] = "Error al preparar la creación de la alerta: " . $conn->error;
                $_SESSION['alert_message_type'] = "error";
            }
        } elseif ($action == 'edit' && $alert_id) {
            // Se incluye 'titulo' y 'contenido' en la sentencia UPDATE
            $stmt = $conn->prepare("UPDATE site_alerts SET titulo = ?, contenido = ?, tipo = ?, fecha_inicio = ?, fecha_fin = ?, activa = ? WHERE id = ?");
            if ($stmt) {
                $stmt->bind_param("sssssii", $titulo, $contenido, $alert_type, $start_date, $end_date, $is_active, $alert_id);
                if ($stmt->execute()) {
                    $_SESSION['alert_message'] = "Alerta actualizada con éxito.";
                    $_SESSION['alert_message_type'] = "success";
                } else {
                    $_SESSION['alert_message'] = "Error al actualizar la alerta: " . $stmt->error;
                    $_SESSION['alert_message_type'] = "error";
                }
                $stmt->close();
            } else {
                $_SESSION['alert_message'] = "Error al preparar la actualización de la alerta: " . $conn->error;
                $_SESSION['alert_message_type'] = "error";
            }
        }
    }
    // Redirigir para evitar reenvío de formulario
    header("Location: manage_alerts.php");
    exit;
}

// --- PROCESAR ACCIONES DE ELIMINAR/ACTIVAR/DESACTIVAR ---
if (isset($_GET['action'])) {
    $action = $_GET['action'];
    $alert_id = $_GET['id'] ?? null;

    if ($alert_id) {
        if ($action == 'delete') {
            $stmt = $conn->prepare("DELETE FROM site_alerts WHERE id = ?");
            if ($stmt && $stmt->bind_param("i", $alert_id) && $stmt->execute()) {
                $_SESSION['alert_message'] = "Alerta eliminada con éxito.";
                $_SESSION['alert_message_type'] = "success";
            } else {
                $_SESSION['alert_message'] = "Error al eliminar la alerta: " . ($stmt ? $stmt->error : $conn->error);
                $_SESSION['alert_message_type'] = "error";
            }
            if ($stmt) $stmt->close();
        } elseif ($action == 'toggle_active') {
            $current_active_status = filter_var($_GET['status'], FILTER_VALIDATE_BOOLEAN);
            $new_active_status = !$current_active_status;
            // Usar 'activa' para actualizar el estado
            $stmt = $conn->prepare("UPDATE site_alerts SET activa = ? WHERE id = ?");
            if ($stmt && $stmt->bind_param("ii", $new_active_status, $alert_id) && $stmt->execute()) {
                $_SESSION['alert_message'] = "Estado de la alerta actualizado con éxito.";
                $_SESSION['alert_message_type'] = "success";
            } else {
                $_SESSION['alert_message'] = "Error al actualizar el estado: " . ($stmt ? $stmt->error : $conn->error);
                $_SESSION['alert_message_type'] = "error";
            }
            if ($stmt) $stmt->close();
        }
    } else {
        $_SESSION['alert_message'] = "ID de alerta inválido para la acción.";
        $_SESSION['alert_message_type'] = "error";
    }
    header("Location: manage_alerts.php");
    exit;
}

// --- CARGAR ALERTA PARA EDICIÓN ---
if (isset($_GET['edit_id'])) {
    $edit_id = $_GET['edit_id'];
    // Asegurarse de seleccionar 'titulo' y 'contenido' para pre-llenar el formulario
    $stmt = $conn->prepare("SELECT id, titulo, contenido, tipo, fecha_inicio, fecha_fin, activa FROM site_alerts WHERE id = ?");
    if ($stmt) {
        $stmt->bind_param("i", $edit_id);
        $stmt->execute();
        $result = $stmt->get_result();
        if ($result->num_rows == 1) {
            $alert_to_edit = $result->fetch_assoc();
        } else {
            $message = "Alerta no encontrada para edición.";
            $message_type = 'error';
        }
        $stmt->close();
    }
}

// --- OBTENER TODAS LAS ALERTAS PARA LISTADO ---
$alerts = [];
// Asegurarse de seleccionar 'titulo' y 'contenido' para el listado
$result = $conn->query("SELECT id, titulo, contenido, tipo, fecha_inicio, fecha_fin, activa FROM site_alerts ORDER BY fecha_creacion DESC");
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $alerts[] = $row;
    }
}


$page_title = 'Gestionar Alertas - Colegio María Auxiliadora';
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

    <script src="https://cdn.tiny.cloud/1/8re4vem39f9zsq49n7ugnyvto179vnnssv9fi2ntx6zw4rei/tinymce/7/tinymce.min.js" referrerpolicy="origin"></script>
    <script>
        tinymce.init({
            selector: '#contenido', // Muy importante: el ID del textarea debe coincidir
            plugins: 'advlist autolink lists link image charmap print preview anchor searchreplace visualblocks code fullscreen insertdatetime media table paste code help wordcount',
            toolbar: 'undo redo | formatselect | bold italic backcolor | alignleft aligncenter alignright alignjustify | bullist numlist outdent indent | removeformat | help | image',
            language: 'es',
            height: 300,
            menubar: false,
            images_upload_url: 'upload_image.php',
            images_upload_base_path: '/',
            image_title: true,
            automatic_uploads: true,
            file_picker_types: 'image',
            file_picker_callback: function(cb, value, meta) {
                var input = document.createElement('input');
                input.setAttribute('type', 'file');
                input.setAttribute('accept', 'image/*');
                input.onchange = function() {
                    var file = this.files[0];
                    var reader = new FileReader();
                    reader.onload = function () {
                        var id = 'blobid' + (new Date()).getTime();
                        var blobCache =  tinymce.activeEditor.editorUpload.blobCache;
                        var base64 = reader.result.split(',')[1];
                        var blobInfo = blobCache.create(id, file, base64);
                        blobCache.add(blobInfo);
                        cb(blobInfo.blobUri(), { title: file.name });
                    };
                    reader.readAsDataURL(file);
                };
                input.click();
            }
        });
    </script>
</head>
<body class="<?php echo $body_class; ?>">

<?php include 'includes/header.php'; ?>

    <div class="admin-container">
        <h2><?php echo $alert_to_edit ? 'Editar Alerta' : 'Crear Nueva Alerta'; ?></h2>

        <?php if ($message): ?>
            <div class="message <?php echo htmlspecialchars($message_type); ?>">
                <?php echo htmlspecialchars($message); ?>
            </div>
        <?php endif; ?>

        <form action="manage_alerts.php" method="POST">
            <input type="hidden" name="action" value="<?php echo $alert_to_edit ? 'edit' : 'create'; ?>">
            <?php if ($alert_to_edit): ?>
                <input type="hidden" name="alert_id" value="<?php echo htmlspecialchars($alert_to_edit['id']); ?>">
            <?php endif; ?>

            <div class="form-group">
                <label for="titulo">Título Corto (Solo para Administración):</label>
                <input type="text" id="titulo" name="titulo" value="<?php echo htmlspecialchars($alert_to_edit['titulo'] ?? ''); ?>" placeholder="Ej. Alerta de Inscripciones">
            </div>

            <div class="form-group">
                <label for="contenido">Contenido de la Alerta:</label>
                <textarea id="contenido" name="contenido" rows="10"><?php echo htmlspecialchars($alert_to_edit['contenido'] ?? ''); ?></textarea>
            </div>

            <div class="form-group">
                <label for="alert_type">Tipo de Alerta:</label>
                <select id="alert_type" name="alert_type" required>
                    <?php foreach ($alert_types as $type): ?>
                        <option value="<?php echo htmlspecialchars($type); ?>" <?php echo ($alert_to_edit['tipo'] ?? 'info') == $type ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars(ucfirst($type)); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="form-group">
                <label for="start_date">Mostrar desde (opcional):</label>
                <input type="date" id="start_date" name="start_date" value="<?php echo htmlspecialchars(isset($alert_to_edit['fecha_inicio']) ? date('Y-m-d', strtotime($alert_to_edit['fecha_inicio'])) : ''); ?>">
            </div>

            <div class="form-group">
                <label for="end_date">Mostrar hasta (opcional):</label>
                <input type="date" id="end_date" name="end_date" value="<?php echo htmlspecialchars(isset($alert_to_edit['fecha_fin']) ? date('Y-m-d', strtotime($alert_to_edit['fecha_fin'])) : ''); ?>">
            </div>

            <div class="form-group">
                <input type="checkbox" id="is_active" name="is_active" value="1" <?php echo ($alert_to_edit['activa'] ?? true) ? 'checked' : ''; ?>>
                <label for="is_active" style="display:inline-block; margin-left: 5px;">Alerta Activa (Visible en el sitio)</label>
            </div>

            <button type="submit" class="form-submit-btn"><?php echo $alert_to_edit ? 'Guardar Cambios' : 'Crear Alerta'; ?></button>
            <?php if ($alert_to_edit): ?>
                <a href="manage_alerts.php" class="btn btn-secondary" style="margin-top: 10px; display: inline-block;">Cancelar Edición</a>
            <?php endif; ?>
        </form>

        <h3 style="margin-top: 50px; text-align: center;">Alertas Existentes</h3>
        <?php if (empty($alerts)): ?>
            <p class="no-records">No hay alertas configuradas.</p>
        <?php else: ?>
            <div style="overflow-x: auto;">
                <table class="solicitudes-table">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Título</th>
                            <th>Contenido (Extracto)</th>
                            <th>Tipo</th>
                            <th>Desde</th>
                            <th>Hasta</th>
                            <th>Activa</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($alerts as $alert): ?>
                        <tr>
                            <td data-label="ID"><?php echo htmlspecialchars($alert['id']); ?></td>
                            <td data-label="Título"><?php echo htmlspecialchars($alert['titulo'] ?? 'Sin título'); ?></td>
                            <td data-label="Contenido"><?php echo htmlspecialchars(substr(strip_tags($alert['contenido']), 0, 100)) . '...'; ?></td>
                            <td data-label="Tipo"><span class="status-badge <?php echo htmlspecialchars($alert['tipo']); ?>"><?php echo htmlspecialchars(ucfirst($alert['tipo'])); ?></span></td>
                            <td data-label="Desde"><?php echo $alert['fecha_inicio'] ? htmlspecialchars($alert['fecha_inicio']) : 'Siempre'; ?></td>
                            <td data-label="Hasta"><?php echo $alert['fecha_fin'] ? htmlspecialchars($alert['fecha_fin']) : 'Siempre'; ?></td>
                            <td data-label="Activa">
                                <span class="status-badge <?php echo $alert['activa'] ? 'aprobada' : 'rechazada'; ?>">
                                    <?php echo $alert['activa'] ? 'Sí' : 'No'; ?>
                                </span>
                            </td>
                            <td data-label="Acciones">
                                <div class="action-buttons-group">
                                    <a href="manage_alerts.php?edit_id=<?php echo htmlspecialchars($alert['id']); ?>" class="btn btn-sm btn-secondary">Editar</a>
                                    <a href="manage_alerts.php?action=toggle_active&id=<?php echo htmlspecialchars($alert['id']); ?>&status=<?php echo $alert['activa'] ? 'true' : 'false'; ?>"
                                       class="btn btn-sm <?php echo $alert['activa'] ? 'btn-danger' : 'btn-success'; ?>">
                                        <?php echo $alert['activa'] ? 'Desactivar' : 'Activar'; ?>
                                    </a>
                                    <a href="manage_alerts.php?action=delete&id=<?php echo htmlspecialchars($alert['id']); ?>"
                                       class="btn btn-sm btn-danger" onclick="return confirm('¿Estás seguro de que quieres eliminar esta alerta?');">
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