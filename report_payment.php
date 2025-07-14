<?php
session_start();
require_once 'config.php';

// Verificar si el usuario está logueado y tiene un rol permitido (representante o administrador)
if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true || ($_SESSION['user_role'] !== 'representante' && $_SESSION['user_role'] !== 'admin')) {
    header("Location: login.php");
    exit;
}

$user_id = $_SESSION['user_id'];
$message = '';
$message_type = '';

// Obtener los estudiantes del representante para el select
$students_list = [];
// Si es admin, puede seleccionar cualquier estudiante. Si es representante, solo los suyos.
$sql_students = "SELECT id, nombre, apellido, grado, user_id FROM students";
$params = [];
$types = "";

if ($_SESSION['user_role'] === 'representante') {
    $sql_students .= " WHERE user_id = ?";
    $params[] = $user_id;
    $types .= "i";
}
$sql_students .= " ORDER BY apellido, nombre";

$stmt = $conn->prepare($sql_students);
if ($stmt) {
    if (!empty($params)) {
        $stmt->bind_param($types, ...$params);
    }
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $students_list[] = $row;
    }
    $stmt->close();
}


// Directorio donde se guardarán los comprobantes
$upload_dir = 'assets/comprobantes/';
if (!is_dir($upload_dir)) {
    mkdir($upload_dir, 0777, true); // Crear el directorio si no existe con permisos de escritura
}

