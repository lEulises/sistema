<?php
// register.php
// Lógica PHP para procesar el formulario de registro.

// ¡IMPORTANTE!: Iniciar la sesión al principio
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Incluir el archivo de configuración de la base de datos
// Asegúrate de que la ruta sea correcta (ej. 'config/config.php' si está en una subcarpeta)
include 'config.php'; 

// Inicializamos variables para mensajes de feedback
$feedback_message = '';
$feedback_type = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // 1. Recoger los datos del formulario
    $nombre = htmlspecialchars(trim($_POST['nombre'] ?? ''));
    $apellido = htmlspecialchars(trim($_POST['apellido'] ?? ''));
    $username = htmlspecialchars(trim($_POST['username'] ?? ''));
    $email = htmlspecialchars(trim($_POST['email'] ?? ''));
    $password = $_POST['password'] ?? ''; // No escapar la contraseña aquí
    $confirm_password = $_POST['confirm_password'] ?? ''; // No escapar la contraseña aquí

    // 2. Validaciones
    if (empty($nombre) || empty($apellido) || empty($username) || empty($email) || empty($password) || empty($confirm_password)) {
        $feedback_message = 'Todos los campos son obligatorios.';
        $feedback_type = 'error';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $feedback_message = 'El formato del correo electrónico no es válido.';
        $feedback_type = 'error';
    } elseif ($password !== $confirm_password) {
        $feedback_message = 'Las contraseñas no coinciden.';
        $feedback_type = 'error';
    } elseif (strlen($password) < 6) { // Ejemplo: Contraseña mínimo 6 caracteres
        $feedback_message = 'La contraseña debe tener al menos 6 caracteres.';
        $feedback_type = 'error';
    } else {
        // 3. Verificar si el nombre de usuario o correo ya existen en la base de datos
        $stmt = $conn->prepare("SELECT id FROM usuarios WHERE username = ? OR email = ?");
        $stmt->bind_param("ss", $username, $email);
        $stmt->execute();
        $stmt->store_result();

        if ($stmt->num_rows > 0) {
            $feedback_message = 'El nombre de usuario o correo electrónico ya están registrados.';
            $feedback_type = 'error';
        } else {
            // 4. Hashear la contraseña
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);

            // 5. Insertar el nuevo usuario en la base de datos
            // Definimos un rol por defecto para los nuevos registros
            $default_rol = 'representante'; // Asegúrate de que este valor sea válido en tu ENUM para 'rol'
            
            // La columna `fecha_registro` en tu tabla es `timestamp` y puede tener un valor por defecto
            // como `CURRENT_TIMESTAMP`, en cuyo caso no necesitas insertarla explícitamente aquí.
            $stmt_insert = $conn->prepare("INSERT INTO usuarios (nombre, apellido, username, password, rol, email) VALUES (?, ?, ?, ?, ?, ?)");
            $stmt_insert->bind_param("ssssss", $nombre, $apellido, $username, $hashed_password, $default_rol, $email);

            if ($stmt_insert->execute()) {
                // *** Guardar datos en la sesión y redirigir ***
                $_SESSION['user_id'] = $conn->insert_id; // ID del usuario recién insertado
                $_SESSION['nombre'] = $nombre;
                $_SESSION['apellido'] = $apellido;
                $_SESSION['username'] = $username; // Aunque no lo muestres, es útil tenerlo en sesión
                $_SESSION['rol'] = $default_rol;

                // Redirigir al usuario a la página de inicio (o a un panel de usuario)
                header('Location: index.php'); // Redirecciona a la página de inicio
                exit(); // Es crucial llamar a exit() después de un header Location
            } else {
                $feedback_message = 'Error al registrar el usuario: ' . $stmt_insert->error;
                $feedback_type = 'error';
            }
            $stmt_insert->close();
        }
        $stmt->close();
    }
    // No cierres la conexión aquí si el header/footer o algún otro script lo va a necesitar
    // $conn->close(); // Si usas la conexión en otras partes del script, ciérrala al final
}

// Configura el título de la página y la clase del body ANTES de incluir el header
$page_title = 'Colegio María Auxiliadora - Registro';
$body_class = 'auth-page'; // Para aplicar el fondo de autenticación si lo tienes en tu CSS

// Incluimos el header
include 'includes/header.php';
?>

    <div class="login-container">
        <h2>Crear Cuenta</h2>

        <?php if (!empty($feedback_message)): ?>
            <div class="feedback-message <?php echo htmlspecialchars($feedback_type); ?>">
                <?php echo htmlspecialchars($feedback_message); ?>
            </div>
        <?php endif; ?>

        <form action="register.php" method="POST" class="login-form">
            <div class="form-group">
                <label for="nombre">Nombre:</label>
                <input type="text" id="nombre" name="nombre" required>
            </div>
            <div class="form-group">
                <label for="apellido">Apellido:</label>
                <input type="text" id="apellido" name="apellido" required>
            </div>
            <div class="form-group">
                <label for="username">Usuario:</label>
                <input type="text" id="username" name="username" required autocomplete="username">
            </div>
            <div class="form-group">
                <label for="email">Correo Electrónico:</label>
                <input type="email" id="email" name="email" required autocomplete="email">
            </div>
            <div class="form-group">
                <label for="password">Contraseña:</label>
                <input type="password" id="password" name="password" required autocomplete="new-password">
            </div>
            <div class="form-group">
                <label for="confirm_password">Confirmar Contraseña:</label>
                <input type="password" id="confirm_password" name="confirm_password" required autocomplete="new-password">
            </div>
            <button type="submit">Registrarse</button>
        </form>

        <div class="form-links">
            <ul>
                <li>¿Ya tienes una cuenta? <a href="login.php">Inicia Sesión aquí</a></li>
            </ul>
        </div>
    </div>

<?php
// Incluimos el footer
include 'includes/footer.php';

// Es buena práctica cerrar la conexión a la base de datos al final del script si no se usa más
if (isset($conn) && $conn->ping()) {
    $conn->close();
}
?>