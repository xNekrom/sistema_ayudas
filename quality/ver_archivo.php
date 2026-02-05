<?php
session_start();
// CAMBIO 1: Ajustar ruta de conexión
include '../config/db.php'; 

if (!isset($_SESSION['usuario_id'])) {
    die("Acceso denegado.");
}

if (isset($_GET['id'])) {
    $doc_id = intval($_GET['id']);
    $user_id = $_SESSION['usuario_id'];

    // 1. Consultar la ruta guardada en la base de datos
    $sql = "SELECT ruta_archivo FROM documentos WHERE id = $doc_id";
    $res = $conn->query($sql);

    if ($row = $res->fetch_assoc()) {
        // Asignamos la ruta de la DB (ej: uploads/TENNANT/archivo.pdf)
        $ruta_db = $row['ruta_archivo']; 

        // 2. Construir la ruta física real en el servidor
        // __DIR__ es '.../quality/'. Con '../' subimos a la raíz para entrar a 'uploads/'
        $ruta_final = realpath(__DIR__ . '/../' . $ruta_db);

        // 3. Validar que el archivo exista antes de intentar abrirlo
        if ($ruta_final && file_exists($ruta_final)) {
            
            // Guardar Log de visualización
            $conn->query("INSERT INTO historial_vistas (usuario_id, documento_id, fecha_vista) VALUES ('$user_id', '$doc_id', NOW())");

            // 4. Configurar Cabeceras para servir el PDF directamente
            header('Content-Type: application/pdf');
            header('Content-Disposition: inline; filename="' . basename($ruta_final) . '"');
            header('Content-Length: ' . filesize($ruta_final));
            
            // Limpiar buffers para evitar caracteres extra que corrompan el PDF
            ob_clean();
            flush();
            
            // Leer y enviar el archivo al navegador
            readfile($ruta_final);
            exit();
        } else {
            // Error detallado para depuración
            echo "Error: El archivo no existe físicamente en: " . htmlspecialchars($ruta_db);
        }
    } else {
        echo "Documento no encontrado en la base de datos.";
    }
}
?>