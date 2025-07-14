<?php
/*
 * Archivo: config.php
 * Descripción: Configuración para la conexión a la base de datos MySQL.
 */

// Define las constantes de conexión a la base de datos
define('DB_SERVER', 'localhost'); // Usualmente 'localhost' si la base de datos está en el mismo servidor
define('DB_USERNAME', 'root'); // ¡Cambia esto por tu usuario de la base de datos!
define('DB_PASSWORD', 'eu154382'); // ¡Cambia esto por tu contraseña de la base de datos!
define('DB_NAME', 'colegio'); // ¡Cambia esto por el nombre de tu base de datos!

/* Intentar conectar a la base de datos MySQL */
$conn = new mysqli(DB_SERVER, DB_USERNAME, DB_PASSWORD, DB_NAME);

// Verificar la conexión
if ($conn->connect_error) {
    die("ERROR: No se pudo conectar a la base de datos. " . $conn->connect_error);
}

// Opcional: Establecer el conjunto de caracteres a UTF-8 para evitar problemas con acentos y caracteres especiales
$conn->set_charset("utf8mb4");

?>