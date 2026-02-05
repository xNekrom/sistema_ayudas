<?php
header('Content-Type: application/json');
include '../config/db.php';

$numero = $_POST['numero_empleado'] ?? '';
$pin    = $_POST['pin'] ?? '';

$sql = "SELECT a.id, e.nombre_completo, e.puesto 
        FROM tablet_accesos a
        JOIN empleados_maestra e ON a.numero_empleado = e.numero_empleado
        WHERE a.numero_empleado = '$numero' AND a.pin = '$pin' AND a.activo = 1";

$result = $conn->query($sql);

if ($result && $result->num_rows > 0) {
    $row = $result->fetch_assoc();
    echo json_encode([
        "success" => true, 
        "usuario" => [
            "id" => $row['id'],
            "nombre" => $row['nombre_completo'],
            "puesto" => $row['puesto']
        ]
    ]);
} else {
    echo json_encode(["success" => false, "message" => "Credenciales incorrectas"]);
}
?>