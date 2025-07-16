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

$students_with_dues = [];
$total_deuda_acumulada = 0;
$total_pagado_verificado = 0;
$balance_pendiente = 0;

// Obtener todos los estudiantes para el representante logueado o todos si es admin
$sql_students_filter = "SELECT id, nombre, apellido FROM students";
$params_students = [];
$types_students = "";

if ($user_role === 'representante') {
    $sql_students_filter .= " WHERE user_id = ?";
    $params_students[] = $user_id_logged_in;
    $types_students .= "i";
}
$sql_students_filter .= " ORDER BY apellido, nombre";

$stmt_students = $conn->prepare($sql_students_filter);
if ($stmt_students) {
    if (!empty($params_students)) {
        $stmt_students->bind_param($types_students, ...$params_students);
    }
    $stmt_students->execute();
    $result_students = $stmt_students->get_result();

    while ($student = $result_students->fetch_assoc()) {
        $student_id = $student['id'];
        $student_nombre_completo = $student['nombre'] . ' ' . $student['apellido'];

        $total_deuda_estudiante = 0;
        $total_pagado_estudiante = 0;
        $detalle_mensual = [];

        // Obtener cargos mensuales para este estudiante
        $stmt_dues = $conn->prepare("SELECT id, periodo, monto_adeudado, estado_cargo FROM monthly_dues WHERE student_id = ? ORDER BY periodo ASC");
        if ($stmt_dues) {
            $stmt_dues->bind_param("i", $student_id);
            $stmt_dues->execute();
            $result_dues = $stmt_dues->get_result();
            while ($due = $result_dues->fetch_assoc()) {
                $total_deuda_estudiante += $due['monto_adeudado'];
                $detalle_mensual[$due['periodo']] = [
                    'monto_adeudado' => $due['monto_adeudado'],
                    'estado_cargo' => $due['estado_cargo'],
                    'pagos_asociados' => []
                ];
            }
            $stmt_dues->close();
        }

        // Obtener pagos verificados para este estudiante
        $stmt_payments = $conn->prepare("SELECT id, monto_pagado, fecha_pago_comprobante, referencia_bancaria FROM payments WHERE student_id = ? AND estado_verificacion = 'Verificado' ORDER BY fecha_pago_comprobante ASC");
        if ($stmt_payments) {
            $stmt_payments->bind_param("i", $student_id);
            $stmt_payments->execute();
            $result_payments = $stmt_payments->get_result();
            while ($payment = $result_payments->fetch_assoc()) {
                $total_pagado_estudiante += $payment['monto_pagado'];
                // Para vincular pagos a períodos, se necesitaría más lógica (ej. buscar qué período cubre el pago)
                // Por ahora, solo sumamos al total pagado.
            }
            $stmt_payments->close();
        }

        $balance_estudiante = $total_deuda_estudiante - $total_pagado_estudiante;

        $students_with_dues[] = [
            'id' => $student_id,
            'nombre_completo' => $student_nombre_completo,
            'total_deuda' => $total_deuda_estudiante,
            'total_pagado' => $total_pagado_estudiante,
            'balance' => $balance_estudiante,
            'detalle_mensual' => $detalle_mensual // Opcional, para una vista más detallada
        ];

        $total_deuda_acumulada += $total_deuda_estudiante;
        $total_pagado_verificado += $total_pagado_estudiante;
    }
    $stmt_students->close();
}

$balance_pendiente = $total_deuda_acumulada - $total_pagado_verificado;


