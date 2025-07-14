<?php
session_start();
require_once 'config.php';

// Verificar si el usuario está logueado y es un administrador
if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true || $_SESSION['user_role'] !== 'admin') {
    header("Location: login.php");
    exit;
}

$message = '';
$message_type = '';

// Manejar mensajes de sesión (ej. después de acciones del administrador)
if (isset($_SESSION['manage_payments_message'])) {
    $message = $_SESSION['manage_payments_message'];
    $message_type = $_SESSION['manage_payments_message_type'];
    unset($_SESSION['manage_payments_message']);
    unset($_SESSION['manage_payments_message_type']);
}

// --- PROCESAR ACCIONES DEL ADMINISTRADOR (VERIFICAR/RECHAZAR) ---
if (isset($_GET['action']) && isset($_GET['id'])) {
    $payment_id = $_GET['id'];
    $action = $_GET['action']; // 'verify' o 'reject'

    if ($action == 'verify' || $action == 'reject') {
        $new_status = ($action == 'verify') ? 'Verificado' : 'Rechazado';
        $stmt = $conn->prepare("UPDATE payments SET estado_verificacion = ? WHERE id = ?");
        if ($stmt) {
            $stmt->bind_param("si", $new_status, $payment_id);
            if ($stmt->execute()) {
                $_SESSION['manage_payments_message'] = "Pago #{$payment_id} actualizado a '{$new_status}' con éxito.";
                $_SESSION['manage_payments_message_type'] = "success";
            } else {
                $_SESSION['manage_payments_message'] = "Error al actualizar pago: " . $stmt->error;
                $_SESSION['manage_payments_message_type'] = "error";
            }
            $stmt->close();
        } else {
            $_SESSION['manage_payments_message'] = "Error al preparar la actualización: " . $conn->error;
            $_SESSION['manage_payments_message_type'] = "error";
        }
    } else {
        $_SESSION['manage_payments_message'] = "Acción inválida.";
        $_SESSION['manage_payments_message_type'] = "error";
    }
    header("Location: manage_payments.php");
    exit;
}

// --- FILTROS Y BÚSQUEDA ---
$filter_status = $_GET['status'] ?? '';
$search_query = $_GET['search'] ?? '';

$sql = "SELECT p.*, s.nombre AS student_nombre, s.apellido AS student_apellido, u.username AS representative_username 
        FROM payments p
        JOIN students s ON p.student_id = s.id
        JOIN usuarios u ON p.user_id = u.id";
$where_clauses = [];
$params = [];
$types = "";

if (!empty($filter_status)) {
    $where_clauses[] = "p.estado_verificacion = ?";
    $params[] = $filter_status;
    $types .= "s";
}
if (!empty($search_query)) {
    $where_clauses[] = "(s.nombre LIKE ? OR s.apellido LIKE ? OR u.username LIKE ? OR p.referencia_bancaria LIKE ?)";
    $search_param = '%' . $search_query . '%';
    $params[] = $search_param;
    $params[] = $search_param;
    $params[] = $search_param;
    $params[] = $search_param;
    $types .= "ssss";
}

if (!empty($where_clauses)) {
    $sql .= " WHERE " . implode(" AND ", $where_clauses);
}

$sql .= " ORDER BY p.fecha_subida DESC";

$payments = [];
$stmt = $conn->prepare($sql);
if ($stmt) {
    if (!empty($params)) {
        $stmt->bind_param($types, ...$params);
    }
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $payments[] = $row;
    }
    $stmt->close();
}

$page_title = 'Gestionar Pagos - Colegio María Auxiliadora';
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
<body class="<?php echo $body_class; ?>">

