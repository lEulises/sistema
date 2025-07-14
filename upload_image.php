<?php
session_start();

// **SEGURIDAD:** Verificar que el usuario sea administrador.
// Si no quieres que solo admins suban, puedes ajustar esta lógica.
if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true || $_SESSION['user_role'] !== 'admin') {
    http_response_code(403); // Prohibido
    echo json_encode(['error' => 'No autorizado para subir imágenes.']);
    exit;
}

// Ruta donde se guardarán las imágenes
$upload_dir = 'assets/img/noticias/';

// Asegúrate de que la carpeta exista y sea escribible
if (!is_dir($upload_dir)) {
    // Intenta crear la carpeta con permisos de escritura
    if (!mkdir($upload_dir, 0777, true)) {
        http_response_code(500);
        echo json_encode(['error' => 'No se pudo crear el directorio de subida de imágenes.']);
        exit;
    }
}

// Verifica si se ha subido un archivo
if (isset($_FILES['file']) && $_FILES['file']['error'] === UPLOAD_ERR_OK) {
    $file = $_FILES['file'];

    // Validar tipo de archivo (solo imágenes)
    $allowed_types = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
    if (!in_array($file['type'], $allowed_types)) {
        http_response_code(400);
        echo json_encode(['error' => 'Tipo de archivo no permitido. Solo se permiten JPG, PNG, GIF, WEBP.']);
        exit;
    }

    // Validar tamaño de archivo (ej. máximo 5MB)
    $max_size = 5 * 1024 * 1024; // 5 MB
    if ($file['size'] > $max_size) {
        http_response_code(400);
        echo json_encode(['error' => 'El archivo es demasiado grande. Máximo 5MB.']);
        exit;
    }

    // Generar un nombre de archivo único para evitar colisiones
    $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
    $filename = uniqid() . '.' . $extension;
    $target_path = $upload_dir . $filename;

    // Mover el archivo subido a la carpeta de destino
    if (move_uploaded_file($file['tmp_name'], $target_path)) {
        // Devolver la URL de la imagen para TinyMCE
        echo json_encode(['location' => $target_path]);
    } else {
        http_response_code(500);
        echo json_encode(['error' => 'Error al mover el archivo subido. Verifique permisos de escritura.']);
    }
} else {
    // Si hay un error de subida (ej. archivo muy grande por configuración PHP), informarlo
    $error_message = 'No se ha subido ningún archivo o hubo un error en la subida.';
    if (isset($file['error'])) {
        switch ($file['error']) {
            case UPLOAD_ERR_INI_SIZE:
            case UPLOAD_ERR_FORM_SIZE:
                $error_message = 'El archivo subido excede el tamaño máximo permitido por el servidor.';
                break;
            case UPLOAD_ERR_PARTIAL:
                $error_message = 'El archivo subido fue cargado solo parcialmente.';
                break;
            case UPLOAD_ERR_NO_FILE:
                $error_message = 'No se seleccionó ningún archivo para subir.';
                break;
            case UPLOAD_ERR_NO_TMP_DIR:
                $error_message = 'Falta una carpeta temporal en el servidor.';
                break;
            case UPLOAD_ERR_CANT_WRITE:
                $error_message = 'Fallo al escribir el archivo en el disco.';
                break;
            case UPLOAD_ERR_EXTENSION:
                $error_message = 'Una extensión de PHP detuvo la subida del archivo.';
                break;
        }
    }
    http_response_code(400);
    echo json_encode(['error' => $error_message]);
}
?>