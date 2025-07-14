<?php
session_start();
require_once 'config.php'; // Asegúrate de que tu archivo config.php esté bien configurado

// **IMPORTANTE:** Verificar la autenticación y el rol de administrador
if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true || $_SESSION['user_role'] !== 'admin') {
    header("Location: login.php"); // Redirigir a una página de login si no es admin
    exit;
}

$message = '';
$message_type = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $titulo = trim($_POST['titulo'] ?? '');
    // El contenido del editor TinyMCE ya viene como HTML, no necesitas htmlspecialchars en él.
    $contenido = $_POST['contenido'] ?? '';
    $autor_id = $_SESSION['user_id']; // El ID del administrador logueado

    if (empty($titulo) || empty($contenido)) {
        $message = 'El título y el contenido de la noticia son obligatorios.';
        $message_type = 'error';
    } else {
        $stmt = $conn->prepare("INSERT INTO noticias (titulo, contenido, autor_id) VALUES (?, ?, ?)");
        if ($stmt === false) {
            $message = "Error al preparar la consulta: " . $conn->error;
            $message_type = 'error';
        } else {
            $stmt->bind_param("ssi", $titulo, $contenido, $autor_id);
            if ($stmt->execute()) {
                $message = "Noticia creada con éxito.";
                $message_type = 'success';
                // Opcional: limpiar los campos del formulario o redirigir
                $_POST = array(); // Limpia los campos
            } else {
                $message = "Error al crear la noticia: " . $stmt->error;
                $message_type = 'error';
            }
            $stmt->close();
        }
    }
}

// Configurar el título de la página y la clase del body
$page_title = 'Crear Nueva Noticia - Colegio María Auxiliadora';
$body_class = 'admin-page'; // Puedes definir un estilo específico para esta página
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
            images_upload_base_path: '/', // Ruta base para las imágenes (opcional, si tus rutas de servidor son complejas)
            image_title: true, // Permite al usuario añadir un título a la imagen
            automatic_uploads: true, // Permite arrastrar y soltar imágenes
            file_picker_types: 'image', // Habilita el selector de archivos para imágenes
            file_picker_callback: function(cb, value, meta) {
                var input = document.createElement('input');
                input.setAttribute('type', 'file');
                input.setAttribute('accept', 'image/*'); // Solo acepta archivos de imagen

                input.onchange = function() {
                    var file = this.files[0];
                    var reader = new FileReader();
                    reader.onload = function () {
                        var id = 'blobid' + (new Date()).getTime();
                        var blobCache =  tinymce.activeEditor.editorUpload.blobCache;
                        var base64 = reader.result.split(',')[1];
                        var blobInfo = blobCache.create(id, file, base64);
                        blobCache.add(blobInfo);

                        /* call the callback and populate the Title field with the name of the file */
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
        <h2>Crear Nueva Noticia</h2>

        <?php if ($message): ?>
            <div class="message <?php echo $message_type; ?>">
                <?php echo $message; ?>
            </div>
        <?php endif; ?>

        <form action="crear_noticia.php" method="POST">
            <div class="form-group">
                <label for="titulo">Título de la Noticia:</label>
                <input type="text" id="titulo" name="titulo" value="<?php echo htmlspecialchars($_POST['titulo'] ?? ''); ?>" required>
            </div>
            <div class="form-group">
                <label for="contenido_noticia">Contenido de la Noticia:</label>
                <textarea id="contenido_noticia" name="contenido" rows="15"><?php echo htmlspecialchars($_POST['contenido'] ?? ''); ?></textarea>
            </div>
            <button type="submit" class="form-submit-btn">Publicar Noticia</button>
        </form>
    </div>

<?php include 'includes/footer.php'; ?>

</body>
</html>
<?php $conn->close(); // Mueve el cierre de conexión al final del archivo principal ?>