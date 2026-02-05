<?php
header('Content-Type: application/json');
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST");

include '../config/db.php'; // Ajusta ruta de ser necesario

$input = file_get_contents('php://input');
$data = json_decode($input, true);

if (isset($data['id'])) {
    $id = intval($data['id']);
    
    // Eliminamos de la tabla reportes_calidad
    $stmt = $conn->prepare("DELETE FROM reportes_calidad WHERE id = ?");
    $stmt->bind_param("i", $id);
    
    if ($stmt->execute()) {
        echo json_encode(["success" => true, "message" => "Reporte eliminado"]);
    } else {
        echo json_encode(["success" => false, "message" => "Error al eliminar en BD"]);
    }
    $stmt->close();
} else {
    echo json_encode(["success" => false, "message" => "No se recibió el ID"]);
}

$conn->close();
?>