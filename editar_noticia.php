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
$noticia = null; // Inicializar la variable para la noticia

$noticia_id = $_GET['id'] ?? ($_POST['id'] ?? null);

if (!$noticia_id) {
    // Si no hay ID en la URL ni en POST, redirigir
    header("Location: gestionar_noticias.php");
    exit;
}

// Cargar la noticia existente si hay un ID
if ($noticia_id) {
    $stmt = $conn->prepare("SELECT id, titulo, contenido, autor_id, fecha_publicacion, activo FROM noticias WHERE id = ?");
    if ($stmt === false) {
        $message = "Error al preparar la consulta de carga: " . $conn->error;
        $message_type = 'error';
    } else {
        $stmt->bind_param("i", $noticia_id);
        $stmt->execute();
        $result = $stmt->get_result();
        if ($result->num_rows == 1) {
            $noticia = $result->fetch_assoc();
        } else {
            $message = "Noticia no encontrada.";
            $message_type = 'error';
            $noticia_id = null; // Para evitar intentar guardar si no existe
        }
        $stmt->close();
    }
}

// Procesar el formulario de edición cuando se envía por POST
if ($_SERVER['REQUEST_METHOD'] == 'POST' && $noticia_id) {
    $titulo = trim($_POST['titulo'] ?? '');
    $contenido = $_POST['contenido'] ?? ''; // El contenido de TinyMCE ya es HTML
    $activo = isset($_POST['activo']) ? 1 : 0; // Checkbox de activo/inactivo

    if (empty($titulo) || empty($contenido)) {
        $message = 'El título y el contenido de la noticia son obligatorios.';
        $message_type = 'error';
    } else {
        $stmt = $conn->prepare("UPDATE noticias SET titulo = ?, contenido = ?, activo = ? WHERE id = ?");
        if ($stmt === false) {
            $message = "Error al preparar la actualización: " . $conn->error;
            $message_type = 'error';
        } else {
            $stmt->bind_param("ssii", $titulo, $contenido, $activo, $noticia_id);
            if ($stmt->execute()) {
                $message = "Noticia actualizada con éxito.";
                $message_type = 'success';
                // Recargar los datos de la noticia para mostrar los cambios
                $stmt_reload = $conn->prepare("SELECT id, titulo, contenido, autor_id, fecha_publicacion, activo FROM noticias WHERE id = ?");
                $stmt_reload->bind_param("i", $noticia_id);
                $stmt_reload->execute();
                $result_reload = $stmt_reload->get_result();
                $noticia = $result_reload->fetch_assoc();
                $stmt_reload->close();
            } else {
                $message = "Error al actualizar la noticia: " . $stmt->error;
                $message_type = 'error';
            }
            $stmt->close();
        }
    }
}

$page_title = 'Editar Noticia - Colegio María Auxiliadora';
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
            selector: '#contenido_noticia', // ID del textarea
            plugins: 'advlist autolink lists link image charmap print preview anchor searchreplace visualblocks code fullscreen insertdatetime media table paste code help wordcount',
            toolbar: 'undo redo | formatselect | bold italic backcolor | alignleft aligncenter alignright alignjustify | bullist numlist outdent indent | removeformat | help | image', // Añade 'image' a la barra de herramientas
            language: 'es', // Configura el idioma a español
            height: 500, // Altura del editor
            menubar: false, // Oculta la barra de menú si lo prefieres más simple
            
            // --- Configuración para la subida de imágenes ---
            images_upload_url: 'upload_image.php', // ¡Punta a tu script de subida!
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
        <h2><?php echo $noticia ? 'Editar Noticia' : 'Noticia No Encontrada'; ?></h2>

        <?php if ($message): ?>
            <div class="message <?php echo $message_type; ?>">
                <?php echo $message; ?>
            </div>
        <?php endif; ?>

        <?php if ($noticia): ?>
        <form action="editar_noticia.php" method="POST">
            <input type="hidden" name="id" value="<?php echo htmlspecialchars($noticia['id']); ?>">
            <div class="form-group">
                <label for="titulo">Título de la Noticia:</label>
                <input type="text" id="titulo" name="titulo" value="<?php echo htmlspecialchars($noticia['titulo'] ?? ''); ?>" required>
            </div>
            <div class="form-group">
                <label for="contenido_noticia">Contenido de la Noticia:</label>
                <textarea id="contenido_noticia" name="contenido" rows="15"><?php echo htmlspecialchars($noticia['contenido'] ?? ''); ?></textarea>
            </div>
            <div class="form-group">
                <input type="checkbox" id="activo" name="activo" value="1" <?php echo $noticia['activo'] ? 'checked' : ''; ?>>
                <label for="activo" style="display:inline-block; margin-left: 5px;">Noticia Activa (Visible al público)</label>
            </div>
            <button type="submit" class="form-submit-btn">Guardar Cambios</button>
            <a href="gestionar_noticias.php" class="btn btn-secondary" style="margin-top: 10px; display: inline-block;">Volver a Gestión de Noticias</a>
        </form>
        <?php else: ?>
            <p style="text-align: center;">La noticia que intentas editar no existe o ha sido eliminada.</p>
            <p style="text-align: center;"><a href="gestionar_noticias.php" class="btn btn-primary">Volver a Gestión de Noticias</a></p>
        <?php endif; ?>
    </div>

<?php include 'includes/footer.php'; ?>

</body>
</html>
<?php $conn->close(); // Mueve el cierre de conexión al final del archivo principal ?>