<?php
// Script: generate_monthly_dues.php
// Descripción: Genera automáticamente los cargos mensuales de colegiatura para todos los estudiantes activos.
// Debe ser ejecutado periódicamente (ej. el primer día de cada mes) mediante un Cron Job o tarea programada.

// Incluir el archivo de configuración de la base de datos
require_once 'config.php';

// Definir el monto de la mensualidad y la moneda
$monthly_amount = 350.00;
$currency = 'USD';

// Definir el período actual (ej. 'YYYY-MM') y la fecha de vencimiento (ej. día 15 del mes actual)
$current_period = date('Y-m'); // Formato YYYY-MM
$due_date = date('Y-m-15'); // Día 15 del mes actual, puedes ajustarlo

echo "--- Inicio de generación de cargos mensuales para el período: " . $current_period . " ---\n";

// 1. Obtener todos los estudiantes activos (asumo que 'Inscrito' es el estado activo)
//    Asegúrate de que el 'estado_inscripcion' en tu tabla 'students' coincida.
$sql_students = "SELECT id, nombre, apellido FROM students WHERE estado_inscripcion = 'Inscrito'";
$result_students = $conn->query($sql_students);

if ($result_students === FALSE) {
    echo "Error al obtener estudiantes: " . $conn->error . "\n";
    $conn->close();
    exit;
}

if ($result_students->num_rows == 0) {
    echo "No se encontraron estudiantes activos para generar cargos.\n";
    $conn->close();
    exit;
}

$students_processed = 0;
$charges_generated = 0;
$duplicates_skipped = 0;

while ($student = $result_students->fetch_assoc()) {
    $student_id = $student['id'];
    $student_name = $student['nombre'] . ' ' . $student['apellido'];

    echo "Procesando estudiante: {$student_name} (ID: {$student_id})\n";

    // 2. Verificar si ya existe un cargo para este estudiante para el período actual
    $sql_check_duplicate = "SELECT id FROM monthly_dues WHERE student_id = ? AND periodo = ?";
    $stmt_check = $conn->prepare($sql_check_duplicate);
    if ($stmt_check) {
        $stmt_check->bind_param("is", $student_id, $current_period);
        $stmt_check->execute();
        $stmt_check->store_result();

        if ($stmt_check->num_rows > 0) {
            echo "  - Cargo para el período {$current_period} ya existe. Saltando.\n";
            $duplicates_skipped++;
        } else {
            // 3. Si no existe, insertar un nuevo cargo en monthly_dues
            $sql_insert_due = "INSERT INTO monthly_dues (student_id, periodo, monto_adeudado, moneda, fecha_generacion, fecha_vencimiento, estado_cargo) VALUES (?, ?, ?, ?, NOW(), ?, 'Pendiente')";
            $stmt_insert = $conn->prepare($sql_insert_due);

            if ($stmt_insert) {
                $stmt_insert->bind_param("isdss", $student_id, $current_period, $monthly_amount, $currency, $due_date);
                if ($stmt_insert->execute()) {
                    echo "  - Cargo de {$monthly_amount} {$currency} generado con éxito para el período {$current_period}.\n";
                    $charges_generated++;
                } else {
                    echo "  - ERROR al generar cargo para {$student_name}: " . $stmt_insert->error . "\n";
                }
                $stmt_insert->close();
            } else {
                echo "  - ERROR al preparar inserción de cargo para {$student_name}: " . $conn->error . "\n";
            }
        }
        $stmt_check->close();
    } else {
        echo "  - ERROR al preparar verificación de duplicados para {$student_name}: " . $conn->error . "\n";
    }
    $students_processed++;
}

echo "--- Resumen de la generación de cargos ---\n";
echo "Estudiantes procesados: {$students_processed}\n";
echo "Cargos generados: {$charges_generated}\n";
echo "Cargos duplicados saltados: {$duplicates_skipped}\n";
echo "--- Fin de generación de cargos mensuales ---\n";

// Cerrar la conexión a la base de datos al final del script
$conn->close();
?>