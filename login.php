<?php
session_start();
require_once 'config.php';

$message = '';

// Si el usuario ya está logueado, redirigir a la página de solicitudes
if (isset($_SESSION['loggedin']) && $_SESSION['loggedin'] === true) {
    header("Location: ver_solicitudes.php");
    exit;
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = trim($_POST['username'] ?? '');
    $password = trim($_POST['password'] ?? '');

    if (empty($username) || empty($password)) {
        $message = "Por favor, ingrese usuario y contraseña.";
    } else {
        $sql = "SELECT id, username, password, rol FROM usuarios WHERE username = ?";
        $stmt = $conn->prepare($sql);

        if ($stmt === false) {
            $message = "Error interno del servidor al preparar la consulta.";
        } else {
            $stmt->bind_param("s", $username);
            $stmt->execute();
            $stmt->store_result();

            if ($stmt->num_rows == 1) {
                $stmt->bind_result($id, $db_username, $hashed_password, $rol);
                $stmt->fetch();

                if (password_verify($password, $hashed_password)) {
                    // Contraseña correcta, iniciar sesión
                    $_SESSION['loggedin'] = true;
                    $_SESSION['user_id'] = $id;
                    $_SESSION['username'] = $db_username;
                    $_SESSION['user_role'] = $rol;

                    // Redirigir al panel de administración o a ver solicitudes
                    header("Location: ver_solicitudes.php");
                    exit;
                } else {
                    $message = "Usuario o contraseña incorrectos."; // Mensaje genérico por seguridad
                }
            } else {
                $message = "Usuario o contraseña incorrectos."; // Mensaje genérico por seguridad
            }
            $stmt->close();
        }
    }
}
$conn->close();
?>
    <?php include 'includes/header.php'; // Incluimos el header aquí. Asume que 'includes' está en la misma raíz que login.php ?>

    <div class="login-container">
        <h2>Iniciar Sesión</h2>
        <?php if ($message): ?>
            <div class="login-message error"><?php echo $message; ?></div>
        <?php endif; ?>
        <form action="login.php" method="POST" class="login-form">
            <div class="form-group">
                <label for="username">Usuario:</label>
                <input type="text" id="username" name="username" required autocomplete="username">
            </div>
            <div class="form-group">
                <label for="password">Contraseña:</label>
                <input type="password" id="password" name="password" required autocomplete="current-password">
            </div>
            <button type="submit">Entrar</button>
        </form>
    </div>

    <?php include 'includes/footer.php'; // Incluimos el footer aquí. ?>

</body>
</html>