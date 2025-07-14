<?php
session_start();
require_once 'config.php'; // Asegúrate de que tu archivo config.php esté bien configurado

// Verificar que solo los administradores autenticados puedan acceder a este script
if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true || $_SESSION['user_role'] !== 'admin') {
    header("Location: login.php"); // Redirigir si no es un admin
    exit;
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $solicitud_id = $_POST['solicitud_id'] ?? null;
    $new_estado = $_POST['new_estado'] ?? null;

    // Opciones de estado permitidas (debe coincidir con la definición ENUM en la DB)
    $allowed_estados = ['pendiente', 'en_revision', 'aprobada', 'rechazada', 'inscrito'];

    if ($solicitud_id && $new_estado && in_array($new_estado, $allowed_estados)) {
        // Prepara la consulta de actualización
        $stmt = $conn->prepare("UPDATE solicitudes_cupo SET estado_solicitud = ? WHERE id = ?");

        if ($stmt === false) {
            // Manejo de error si la preparación falla
            $_SESSION['message'] = "Error interno del servidor al preparar la actualización.";
            $_SESSION['message_type'] = "error";
        } else {
            $stmt->bind_param("si", $new_estado, $solicitud_id);

            if ($stmt->execute()) {
                $_SESSION['message'] = "Estado de la solicitud ID " . htmlspecialchars($solicitud_id) . " actualizado a '" . htmlspecialchars(ucfirst(str_replace('_', ' ', $new_estado))) . "' con éxito.";
                $_SESSION['message_type'] = "success";
            } else {
                $_SESSION['message'] = "Error al actualizar el estado de la solicitud: " . $stmt->error;
                $_SESSION['message_type'] = "error";
            }
            $stmt->close();
        }
    } else {
        $_SESSION['message'] = "Datos de actualización inválidos o incompletos.";
        $_SESSION['message_type'] = "error";
    }
} else {
    $_SESSION['message'] = "Acceso inválido a la página de actualización.";
    $_SESSION['message_type'] = "error";
}

$conn->close(); // Cierra la conexión a la base de datos

// Redirigir de vuelta a la página de ver solicitudes
header("Location: ver_solicitudes.php");
exit;
?>