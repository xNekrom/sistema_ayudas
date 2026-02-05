<?php
// 1. INICIAR LIMPIEZA (Atrapa cualquier espacio en blanco accidental)
ob_start();

include '../config/db.php';

// 2. BORRAR LA BASURA (Descarta todo lo que no sea nuestro JSON)
ob_end_clean();

// 3. ENCABEZADOS Y LÓGICA
header('Content-Type: application/json; charset=utf-8');
header("Access-Control-Allow-Origin: *");

// Configura el charset para acentos
$conn->set_charset("utf8");

// Consulta (Asegúrate que tu tabla se llame 'clientes')
$sql = "SELECT id, nombre FROM clientes ORDER BY nombre ASC";
$result = $conn->query($sql);

$clientes = [];

if ($result && $result->num_rows > 0) {
    while($row = $result->fetch_assoc()) {
        $clientes[] = [
            "id" => (int)$row['id'], // Forzar a número
            "nombre" => $row['nombre']
        ];
    }
} else {
    // Si no hay clientes, enviamos al menos uno de prueba
    $clientes[] = ["id" => 0, "nombre" => "Cliente Manual"];
}

// 4. ENVIAR JSON PURO
echo json_encode($res->fetch_all(MYSQLI_ASSOC));
?>