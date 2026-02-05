<?php
// Cargar el autoloader de Composer para poder usar las librerías instaladas
require_once __DIR__ . '/../vendor/autoload.php';

// Cargar las variables de entorno desde el archivo .env
// __DIR__ . '/..' apunta al directorio raíz del proyecto
$dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/..');
$dotenv->load();

// Leer las credenciales desde las variables de entorno
$host = $_ENV['DB_HOST'];
$usuario = $_ENV['DB_USER'];
$pass = $_ENV['DB_PASS'];
$base_datos = $_ENV['DB_NAME'];

$conn = new mysqli($host, $usuario, $pass, $base_datos);

if ($conn->connect_error) {
    die("Error de conexión: " . $conn->connect_error);
}