<?php
header('Content-Type: application/json');
header("Access-Control-Allow-Origin: *");
include '../config/db.php';

$accion = $_GET['accion'] ?? '';

// --- LISTAR HISTORIAL ---
if ($accion == 'listar') {
    // Puedes filtrar por usuario si quieres: WHERE inspector = '...'
    $sql = "SELECT id, fecha, formato, inspector, resultado, archivo_csv 
            FROM resumen_calidad 
            ORDER BY fecha DESC LIMIT 50";
    
    $res = $conn->query($sql);
    $datos = [];
    while($r = $res->fetch_assoc()) {
        $datos[] = $r;
    }
    echo json_encode($datos);
}

// --- ELIMINAR REPORTE ---
if ($accion == 'eliminar') {
    $id = intval($_POST['id']);
    
    // 1. Obtener ruta del archivo para borrarlo
    $sql_info = "SELECT archivo_csv FROM resumen_calidad WHERE id = $id";
    $res_info = $conn->query($sql_info);
    
    if ($row = $res_info->fetch_assoc()) {

        $ruta_fisica = "../../formatos/" . $row['archivo_csv'];
        
        // 2. Borrar Archivo CSV
        if (file_exists($ruta_fisica)) {
            unlink($ruta_fisica);
        }
        
        // 3. Borrar de Base de Datos
        $conn->query("DELETE FROM resumen_calidad WHERE id = $id");
        
        echo json_encode(["success" => true, "message" => "Reporte eliminado"]);
    } else {
        echo json_encode(["success" => false, "message" => "Reporte no encontrado"]);
    }
}
?>