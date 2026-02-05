<?php
// api.php - Versión optimizada con JOINs
include '../config/db.php';

// Consulta profesional usando JOINs para mantener compatibilidad con el modelo de Kotlin
$query = "SELECT 
            d.id, 
            d.titulo, 
            c.nombre AS cliente, 
            cat.nombre AS categoria, 
            d.ruta_archivo AS url, 
            d.version, 
            u.nombre_completo AS autor
          FROM documentos d
          INNER JOIN clientes c ON d.cliente_id = c.id
          INNER JOIN categorias cat ON d.categoria_id = cat.id
          LEFT JOIN usuarios u ON d.usuario_subio_id = u.id
          WHERE d.activo = 1
          ORDER BY d.titulo ASC";

$result = $conn->query($query);
$documentos = [];

if ($result) {
    while($row = $result->fetch_assoc()) {
        $documentos[] = $row;
    }
}

header('Content-Type: application/json');
echo json_encode($documentos);