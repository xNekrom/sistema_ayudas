<?php
header('Content-Type: application/json');
include '../config/db.php'; // Asegúrate de la ruta

$numero = $_POST['numero_empleado'] ?? '';
$pin = $_POST['pin'] ?? '';

if (empty($numero) || empty($pin)) {
    echo json_encode(["success" => false, "message" => "Faltan datos"]);
    exit();
}

// 1. Verificar si existe en la MAESTRA
$sql_check = "SELECT nombre_completo FROM empleados_maestra WHERE numero_empleado = '$numero'";
$res = $conn->query($sql_check);

if ($res->num_rows > 0) {
    // 2. Verificar si ya tiene acceso
    $check_access = $conn->query("SELECT id FROM tablet_accesos WHERE numero_empleado = '$numero'");
    
    if ($check_access->num_rows > 0) {
        // Ya tiene acceso: Actualizamos el PIN
        $conn->query("UPDATE tablet_accesos SET pin = '$pin', activo = 1 WHERE numero_empleado = '$numero'");
        echo json_encode(["success" => true, "message" => "PIN actualizado correctamente"]);
    } else {
        // No tiene acceso: Lo creamos
        $conn->query("INSERT INTO tablet_accesos (numero_empleado, pin, activo) VALUES ('$numero', '$pin', 1)");
        echo json_encode(["success" => true, "message" => "Acceso creado con éxito"]);
    }
} else {
    echo json_encode(["success" => false, "message" => "Empleado no encontrado en lista maestra"]);
}
?>