<?php
session_start();
require_once 'config.php'; // La conexión $conn se establece aquí.

$noticias = [];

// Consulta para obtener las noticias activas, ordenadas por fecha de publicación
$sql = "SELECT id, titulo, contenido, autor_id, fecha_publicacion FROM noticias WHERE activo = TRUE ORDER BY fecha_publicacion DESC";
$stmt = $conn->prepare($sql);

if ($stmt === false) {
    echo "Error al preparar la consulta: " . $conn->error;
} else {
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $noticias[] = $row;
        }
    }
    $stmt->close();
    // ¡ELIMINA O COMENTA ESTA LÍNEA DE AQUÍ ABAJO!
    // $conn->close(); // ¡ESTA LÍNEA ES EL PROBLEMA!
}
// ... (el resto de tu código PHP y HTML de noticias.php) ...

$page_title = 'Noticias del Colegio - Colegio María Auxiliadora';
$body_class = 'noticias-page';
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

<?php include 'includes/header.php'; // Ahora $conn estará abierto cuando se incluya header.php ?>

    <div class="container section-padding">
        <h1 style="text-align: center; margin-bottom: 40px;">Últimas Noticias</h1>

        <?php if (empty($noticias)): ?>
            <p style="text-align: center;">No hay noticias publicadas en este momento.</p>
        <?php else: ?>
            <div class="noticias-grid">
                <?php foreach ($noticias as $noticia): ?>
                    <article class="noticia-card">
                        <h2><?php echo htmlspecialchars($noticia['titulo']); ?></h2>
                        <div class="noticia-meta">
                            <span class="fecha"><i class="far fa-calendar-alt"></i> <?php echo date('d/m/Y', strtotime($noticia['fecha_publicacion'])); ?></span>
                            </div>
                        <div class="noticia-contenido">
                            <?php
                                echo $noticia['contenido'];
                            ?>
                        </div>
                        </article>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>

<?php include 'includes/footer.php'; ?>

</body>
</html>
<?php
// ¡AÑADE O MUEVE LA LÍNEA $conn->close(); AQUÍ, AL FINAL DEL ARCHIVO!
// Esto asegura que la conexión se cierre después de que todas las partes de la página la hayan usado.
$conn->close();
?>