$page_title = 'Mis Deudas y Pagos - Colegio María Auxiliadora';
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
    <style>
        /* Estilos básicos para la sección de deudas, puedes moverlos a styles.css */
        .debt-summary-card {
            background-color: var(--primary-blue);
            color: var(--white);
            padding: 30px;
            border-radius: 8px;
            text-align: center;
            margin-bottom: 40px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.2);
        }
        .debt-summary-card h3 {
            color: var(--accent-gold);
            font-size: 1.8em;
            margin-bottom: 10px;
        }
        .debt-summary-card p {
            font-size: 2.5em;
            font-weight: 700;
            margin-bottom: 0;
            line-height: 1.2;
        }
        .debt-summary-card p.balance-status {
            font-size: 1.2em;
            font-weight: 500;
            opacity: 0.9;
        }
        .debt-detail-section h3 {
            text-align: center;
            margin-top: 50px;
            margin-bottom: 30px;
            color: var(--primary-blue);
        }
        .student-debt-card {
            background-color: var(--white);
            padding: 25px;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.08);
            margin-bottom: 20px;
            border-left: 5px solid var(--accent-gold);
        }
        .student-debt-card h4 {
            color: var(--primary-blue);
            margin-top: 0;
            margin-bottom: 15px;
            font-size: 1.6em;
            border-bottom: 1px solid var(--medium-gray);
            padding-bottom: 10px;
        }
        .student-debt-card .debt-info p {
            margin-bottom: 8px;
            font-size: 1.05em;
            color: var(--dark-gray);
        }
        .student-debt-card .debt-info span.amount {
            font-weight: 700;
            color: var(--black);
        }
        .student-debt-card .debt-info span.currency {
            font-size: 0.9em;
            color: var(--dark-gray);
        }
        .student-debt-card .balance-status-text {
            font-weight: 700;
            font-size: 1.2em;
            color: var(--primary-blue); /* Color por defecto */
        }
        .student-debt-card .balance-status-text.positive {
            color: var(--green-accent); /* Verde si está a favor */
        }
        .student-debt-card .balance-status-text.negative {
            color: #dc3545; /* Rojo si hay deuda */
        }
        /* Estilos para la tabla responsive (reusa solicitudes-table) */
    </style>
</head>
<body class="<?php echo $body_class; ?>">

<?php include 'includes/header.php'; ?>

    <div class="container section-padding">
        <h2 style="text-align: center; margin-bottom: 40px;">Resumen de Deudas y Pagos</h2>

        <div class="debt-summary-card">
            <h3>Balance Pendiente Total</h3>
            <p><?php echo htmlspecialchars(number_format($balance_pendiente, 2)); ?> USD</p>
            <p class="balance-status">
                <?php if ($balance_pendiente > 0): ?>
                    Tienes montos pendientes por pagar.
                <?php elseif ($balance_pendiente < 0): ?>
                    Tienes un saldo a favor.
                <?php else: ?>
                    Tus pagos están al día.
                <?php endif; ?>
            </p>
        </div>

        <div class="debt-detail-section">
            <h3 style="text-align: center; margin-bottom: 30px;">Detalle por Estudiante</h3>
            <?php if (empty($students_with_dues)): ?>
                <p class="no-records" style="text-align: center;">No hay estudiantes registrados o con cargos/pagos asociados.</p>
            <?php else: ?>
                <?php foreach ($students_with_dues as $student): ?>
                    <div class="student-debt-card">
                        <h4><?php echo htmlspecialchars($student['nombre_completo']); ?></h4>
                        <div class="debt-info">
                            <p>Deuda Acumulada: <span class="amount"><?php echo htmlspecialchars(number_format($student['total_deuda'], 2)); ?></span> USD</p>
                            <p>Pagos Verificados: <span class="amount"><?php echo htmlspecialchars(number_format($student['total_pagado'], 2)); ?></span> USD</p>
                            <p>Balance Pendiente: 
                                <span class="amount balance-status-text <?php echo ($student['balance'] > 0) ? 'negative' : (($student['balance'] < 0) ? 'positive' : ''); ?>">
                                    <?php echo htmlspecialchars(number_format($student['balance'], 2)); ?>
                                </span> USD
                            </p>
                        </div>
                        <?php if (!empty($student['detalle_mensual'])): ?>
                            <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>

<?php include 'includes/footer.php'; ?>

</body>
</html>
<?php $conn->close(); ?>