// --- PROCESAR FORMULARIO DE REPORTE DE PAGO ---
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['submit_payment'])) {
    $student_id = $_POST['student_id'] ?? null;
    $monto_pagado = $_POST['monto_pagado'] ?? '';
    $referencia_bancaria = trim($_POST['referencia_bancaria'] ?? '');
    $fecha_pago_comprobante = $_POST['fecha_pago_comprobante'] ?? null;
    $metodo_pago = $_POST['metodo_pago'] ?? '';
    $comprobante_url = ''; // Aquí se guardará la ruta del archivo subido

    // Validación básica de campos
    if (empty($monto_pagado) || empty($referencia_bancaria) || empty($fecha_pago_comprobante) || empty($metodo_pago) || empty($student_id)) {
        $message = 'Todos los campos marcados con * son obligatorios.';
        $message_type = 'error';
    } elseif (!is_numeric($monto_pagado) || $monto_pagado <= 0) {
        $message = 'El monto pagado debe ser un número positivo.';
        $message_type = 'error';
    } else {
        // Validación del archivo de comprobante
        if (isset($_FILES['comprobante']) && $_FILES['comprobante']['error'] == UPLOAD_ERR_OK) {
            $file_tmp_name = $_FILES['comprobante']['tmp_name'];
            $file_name = uniqid() . '_' . basename($_FILES['comprobante']['name']); // Nombre único para evitar conflictos
            $file_path = $upload_dir . $file_name;
            $file_type = strtolower(pathinfo($file_path, PATHINFO_EXTENSION));

            // Extensiones permitidas
            $allowed_extensions = ['jpg', 'jpeg', 'png', 'pdf'];
            if (!in_array($file_type, $allowed_extensions)) {
                $message = 'Solo se permiten archivos JPG, JPEG, PNG y PDF.';
                $message_type = 'error';
            } elseif ($_FILES['comprobante']['size'] > 5 * 1024 * 1024) { // Límite de 5MB
                $message = 'El archivo es demasiado grande (máx 5MB).';
                $message_type = 'error';
            } else {
                if (move_uploaded_file($file_tmp_name, $file_path)) {
                    $comprobante_url = $file_path;
                } else {
                    $message = 'Error al subir el archivo de comprobante.';
                    $message_type = 'error';
                }
            }
        } else {
            $message = 'Por favor, sube un archivo de comprobante.';
            $message_type = 'error';
        }

        // Si no hubo errores en la validación o subida del archivo
        if (empty($message)) {
            $stmt = $conn->prepare("INSERT INTO payments (user_id, student_id, monto_pagado, moneda, referencia_bancaria, fecha_pago_comprobante, metodo_pago, comprobante_url, estado_verificacion) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
            if ($stmt) {
                // Si es un admin el que reporta el pago, debería seleccionar el representante del estudiante.
                // Aquí usamos el user_id del representante logueado o del estudiante seleccionado.
                // Para simplificar, si el admin reporta un pago, se asocia al user_id del estudiante seleccionado.
                // Si el estudiante_id no está vacío, obtenemos su user_id asociado.
                $associated_user_id = $user_id; // Por defecto es el usuario logueado

                if ($_SESSION['user_role'] === 'admin' && $student_id) {
                    $stmt_get_student_user = $conn->prepare("SELECT user_id FROM students WHERE id = ?");
                    if ($stmt_get_student_user) {
                        $stmt_get_student_user->bind_param("i", $student_id);
                        $stmt_get_student_user->execute();
                        $res_get_student_user = $stmt_get_student_user->get_result();
                        if ($res_get_student_user->num_rows > 0) {
                            $row_user = $res_get_student_user->fetch_assoc();
                            $associated_user_id = $row_user['user_id'];
                        }
                        $stmt_get_student_user->close();
                    }
                }

                $moneda = 'USD'; // Como la mensualidad es en USD, asumimos USD para el pago
                $estado_verificacion = 'Pendiente'; // Estado inicial
                $stmt->bind_param("iisdsssss", $associated_user_id, $student_id, $monto_pagado, $moneda, $referencia_bancaria, $fecha_pago_comprobante, $metodo_pago, $comprobante_url, $estado_verificacion);

                if ($stmt->execute()) {
                    $message = "Comprobante de pago subido con éxito y a la espera de verificación.";
                    $message_type = "success";
                    // Limpiar campos del formulario después del envío exitoso
                    $_POST = array();
                } else {
                    $message = "Error al registrar el pago: " . $stmt->error;
                    $message_type = "error";
                }
                $stmt->close();
            } else {
                $message = "Error al preparar el registro del pago: " . $conn->error;
                $message_type = "error";
            }
        }
    }
    // Para persistir el mensaje después de la redirección
    $_SESSION['payment_message'] = $message;
    $_SESSION['payment_message_type'] = $message_type;
    header("Location: report_payment.php");
    exit;
}

// Manejar mensajes de sesión para la redirección
if (isset($_SESSION['payment_message'])) {
    $message = $_SESSION['payment_message'];
    $message_type = $_SESSION['payment_message_type'];
    unset($_SESSION['payment_message']);
    unset($_SESSION['payment_message_type']);
}

$page_title = 'Reportar Pago - Colegio María Auxiliadora';
$body_class = 'representante-page';
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
        <h2 style="text-align: center; margin-bottom: 20px;">Reportar Nuevo Pago</h2>

        <?php if ($message): ?>
            <div class="message <?php echo htmlspecialchars($message_type); ?>">
                <?php echo htmlspecialchars($message); ?>
            </div>
        <?php endif; ?>

        <form action="report_payment.php" method="POST" enctype="multipart/form-data">
            <div class="form-group">
                <label for="student_id">Estudiante Asociado (*):</label>
                <select id="student_id" name="student_id" required>
                    <option value="">Seleccione un estudiante</option>
                    <?php if (empty($students_list)): ?>
                        <option value="" disabled>No tienes estudiantes registrados. Por favor, regístralos primero.</option>
                    <?php else: ?>
                        <?php foreach ($students_list as $student): ?>
                            <option value="<?php echo htmlspecialchars($student['id']); ?>" <?php echo (isset($_POST['student_id']) && $_POST['student_id'] == $student['id']) ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($student['nombre'] . ' ' . $student['apellido'] . ' (' . $student['grado'] . ')'); ?>
                                <?php if ($_SESSION['user_role'] === 'admin'): ?>
                                    (Representante: <?php echo htmlspecialchars($student['user_id']); ?>)
                                <?php endif; ?>
                            </option>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </select>
            </div>

            <div class="form-group">
                <label for="monto_pagado">Monto Pagado (USD) (*):</label>
                <input type="text" id="monto_pagado" name="monto_pagado" required value="<?php echo htmlspecialchars($_POST['monto_pagado'] ?? ''); ?>">
            </div>

            <div class="form-group">
                <label for="referencia_bancaria">Referencia/Número de Comprobante (*):</label>
                <input type="text" id="referencia_bancaria" name="referencia_bancaria" required value="<?php echo htmlspecialchars($_POST['referencia_bancaria'] ?? ''); ?>">
            </div>

            <div class="form-group">
                <label for="fecha_pago_comprobante">Fecha del Pago (*):</label>
                <input type="date" id="fecha_pago_comprobante" name="fecha_pago_comprobante" required value="<?php echo htmlspecialchars($_POST['fecha_pago_comprobante'] ?? ''); ?>">
            </div>

            <div class="form-group">
                <label for="metodo_pago">Método de Pago (*):</label>
                <select id="metodo_pago" name="metodo_pago" required>
                    <option value="">Seleccione un método</option>
                    <option value="Transferencia" <?php echo (($_POST['metodo_pago'] ?? '') == 'Transferencia') ? 'selected' : ''; ?>>Transferencia</option>
                    <option value="Pago Móvil" <?php echo (($_POST['metodo_pago'] ?? '') == 'Pago Móvil') ? 'selected' : ''; ?>>Pago Móvil</option>
                    <option value="Depósito" <?php echo (($_POST['metodo_pago'] ?? '') == 'Depósito') ? 'selected' : ''; ?>>Depósito en Efectivo</option>
                    <option value="Zelle" <?php echo (($_POST['metodo_pago'] ?? '') == 'Zelle') ? 'selected' : ''; ?>>Zelle</option>
                    <option value="Binance" <?php echo (($_POST['metodo_pago'] ?? '') == 'Binance') ? 'selected' : ''; ?>>Binance</option>
                    <option value="Paypal" <?php echo (($_POST['metodo_pago'] ?? '') == 'Paypal') ? 'selected' : ''; ?>>Paypal</option>
                </select>
            </div>

            <div class="form-group">
                <label for="comprobante">Subir Comprobante (JPG, PNG, PDF - Máx 5MB) (*):</label>
                <input type="file" id="comprobante" name="comprobante" accept=".jpg,.jpeg,.png,.pdf" required>
            </div>

            <button type="submit" name="submit_payment" class="form-submit-btn">Reportar Pago</button>
        </form>
    </div>

<?php include 'includes/footer.php'; ?>

</body>
</html>
<?php $conn->close(); ?>