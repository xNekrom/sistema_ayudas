<?php
// --- LÓGICA DE LA PÁGINA ---
session_start();
include '../config/db.php';

// SEGURIDAD: Solo Admin
if (!isset($_SESSION['usuario_id']) || $_SESSION['rol'] != 'Admin') {
    header("Location: ../index.php");
    exit();
}

$mensaje = "";

// LÓGICA DE RESTAURAR
if (isset($_GET['restaurar_id'])) {
    $id_restaurar = intval($_GET['restaurar_id']);
    
    // Obtenemos info para saber si choca con uno activo
    $sql_info = "SELECT titulo, cliente_id FROM documentos WHERE id = $id_restaurar";
    $res_info = $conn->query($sql_info);

    if ($fila = $res_info->fetch_assoc()) {
        $titulo = $conn->real_escape_string($fila['titulo']);
        $cliente_id = intval($fila['cliente_id']);

        $sql_check = "SELECT id FROM documentos WHERE titulo = '$titulo' AND cliente_id = $cliente_id AND activo = 1";
        $existe_activo = $conn->query($sql_check);

        if ($existe_activo->num_rows > 0) {
            $nuevo_estado = 0; // Se vuelve obsoleto
            $mensaje = "El archivo se restauró como <span class='badge bg-warning'>Obsoleto</span> (ya existe una versión activa).";
        } else {
            $nuevo_estado = 1; // Se vuelve activo
            $mensaje = "Archivo restaurado y marcado como <span class='badge bg-success'>Activo</span>.";
        }

        $sql_update = "UPDATE documentos SET activo = $nuevo_estado, fecha_eliminado = NULL, usuario_elimino_id = NULL WHERE id = $id_restaurar";

        if ($conn->query($sql_update)) {
            $_SESSION['flash_msg'] = "♻️ " . $mensaje;
            $_SESSION['flash_type'] = ($nuevo_estado == 1) ? "success" : "warning";
        } else {
            $_SESSION['flash_msg'] = "❌ Error al restaurar: " . $conn->error;
            $_SESSION['flash_type'] = "danger";
        }
    }
    header("Location: eliminados.php");
    exit();
}
// --- FIN DE LÓGICA ---

require_once __DIR__ . '/../templates/header.php';
?>

<h1 class="mb-4 text-danger">🗑️ Papelera de Reciclaje</h1>

<div class="card shadow border-danger">
    <div class="card-header bg-danger text-white">
        <h5 class="mb-0">Archivos Eliminados (Activo=2)</h5>
    </div>
    <div class="card-body">
        <table id="dataTable" class="table table-striped table-hover">
            <thead class="table-dark">
                <tr>
                    <th>Título</th>
                    <th>Cliente</th>
                    <th>Versión</th>
                    <th>Eliminado Por</th>
                    <th>Fecha Eliminación</th>
                    <th class="text-center">Acciones</th>
                </tr>
            </thead>
            <tbody>
                <?php
                $sql = "SELECT 
                            d.*, 
                            u_subio.nombre_completo AS autor_subida, 
                            u_elimino.nombre_completo AS quien_elimino,
                            c.nombre AS cliente_nom
                        FROM documentos d 
                        LEFT JOIN usuarios u_subio ON d.usuario_subio_id = u_subio.id 
                        LEFT JOIN usuarios u_elimino ON d.usuario_elimino_id = u_elimino.id 
                        INNER JOIN clientes c ON d.cliente_id = c.id
                        WHERE d.activo = 2 
                        ORDER BY d.fecha_eliminado DESC";
                
                $res = $conn->query($sql);

                if ($res->num_rows > 0) {
                    while ($row = $res->fetch_assoc()) {
                        echo "<tr>
                            <td>" . htmlspecialchars($row['titulo']) . "</td>
                            <td>" . htmlspecialchars($row['cliente_nom']) . "</td>
                            <td><span class='badge bg-secondary'>v{$row['version']}</span></td>
                            <td class='text-muted'>" . htmlspecialchars($row['quien_elimino'] ?: 'N/A') . "</td>
                            <td><small>{$row['fecha_eliminado']}</small></td>
                            <td class='text-center'>
                                <a href='../quality/ver_archivo.php?id={$row['id']}' target='_blank' class='btn btn-sm btn-info' title='Ver Archivo'><i class='fas fa-eye'></i></a>
                                <a href='eliminados.php?restaurar_id={$row['id']}' 
                                   class='btn btn-sm btn-success'
                                   onclick=\"return confirm('¿Restaurar este archivo?');\" title='Restaurar'>
                                   <i class='fas fa-recycle'></i>
                                </a>
                            </td>
                        </tr>";
                    }
                } else {
                    echo "<tr><td colspan='6' class='text-center p-4 text-muted'>Papelera vacía. 👍</td></tr>";
                }
                ?>
            </tbody>
        </table>
    </div>
</div>

<?php require_once __DIR__ . '/../templates/footer.php'; ?>