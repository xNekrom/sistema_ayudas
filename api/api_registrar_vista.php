<?php
header('Content-Type: application/json');
// Verifica si la ruta es ../config/db.php si el archivo está en la carpeta /api/
include '../config/db.php'; 

$usuario_id = $_POST['usuario_id'] ?? 0;
$documento_id = $_POST['documento_id'] ?? 0;

if ($usuario_id > 0 && $documento_id > 0) {
    // La inserción sigue siendo válida porque usa IDs
    $stmt = $conn->prepare("INSERT INTO historial_vistas (usuario_id, documento_id, fecha_vista) VALUES (?, ?, NOW())");
    $stmt->bind_param("ii", $usuario_id, $documento_id);
    
    if ($stmt->execute()) {
        echo json_encode(["success" => true]);
    } else {
        echo json_encode(["success" => false, "error" => $conn->error]);
    }
} else {
    echo json_encode(["success" => false, "message" => "Datos incompletos"]);
}
?>