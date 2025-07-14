<?php
session_start();
require_once 'config.php'; // Asegúrate de que tu archivo config.php esté bien configurado

$message = '';
$message_type = ''; // 'success' o 'error'

// Opciones para "Grado/Año a cursar"
$grados_disponibles = [
    '3er Nivel Preescolar', '1er Grado de Primaria', '2do Grado de Primaria', '3er Grado de Primaria',
    '4to Grado de Primaria', '5to Grado de Primaria', '6to Grado de Primaria', '1er Año de Bachillerato',
    '2do Año de Bachillerato', '3er Año de Bachillerato', '4to Año de Bachillerato', '5to Año de Bachillerato'
];

// Opciones para "¿Cómo obtuvo información?"
$como_se_entero_opciones = [
    'Por medio de un familiar', 'Por medio de un amigo', 'A través de la página web de la institución',
    'Redes Sociales', 'Otro'
];

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // 1. Recoger y Sanitizar los datos del formulario
    // Datos de la Madre
    $madre_nombre_completo = trim($_POST['madre_nombre_completo'] ?? '');
    $madre_cedula = trim($_POST['madre_cedula'] ?? '');
    $madre_telefono_movil = trim($_POST['madre_telefono_movil'] ?? '');
    $madre_email = trim($_POST['madre_email'] ?? '');
    $madre_direccion = trim($_POST['madre_direccion'] ?? '');

    // Datos del Padre
    $padre_nombre_completo = trim($_POST['padre_nombre_completo'] ?? '');
    $padre_telefono_movil = trim($_POST['padre_telefono_movil'] ?? '');
    $padre_cedula = trim($_POST['padre_cedula'] ?? '');
    $padre_direccion = trim($_POST['padre_direccion'] ?? '');

    // Datos del Hijo/a (1)
    $hijo1_nombre_completo = trim($_POST['hijo1_nombre_completo'] ?? '');
    $hijo1_fecha_nacimiento = trim($_POST['hijo1_fecha_nacimiento'] ?? '');
    $hijo1_cedula = trim($_POST['hijo1_cedula'] ?? '');
    $hijo1_colegio_actual = trim($_POST['hijo1_colegio_actual'] ?? '');
    $hijo1_grado_cursar = trim($_POST['hijo1_grado_cursar'] ?? '');

    // Información Adicional
    $como_se_entero = trim($_POST['como_se_entero'] ?? '');
    $como_se_entero_otro = '';
    if ($como_se_entero === 'Otro') {
        $como_se_entero_otro = trim($_POST['como_se_entero_otro'] ?? '');
    }
    $conoce_miembro_institucion = trim($_POST['conoce_miembro_institucion'] ?? '');
    $miembro_institucion_info = '';
    if ($conoce_miembro_institucion === 'SI') {
        $miembro_institucion_info = trim($_POST['miembro_institucion_info'] ?? '');
    }
    $observacion = trim($_POST['observacion'] ?? '');

    // NUEVO: Obtener el user_id de la sesión si el usuario está logueado
    $user_id = $_SESSION['user_id'] ?? null; // Si no hay sesión, será NULL

    // 2. Validar los datos (los campos obligatorios)
    $errors = [];

    // Validar datos de la Madre (obligatorios)
    if (empty($madre_nombre_completo)) $errors[] = "El nombre de la madre es requerido.";
    if (empty($madre_cedula)) $errors[] = "La cédula de la madre es requerida.";
    if (empty($madre_telefono_movil)) $errors[] = "El teléfono móvil de la madre es requerido.";
    if (empty($madre_email)) $errors[] = "El email de la madre es requerido.";
    else if (!filter_var($madre_email, FILTER_VALIDATE_EMAIL)) $errors[] = "El formato del email de la madre es inválido.";
    if (empty($madre_direccion)) $errors[] = "La dirección de residencia de la madre es requerida.";

    // Validar datos del Hijo/a (obligatorios)
    if (empty($hijo1_nombre_completo)) $errors[] = "El nombre del hijo/a es requerido.";
    if (empty($hijo1_fecha_nacimiento)) $errors[] = "La fecha de nacimiento del hijo/a es requerida.";
    if (empty($hijo1_grado_cursar) || !in_array($hijo1_grado_cursar, $grados_disponibles)) $errors[] = "El grado/año a cursar del hijo/a es inválido.";

    // Validar Información Adicional
    if (empty($como_se_entero)) $errors[] = "¿Cómo obtuvo información de la institución? es requerido.";
    if ($como_se_entero === 'Otro' && empty($como_se_entero_otro)) $errors[] = "Debe especificar cómo obtuvo la información.";
    if (empty($conoce_miembro_institucion)) $errors[] = "Indicar si conoce a un miembro de la institución es requerido.";
    if ($conoce_miembro_institucion === 'SI' && empty($miembro_institucion_info)) $errors[] = "Debe indicar el nombre y contacto del miembro de la institución.";

    if (count($errors) > 0) {
        $message = implode('<br>', $errors);
        $message_type = 'error';
    } else {
        // 3. Insertar los datos en la base de datos usando sentencias preparadas (¡Seguridad!)
        // Modificación de la sentencia INSERT para incluir user_id
        $stmt = $conn->prepare("INSERT INTO solicitudes_cupo (
            madre_nombre_completo, madre_cedula, madre_telefono_movil, madre_email, madre_direccion,
            padre_nombre_completo, padre_telefono_movil, padre_cedula, padre_direccion,
            hijo1_nombre_completo, hijo1_fecha_nacimiento, hijo1_cedula, hijo1_colegio_actual, hijo1_grado_cursar,
            como_se_entero, como_se_entero_otro, conoce_miembro_institucion, miembro_institucion_info, observacion, user_id
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");

        if ($stmt === false) {
            $message = "Error al preparar la consulta: " . $conn->error;
            $message_type = 'error';
        } else {
            // Modificación de bind_param para incluir user_id
            $stmt->bind_param("sssssssssssssssssssi", // 'i' para el user_id (integer)
                $madre_nombre_completo, $madre_cedula, $madre_telefono_movil, $madre_email, $madre_direccion,
                $padre_nombre_completo, $padre_telefono_movil, $padre_cedula, $padre_direccion,
                $hijo1_nombre_completo, $hijo1_fecha_nacimiento, $hijo1_cedula, $hijo1_colegio_actual, $hijo1_grado_cursar,
                $como_se_entero, $como_se_entero_otro, $conoce_miembro_institucion, $miembro_institucion_info, $observacion, $user_id
            );

            if ($stmt->execute()) {
                $message = "¡Su solicitud de cupo ha sido enviada con éxito! Nos pondremos en contacto con usted pronto.";
                $message_type = 'success';
                // Opcional: Limpiar los campos del formulario después del envío exitoso
                $_POST = array(); // Esto borra todos los valores enviados, para que no aparezcan en el formulario
            } else {
                $message = "Error al guardar la solicitud: " . $stmt->error;
                $message_type = 'error';
            }
            $stmt->close();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Solicitud de Cupo - Colegio María Auxiliadora</title>
    <link rel="stylesheet" href="css/styles.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&family=Playfair+Display:700&display=swap" rel="stylesheet">
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Lógica para mostrar/ocultar el campo "Otro"
            const comoSeEnteroSelect = document.getElementById('como_se_entero');
            const comoSeEnteroOtroGroup = document.getElementById('como_se_entero_otro_group');

            function toggleComoSeEnteroOtro() {
                if (comoSeEnteroSelect.value === 'Otro') {
                    comoSeEnteroOtroGroup.style.display = 'block';
                } else {
                    comoSeEnteroOtroGroup.style.display = 'none';
                }
            }
            comoSeEnteroSelect.addEventListener('change', toggleComoSeEnteroOtro);
            toggleComoSeEnteroOtro(); // Ejecutar al cargar la página para el valor inicial

            // Lógica para mostrar/ocultar el campo "Miembro de la Institución"
            const conoceMiembroSi = document.getElementById('conoce_miembro_si');
            const conoceMiembroNo = document.getElementById('conoce_miembro_no');
            const miembroInstitucionInfoGroup = document.getElementById('miembro_institucion_info_group');

            function toggleMiembroInstitucionInfo() {
                if (conoceMiembroSi.checked) {
                    miembroInstitucionInfoGroup.style.display = 'block';
                } else {
                    miembroInstitucionInfoGroup.style.display = 'none';
                }
            }
            conoceMiembroSi.addEventListener('change', toggleMiembroInstitucionInfo);
            conoceMiembroNo.addEventListener('change', toggleMiembroInstitucionInfo);
            toggleMiembroInstitucionInfo(); // Ejecutar al cargar la página para el valor inicial
        });
    </script>
</head>
<body>

<?php include 'includes/header.php'; // Incluimos el header aquí. Asegúrate de que la ruta sea correcta. ?>

    <div class="form-container">
        <h2>Formulario de Solicitud de Cupo</h2>
        <p style="text-align: center; margin-bottom: 30px; color: var(--dark-gray);">Por favor, complete todos los campos obligatorios marcados con <span style="color: red;">*</span> para enviar su solicitud.</p>

        <?php if ($message): ?>
            <div class="message <?php echo $message_type; ?>">
                <?php echo $message; ?>
            </div>
        <?php endif; ?>

        <form action="<?php echo htmlspecialchars($_SERVER['PHP_SELF']); ?>" method="POST">
            <div class="form-section">
                <h3>Datos de la Madre</h3>
                <div class="form-group">
                    <label for="madre_nombre_completo">Nombres y Apellidos Completos de la Madre <span style="color: red;">*</span></label>
                    <input type="text" id="madre_nombre_completo" name="madre_nombre_completo" value="<?php echo htmlspecialchars($_POST['madre_nombre_completo'] ?? ''); ?>" required>
                </div>
                <div class="form-group">
                    <label for="madre_cedula">Cédula de Identidad <span style="color: red;">*</span></label>
                    <input type="text" id="madre_cedula" name="madre_cedula" value="<?php echo htmlspecialchars($_POST['madre_cedula'] ?? ''); ?>" required pattern="[VEGJPvegjp]{0,1}[0-9]{6,10}" title="Ej. V12345678, E12345678">
                </div>
                <div class="form-group">
                    <label for="madre_telefono_movil">Número de Teléfono Móvil <span style="color: red;">*</span></label>
                    <input type="tel" id="madre_telefono_movil" name="madre_telefono_movil" value="<?php echo htmlspecialchars($_POST['madre_telefono_movil'] ?? ''); ?>" required pattern="[0-9]{7,15}" title="Solo números, entre 7 y 15 dígitos. Ej. 04121234567">
                </div>
                <div class="form-group">
                    <label for="madre_email">Email de la Madre <span style="color: red;">*</span></label>
                    <input type="email" id="madre_email" name="madre_email" value="<?php echo htmlspecialchars($_POST['madre_email'] ?? ''); ?>" required>
                </div>
                <div class="form-group">
                    <label for="madre_direccion">Dirección de Residencia de la Madre <span style="color: red;">*</span></label>
                    <textarea id="madre_direccion" name="madre_direccion" rows="3" required><?php echo htmlspecialchars($_POST['madre_direccion'] ?? ''); ?></textarea>
                </div>
            </div>

            <div class="form-section">
                <h3>Datos del Padre (Opcional)</h3>
                <div class="form-group">
                    <label for="padre_nombre_completo">Nombres y Apellidos Completos del Padre</label>
                    <input type="text" id="padre_nombre_completo" name="padre_nombre_completo" value="<?php echo htmlspecialchars($_POST['padre_nombre_completo'] ?? ''); ?>">
                </div>
                <div class="form-group">
                    <label for="padre_cedula">Número de Cédula del Padre</label>
                    <input type="text" id="padre_cedula" name="padre_cedula" value="<?php echo htmlspecialchars($_POST['padre_cedula'] ?? ''); ?>" pattern="[VEGJPvegjp]{0,1}[0-9]{6,10}" title="Ej. V12345678, E12345678">
                </div>
                <div class="form-group">
                    <label for="padre_telefono_movil">Número de Teléfono Móvil del Padre</label>
                    <input type="tel" id="padre_telefono_movil" name="padre_telefono_movil" value="<?php echo htmlspecialchars($_POST['padre_telefono_movil'] ?? ''); ?>" pattern="[0-9]{7,15}" title="Solo números, entre 7 y 15 dígitos. Ej. 04121234567">
                </div>
                <div class="form-group">
                    <label for="padre_direccion">Dirección de Residencia del Padre</label>
                    <textarea id="padre_direccion" name="padre_direccion" rows="3"><?php echo htmlspecialchars($_POST['padre_direccion'] ?? ''); ?></textarea>
                </div>
            </div>

            <div class="form-section">
                <h3>Datos de su Hijo/a (1)</h3>
                <div class="form-group">
                    <label for="hijo1_nombre_completo">Nombres y Apellidos del Hijo/a <span style="color: red;">*</span></label>
                    <input type="text" id="hijo1_nombre_completo" name="hijo1_nombre_completo" value="<?php echo htmlspecialchars($_POST['hijo1_nombre_completo'] ?? ''); ?>" required>
                </div>
                <div class="form-group">
                    <label for="hijo1_fecha_nacimiento">Fecha de Nacimiento <span style="color: red;">*</span></label>
                    <input type="date" id="hijo1_fecha_nacimiento" name="hijo1_fecha_nacimiento" value="<?php echo htmlspecialchars($_POST['hijo1_fecha_nacimiento'] ?? ''); ?>" required>
                </div>
                <div class="form-group">
                    <label for="hijo1_cedula">Número de Cédula de su Hijo/a (Opcional)</label>
                    <input type="text" id="hijo1_cedula" name="hijo1_cedula" value="<?php echo htmlspecialchars($_POST['hijo1_cedula'] ?? ''); ?>" pattern="[VEGJPvegjp]{0,1}[0-9]{6,10}" title="Solo números, entre 6 y 10 dígitos">
                </div>
                <div class="form-group">
                    <label for="hijo1_colegio_actual">Nombre del Colegio o Preescolar al que asiste Actualmente (Opcional)</label>
                    <input type="text" id="hijo1_colegio_actual" name="hijo1_colegio_actual" value="<?php echo htmlspecialchars($_POST['hijo1_colegio_actual'] ?? ''); ?>">
                </div>
                <div class="form-group">
                    <label for="hijo1_grado_cursar">Seleccione Grado/Año a Cursar <span style="color: red;">*</span></label>
                    <select id="hijo1_grado_cursar" name="hijo1_grado_cursar" required>
                        <option value="">Seleccione un grado</option>
                        <?php foreach ($grados_disponibles as $grado): ?>
                            <option value="<?php echo htmlspecialchars($grado); ?>" <?php echo (isset($_POST['hijo1_grado_cursar']) && $_POST['hijo1_grado_cursar'] == $grado) ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($grado); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>

            <div class="form-section">
                <h3>Información Adicional</h3>
                <div class="form-group">
                    <label>¿Cómo obtuvo información de nuestra institución? <span style="color: red;">*</span></label>
                    <select id="como_se_entero" name="como_se_entero" required>
                        <option value="">Seleccione una opción</option>
                        <?php foreach ($como_se_entero_opciones as $opcion): ?>
                            <option value="<?php echo htmlspecialchars($opcion); ?>" <?php echo (isset($_POST['como_se_entero']) && $_POST['como_se_entero'] == $opcion) ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($opcion); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group" id="como_se_entero_otro_group" style="display: none;">
                    <label for="como_se_entero_otro">Especifique cómo obtuvo la información:</label>
                    <input type="text" id="como_se_entero_otro" name="como_se_entero_otro" value="<?php echo htmlspecialchars($_POST['como_se_entero_otro'] ?? ''); ?>">
                </div>

                <div class="form-group">
                    <label>¿Conoce Ud. a un miembro de nuestra institución? <span style="color: red;">*</span></label><br>
                    <input type="radio" id="conoce_miembro_si" name="conoce_miembro_institucion" value="SI" <?php echo (isset($_POST['conoce_miembro_institucion']) && $_POST['conoce_miembro_institucion'] == 'SI') ? 'checked' : ''; ?> required>
                    <label for="conoce_miembro_si">Sí</label>
                    <input type="radio" id="conoce_miembro_no" name="conoce_miembro_institucion" value="NO" <?php echo (isset($_POST['conoce_miembro_institucion']) && $_POST['conoce_miembro_institucion'] == 'NO') ? 'checked' : ''; ?>>
                    <label for="conoce_miembro_no">No</label>
                </div>
                <div class="form-group" id="miembro_institucion_info_group" style="display: none;">
                    <label for="miembro_institucion_info">Indique el nombre y número de contacto, indíquenos también si trabaja o estudia en nuestra institución:</label>
                    <textarea id="miembro_institucion_info" name="miembro_institucion_info" rows="3"><?php echo htmlspecialchars($_POST['miembro_institucion_info'] ?? ''); ?></textarea>
                </div>

                <div class="form-group">
                    <label for="observacion">Observación (Opcional):</label>
                    <textarea id="observacion" name="observacion" rows="5"><?php echo htmlspecialchars($_POST['observacion'] ?? ''); ?></textarea>
                </div>
            </div>
            
            <button type="submit" class="form-submit-btn">Enviar Solicitud de Cupo</button>
        </form>
    </div>

<?php include 'includes/footer.php'; // Incluimos el footer aquí. Asegúrate de que la ruta sea correcta. ?>

</body>
</html>
<?php $conn->close(); // Cierra la conexión a la base de datos al finalizar ?>