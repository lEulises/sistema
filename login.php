<?php
session_start();
require_once 'config.php'; // Incluir la configuración de la base de datos

$username = $password = "";
$username_err = $password_err = $login_err = "";

// Redirigir si el usuario ya está logueado
if (isset($_SESSION["loggedin"]) && $_SESSION["loggedin"] === true) {
    // Si ya está logueado, redirigir según su rol
    if ($_SESSION['user_role'] == 'admin') {
        header("location: admin_dashboard.php");
    } else {
        header("location: index.php");
    }
    exit;
}

// Procesar datos cuando se envía el formulario
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Validar el nombre de usuario
    if (empty(trim($_POST["username"]))) {
        $username_err = "Por favor ingrese su usuario.";
    } else {
        $username = trim($_POST["username"]);
    }

    // Validar la contraseña
    if (empty(trim($_POST["password"]))) {
        $password_err = "Por favor ingrese su contraseña.";
    } else {
        $password = trim($_POST["password"]);
    }

    // Si no hay errores de entrada, intentar iniciar sesión
    if (empty($username_err) && empty($password_err)) {
        // Preparar una sentencia SELECT
        $sql = "SELECT id, username, password, nombre, apellido, rol FROM usuarios WHERE username = ?";

        if ($stmt = $conn->prepare($sql)) {
            // Vincular variables a la sentencia preparada como parámetros
            $stmt->bind_param("s", $param_username);

            // Establecer parámetros
            $param_username = $username;

            // Intentar ejecutar la sentencia preparada
            if ($stmt->execute()) {
                // Almacenar el resultado
                $stmt->store_result();

                // Comprobar si el usuario existe, si es así, verificar la contraseña
                if ($stmt->num_rows == 1) {
                    // Vincular variables de resultado
                    $stmt->bind_result($id, $username, $hashed_password, $nombre, $apellido, $rol);
                    if ($stmt->fetch()) {
                        if (password_verify($password, $hashed_password)) {
                            // Contraseña correcta, iniciar una nueva sesión
                            session_start();

                            // Almacenar datos en variables de sesión
                            $_SESSION["loggedin"] = true;
                            $_SESSION["user_id"] = $id;
                            $_SESSION["username"] = $username;
                            $_SESSION["nombre"] = $nombre;
                            $_SESSION["apellido"] = $apellido;
                            $_SESSION["user_role"] = $rol;

                            // Redirigir al usuario según su rol
                            if ($rol == 'admin') {
                                header("location: admin_dashboard.php");
                            } else {
                                header("location: index.php");
                            }
                        } else {
                            // Contraseña no válida
                            $login_err = "Usuario o contraseña incorrectos.";
                        }
                    }
                } else {
                    // Usuario no existe
                    $login_err = "Usuario o contraseña incorrectos.";
                }
            } else {
                echo "¡Ups! Algo salió mal. Por favor, inténtelo de nuevo más tarde.";
            }

            // Cerrar sentencia
            $stmt->close();
        }
    }

    // ¡ELIMINA O COMENTA ESTA LÍNEA DE AQUÍ ABAJO SI ESTÁ PRESENTE!
    // $conn->close(); // ¡ESTA LÍNEA ES EL PROBLEMA SI ESTÁ AQUÍ!
}

$page_title = 'Acceder - Colegio María Auxiliadora';
$body_class = 'login-page';
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

    <div class="login-container">
        <h2>Acceder al Sistema</h2>
        <p>Por favor, ingrese sus credenciales para acceder.</p>

        <?php if (!empty($login_err)): ?>
            <div class="message error login-message">
                <?php echo $login_err; ?>
            </div>
        <?php endif; ?>

        <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="post" class="login-form">
            <div class="form-group">
                <label>Usuario</label>
                <input type="text" name="username" class="form-control <?php echo (!empty($username_err)) ? 'is-invalid' : ''; ?>" value="<?php echo htmlspecialchars($username); ?>">
                <span class="invalid-feedback"><?php echo $username_err; ?></span>
            </div>
            <div class="form-group">
                <label>Contraseña</label>
                <input type="password" name="password" class="form-control <?php echo (!empty($password_err)) ? 'is-invalid' : ''; ?>">
                <span class="invalid-feedback"><?php echo $password_err; ?></span>
            </div>
            <div class="form-group">
                <button type="submit">Acceder</button>
            </div>
            <div class="form-links">
                <ul>
                    <li><a href="register.php">¿No tienes una cuenta? Regístrate aquí</a></li>
                    </ul>
            </div>
        </form>
    </div>

<?php include 'includes/footer.php'; ?>

</body>
</html>
<?php
// ¡AÑADE O MUEVE LA LÍNEA $conn->close(); AQUÍ, AL FINAL DEL ARCHIVO!
// Esto asegura que la conexión se cierre después de que todas las partes de la página la hayan usado.
$conn->close();
?>