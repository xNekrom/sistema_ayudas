<?php
// 1. Evitar que errores de PHP (Warnings) se mezclen con el JSON
error_reporting(0); 

header('Content-Type: application/json');
header("Access-Control-Allow-Origin: *");

// 2. INCLUIR CONEXIÓN (Prueba estas dos rutas)
if (file_exists('../config/db.php')) {
    include '../config/db.php';
} elseif (file_exists('../db.php')) {
    include '../db.php';
} else {
    // Si no encuentra el archivo, enviamos JSON de error controlado
    echo json_encode(["error" => "No se encuentra db.php"]);
    exit();
}

// 3. CONSULTA SEGURA
// Usamos try-catch por si la tabla no existe
try {
    $sql = "SELECT id, usuario_id, titulo_de_reporte, datos_json, fecha_creacion, ruta_pdf 
            FROM reportes_calidad 
            ORDER BY fecha_creacion DESC";

    $result = $conn->query($sql);
    
    if (!$result) {
        // Si falla la query (ej. tabla no existe), enviamos array vacío para que no truene la app
        echo json_encode([]); 
        exit();
    }

    $data = [];
    while($row = $result->fetch_assoc()) {
        // Limpiamos caracteres raros del JSON para evitar errores de parseo
        $row['datos_json'] = preg_replace('/[\x00-\x1F\x7F]/u', '', $row['datos_json']);
        $data[] = $row;
    }

    echo json_encode($data);

} catch (Exception $e) {
    echo json_encode([]);
}

$conn->close();
?>