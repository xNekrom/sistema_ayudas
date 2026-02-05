<?php
// --- LÓGICA DE LA PÁGINA ---
session_start();
include '../config/db.php';

// SEGURIDAD: Solo Admin y Calidad pueden entrar
$roles_permitidos = ['Admin', 'Calidad'];
if (!isset($_SESSION['usuario_id']) || !in_array($_SESSION['rol'], $roles_permitidos)) {
    header("Location: ../index.php");
    exit();
}

if (isset($_GET['resolver_id'])) {
    $id_rep = intval($_GET['resolver_id']);
    $conn->query("UPDATE reportes_errores SET estado = 'Resuelto', fecha_solucion = NOW() WHERE id = $id_rep");
    $_SESSION['flash_msg'] = "✅ Reporte marcado como resuelto.";
    $_SESSION['flash_type'] = "success";
    header("Location: gestion_reportes.php");
    exit();
}

if (isset($_GET['borrar_id'])) {
    $id_borrar = intval($_GET['borrar_id']);
    $conn->query("DELETE FROM reportes_errores WHERE id = $id_borrar");
    $_SESSION['flash_msg'] = "🗑️ Reporte eliminado.";
    $_SESSION['flash_type'] = "danger";
    header("Location: gestion_reportes.php");
    exit();
}
// --- FIN DE LÓGICA ---

require_once __DIR__ . '/../templates/header.php';
?>

<h1 class="mb-4">📢 Buzón de Calidad - Reportes</h1>

<div class="card shadow">
    <div class="card-header bg-warning text-dark">
        <h5 class="mb-0">Reportes Pendientes</h5>
    </div>
    <div class="card-body">
        <table id="dataTable" class="table table-striped table-hover">
            <thead class="table-dark">
                <tr>
                    <th>Fecha</th>
                    <th>Documento</th>
                    <th>Reportado Por</th>
                    <th>Mensaje / Error</th>
                    <th class="text-center">Acciones</th>
                </tr>
            </thead>
            <tbody>
                <?php
                // CAMBIO CLAVE: También seleccionamos r.documento_id para generar el link
                $sql = "SELECT r.id, r.fecha_reporte, r.mensaje, r.documento_id, u.nombre_completo, d.titulo, d.ruta_archivo 
                        FROM reportes_errores r
                        JOIN usuarios u ON r.usuario_reporto_id = u.id
                        LEFT JOIN documentos d ON r.documento_id = d.id
                        WHERE r.estado = 'Pendiente'
                        ORDER BY r.fecha_reporte ASC";
                
                $res = $conn->query($sql);

                if ($res->num_rows > 0) {
                    while ($row = $res->fetch_assoc()) {
                        $titulo_doc = $row['titulo'] ? htmlspecialchars($row['titulo']) : "Doc. Eliminado";
                        
                        $link_ver = "";
                        if ($row['documento_id']) {
                            $link_ver = "<a href='ver_archivo.php?id={$row['documento_id']}' target='_blank' class='btn btn-sm btn-info'><i class='fas fa-eye'></i> Ver Archivo</a>";
                        }

                        echo "<tr>
                            <td><small>{$row['fecha_reporte']}</small></td>
                            <td><strong>$titulo_doc</strong><br>$link_ver</td>
                            <td>" . htmlspecialchars($row['nombre_completo']) . "</td>
                            <td class='text-danger'>" . htmlspecialchars($row['mensaje']) . "</td>
                            <td class='text-center'>
                                <a href='gestion_reportes.php?resolver_id={$row['id']}' class='btn btn-sm btn-success mb-1 me-1' title='Marcar como resuelto'><i class='fas fa-check'></i></a>
                                <a href='gestion_reportes.php?borrar_id={$row['id']}' class='btn btn-sm btn-danger mb-1' onclick=\"return confirm('¿Borrar este reporte?');\" title='Eliminar reporte'><i class='fas fa-trash'></i></a>
                            </td>
                        </tr>";
                    }
                }
                ?>
            </tbody>
        </table>
    </div>
</div>

<?php require_once __DIR__ . '/../templates/footer.php'; ?>