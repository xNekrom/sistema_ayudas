<?php
session_start();
include '../config/db.php';

// Seguridad: Solo Admin
if (!isset($_SESSION['usuario_id']) || $_SESSION['rol'] !== 'Admin') {
    die("Acceso denegado.");
}

$borrados = 0;
$analizados = 0;

// Consultamos todos los registros para verificar sus archivos
$sql = "SELECT id, archivo_csv FROM resumen_calidad";
$res = $conn->query($sql);

if ($res) {
    while ($row = $res->fetch_assoc()) {
        $analizados++;
        $ruta_fisica = "../../formatos/" . $row['archivo_csv'];

        // Si el archivo NO existe físicamente, eliminamos el registro de la BD
        if (!file_exists($ruta_fisica)) {
            $id_borrar = $row['id'];
            $conn->query("DELETE FROM resumen_calidad WHERE id = $id_borrar");
            $borrados++;
        }
    }
}

// Guardar resultado en la sesión para mostrar mensaje en el Dashboard
$_SESSION['msj_limpieza'] = "Limpieza completada: $analizados registros analizados, $borrados registros huérfanos eliminados.";

// Redirigir de vuelta al Dashboard
header("Location: dashboard.php");
exit();