<?php include 'includes/header.php'; ?>

    <div class="admin-container section-padding">
        <h2 style="text-align: center; margin-bottom: 20px;">Gestionar Pagos</h2>

        <?php if ($message): ?>
            <div class="message <?php echo htmlspecialchars($message_type); ?>">
                <?php echo htmlspecialchars($message); ?>
            </div>
        <?php endif; ?>

        <form action="manage_payments.php" method="GET" class="filter-sort-form" style="margin-bottom: 30px;">
            <div class="form-group">
                <label for="status">Filtrar por Estado:</label>
                <select id="status" name="status" onchange="this.form.submit()">
                    <option value="">Todos</option>
                    <option value="Pendiente" <?php echo ($filter_status == 'Pendiente') ? 'selected' : ''; ?>>Pendiente</option>
                    <option value="Verificado" <?php echo ($filter_status == 'Verificado') ? 'selected' : ''; ?>>Verificado</option>
                    <option value="Rechazado" <?php echo ($filter_status == 'Rechazado') ? 'selected' : ''; ?>>Rechazado</option>
                </select>
            </div>
            <div class="form-group">
                <label for="search">Buscar:</label>
                <input type="text" id="search" name="search" placeholder="Nombre estudiante, ref, usuario..." value="<?php echo htmlspecialchars($search_query); ?>">
            </div>
            <button type="submit" class="btn btn-primary">Aplicar Filtros</button>
            <a href="manage_payments.php" class="btn btn-secondary">Limpiar Filtros</a>
        </form>

        <?php if (empty($payments)): ?>
            <p class="no-records">No hay pagos registrados o que coincidan con la búsqueda.</p>
        <?php else: ?>
            <div style="overflow-x: auto;">
                <table class="solicitudes-table">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Estudiante</th>
                            <th>Representante</th>
                            <th>Monto</th>
                            <th>Referencia</th>
                            <th>Fecha Pago</th>
                            <th>Método</th>
                            <th>Comprobante</th>
                            <th>Estado</th>
                            <th>Subido</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($payments as $payment): ?>
                        <tr>
                            <td data-label="ID"><?php echo htmlspecialchars($payment['id']); ?></td>
                            <td data-label="Estudiante"><?php echo htmlspecialchars($payment['student_nombre'] . ' ' . $payment['student_apellido']); ?></td>
                            <td data-label="Representante"><?php echo htmlspecialchars($payment['representative_username']); ?></td>
                            <td data-label="Monto"><?php echo htmlspecialchars(number_format($payment['monto_pagado'], 2) . ' ' . $payment['moneda']); ?></td>
                            <td data-label="Referencia"><?php echo htmlspecialchars($payment['referencia_bancaria']); ?></td>
                            <td data-label="Fecha Pago"><?php echo htmlspecialchars(date('d/m/Y', strtotime($payment['fecha_pago_comprobante']))); ?></td>
                            <td data-label="Método"><?php echo htmlspecialchars($payment['metodo_pago']); ?></td>
                            <td data-label="Comprobante">
                                <?php if (!empty($payment['comprobante_url'])): ?>
                                    <a href="<?php echo htmlspecialchars($payment['comprobante_url']); ?>" target="_blank" class="btn btn-sm btn-primary">Ver</a>
                                <?php else: ?>
                                    N/A
                                <?php endif; ?>
                            </td>
                            <td data-label="Estado">
                                <span class="status-badge <?php echo strtolower(str_replace(' ', '_', $payment['estado_verificacion'])); ?>">
                                    <?php echo htmlspecialchars($payment['estado_verificacion']); ?>
                                </span>
                            </td>
                            <td data-label="Subido"><?php echo htmlspecialchars(date('d/m/Y H:i', strtotime($payment['fecha_subida']))); ?></td>
                            <td data-label="Acciones">
                                <div class="action-buttons-group">
                                    <?php if ($payment['estado_verificacion'] == 'Pendiente'): ?>
                                        <a href="manage_payments.php?action=verify&id=<?php echo htmlspecialchars($payment['id']); ?>" 
                                           class="btn btn-sm btn-success" onclick="return confirm('¿Confirmar pago #<?php echo htmlspecialchars($payment['id']); ?> como Verificado?');">
                                            Verificar
                                        </a>
                                        <a href="manage_payments.php?action=reject&id=<?php echo htmlspecialchars($payment['id']); ?>" 
                                           class="btn btn-sm btn-danger" onclick="return confirm('¿Rechazar pago #<?php echo htmlspecialchars($payment['id']); ?>?');">
                                            Rechazar
                                        </a>
                                    <?php elseif ($payment['estado_verificacion'] == 'Verificado'): ?>
                                        <span class="text-success">Verificado</span>
                                        <a href="manage_payments.php?action=reject&id=<?php echo htmlspecialchars($payment['id']); ?>"
                                           class="btn btn-sm btn-danger" onclick="return confirm('¿Rechazar pago #<?php echo htmlspecialchars($payment['id']); ?> (actualmente verificado)?');">
                                            Rechazar
                                        </a>
                                    <?php elseif ($payment['estado_verificacion'] == 'Rechazado'): ?>
                                        <span class="text-danger">Rechazado</span>
                                        <a href="manage_payments.php?action=verify&id=<?php echo htmlspecialchars($payment['id']); ?>"
                                           class="btn btn-sm btn-success" onclick="return confirm('¿Verificar pago #<?php echo htmlspecialchars($payment['id']); ?> (actualmente rechazado)?');">
                                            Verificar
                                        </a>
                                    <?php endif; ?>
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