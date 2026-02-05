<?php
// --- LÓGICA DE LA PÁGINA ---
session_start();
include '../config/db.php';

// SEGURIDAD: Solo usuarios logueados
if (!isset($_SESSION['usuario_id'])) {
    header("Location: ../auth/login.php"); // Redirigir al login
    exit();
}

$base_domain = "/sistema_ayudas/uploads/"; // Ajustar a la ruta base de tus archivos subidos

// LÓGICA DE ELIMINACIÓN (solo para Admin, si es que se permite desde aquí)
if (isset($_GET['borrar_id'])) {
    if ($_SESSION['rol'] == 'Admin') {
        $id_borrar = intval($_GET['borrar_id']);
        
        $sql_buscar = "SELECT ruta_archivo FROM documentos WHERE id = $id_borrar";
        $resultado = $conn->query($sql_buscar);

        if ($fila = $resultado->fetch_assoc()) {
            // Asegurarse de que la ruta física sea correcta antes de unlink
            $ruta_fisica = str_replace($base_domain, "uploads/", $fila['ruta_archivo']);
            
            if (file_exists($ruta_fisica)) {
                unlink($ruta_fisica); // Borra el archivo físico
                $_SESSION['flash_msg'] = "🗑️ Archivo obsoleto eliminado permanentemente.";
                $_SESSION['flash_type'] = "success";
            } else {
                 $_SESSION['flash_msg'] = "⚠️ Archivo físico no encontrado, pero el registro será eliminado.";
                 $_SESSION['flash_type'] = "warning";
            }
            $conn->query("DELETE FROM documentos WHERE id = $id_borrar");
        }
    } else {
        $_SESSION['flash_msg'] = "⛔ Solo los Administradores pueden eliminar archivos de este historial.";
        $_SESSION['flash_type'] = "danger";
    }
    header("Location: historial.php");
    exit();
}
// --- FIN DE LÓGICA ---

require_once __DIR__ . '/../templates/header.php';
?>

<h1 class="mb-4">📜 Historial de Archivos Obsoletos</h1>

<div class="card shadow">
    <div class="card-header bg-dark text-white">
        <h5 class="mb-0">Archivos Antiguos (Inactivos)</h5>
    </div>
    <div class="card-body">
        <table id="dataTable" class="table table-striped table-hover">
            <thead class="table-dark">
                <tr>
                    <th>Título</th>
                    <th>Cliente</th>
                    <th>Versión</th>
                    <th>Subido Por</th>
                    <th>Fecha Subida</th>
                    <th class="text-center">Acciones</th>
                </tr>
            </thead>
            <tbody>
                <?php
                // CONSULTA: Solo traemos ACTIVO = 0
                $sql = "SELECT d.*, u.nombre_completo as autor, c.nombre as cliente_nom
                        FROM documentos d 
                        LEFT JOIN usuarios u ON d.usuario_subio_id = u.id 
                        INNER JOIN clientes c ON d.cliente_id = c.id
                        WHERE d.activo = 0 
                        ORDER BY d.id DESC";
                
                $res = $conn->query($sql);

                if ($res->num_rows > 0) {
                    while ($row = $res->fetch_assoc()) {
                        echo "<tr>
                            <td>" . htmlspecialchars($row['titulo']) . "</td>
                            <td>" . htmlspecialchars($row['cliente_nom']) . "</td>
                            <td><span class='badge bg-secondary'>v{$row['version']}</span></td>
                            <td class='text-muted'>" . htmlspecialchars($row['autor'] ?: 'Sistema') . "</td>
                            <td>{$row['fecha_subida']}</td>
                            <td class='text-center'>
                                <a href='../quality/ver_archivo.php?id={$row['id']}' target='_blank' class='btn btn-sm btn-info me-1' title='Ver Archivo'><i class='fas fa-eye'></i></a>";
                                
                                if ($_SESSION['rol'] == 'Admin') {
                                    echo "<a href='historial.php?borrar_id={$row['id']}' 
                                           class='btn btn-sm btn-danger' 
                                           onclick=\"return confirm('¿Estás seguro? Esto borrará el archivo físico para siempre.');\" title='Eliminar permanentemente'>
                                           <i class='fas fa-trash'></i>
                                        </a>";
                                }
                            echo "</td>
                        </tr>";
                    }
                }
                // Si no hay filas, el <tbody> quedará vacío y DataTables manejará el mensaje "No data available"
                ?>
            </tbody>
        </table>
    </div>
</div>

<?php require_once __DIR__ . '/../templates/footer.php'; ?>