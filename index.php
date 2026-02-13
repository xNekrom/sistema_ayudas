<?php
// --- LÓGICA DE LA PÁGINA ---
// Esta sección debe estar ANTES de incluir el header.php porque puede haber redirecciones (header())

session_start();
if (!isset($_SESSION['usuario_id'])) {
    header("Location: auth/login.php");
    exit();
}

require 'config/db.php'; // La conexión ahora se incluye desde el header, pero la dejamos por si se usa antes

// --- MANEJO DE FORMULARIOS ---

// 1. LÓGICA DE REPORTES (CON REDIRECCIÓN)
if (isset($_POST['enviar_reporte'])) {
    $doc_id = intval($_POST['doc_id_reporte']);
    $mensaje_reporte = $conn->real_escape_string($_POST['mensaje_error']);
    $usuario_actual = $_SESSION['usuario_id'];

    $sql_rep = "INSERT INTO reportes_errores (documento_id, usuario_reporto_id, mensaje) 
                VALUES ('$doc_id', '$usuario_actual', '$mensaje_reporte')";

    if ($conn->query($sql_rep)) {
        $_SESSION['flash_msg'] = "✅ Reporte enviado al departamento de Calidad. ¡Gracias!";
        $_SESSION['flash_type'] = "success";
    }
    header("Location: index.php");
    exit();
}

// 2. LÓGICA DE SUBIDA (CON cliente_id / categoria_id)
if (isset($_POST['subir'])) {
    $titulo = $conn->real_escape_string($_POST['titulo']);
    $cliente_id = intval($_POST['cliente_id']);
    $categoria_id = intval($_POST['categoria_id']);

    $res_c = $conn->query("SELECT nombre FROM clientes WHERE id = $cliente_id");
    $nom_cliente = ($res_c->num_rows > 0) ? $res_c->fetch_assoc()['nombre'] : "General";

    $nombre_archivo_original = $_FILES['archivo']['name'];
    $tipo_archivo_temporal = $_FILES['archivo']['tmp_name'];
    $ext = strtolower(pathinfo($nombre_archivo_original, PATHINFO_EXTENSION));

    if ($ext != "pdf") {
        $_SESSION['flash_msg'] = "❌ Error: El archivo debe ser PDF.";
        $_SESSION['flash_type'] = "danger";
    } else {
        $cliente_folder = preg_replace('/[^A-Za-z0-9_\-]/', '_', $nom_cliente);
        $directorio_destino = "uploads/" . $cliente_folder . "/";

        if (!file_exists($directorio_destino)) mkdir($directorio_destino, 0777, true);

        $sql_check = "SELECT id, version FROM documentos WHERE titulo = '$titulo' AND cliente_id = $cliente_id AND activo = 1";
        $check_res = $conn->query($sql_check);

        $nueva_version = 1;
        if ($check_res->num_rows > 0) {
            $row_old = $check_res->fetch_assoc();
            $nueva_version = $row_old['version'] + 1;
            $conn->query("UPDATE documentos SET activo = 0 WHERE id = " . $row_old['id']);
        }

        $nombre_final = str_replace(" ", "_", $titulo) . "_v" . $nueva_version . "_" . date("Ymd_His") . ".pdf";
        $ruta_fisica_final = $directorio_destino . $nombre_final;

        if (move_uploaded_file($tipo_archivo_temporal, $ruta_fisica_final)) {
            $sql = "INSERT INTO documentos (titulo, cliente_id, categoria_id, ruta_archivo, version, activo, usuario_subio_id, estado_aprobacion) 
            VALUES ('$titulo', $cliente_id, $categoria_id, '$ruta_fisica_final', $nueva_version, 0, '{$_SESSION['usuario_id']}', 'Pendiente')";
            $conn->query($sql);
            $_SESSION['flash_msg'] = "✅ Versión $nueva_version subida con éxito.";
            $_SESSION['flash_type'] = "success";
        }
    }
    header("Location: index.php");
    exit();
}

// 3. LÓGICA DE ELIMINACIÓN
if (isset($_GET['borrar_id'])) {
    if (in_array($_SESSION['rol'], ['Admin', 'Tecnico'])) {
        $id = intval($_GET['borrar_id']);
        $conn->query("UPDATE documentos SET activo = 2, fecha_eliminado = NOW(), usuario_elimino_id = '{$_SESSION['usuario_id']}' WHERE id = $id");
        $_SESSION['flash_msg'] = "🗑️ Archivo movido a la papelera.";
        $_SESSION['flash_type'] = "warning";
    }
    header("Location: index.php");
    exit();
}

// --- FIN DE LÓGICA ---

require_once 'templates/header.php';
?>

<!-- Contenido específico de la página -->
<h1 class="mb-4">Panel de Ayudas Visuales</h1>

<div class="card shadow mb-5">
    <div class="card-header">
        <h4 class="mb-0">Subir Nueva Ayuda Visual</h4>
    </div>
    <div class="card-body">
        <form action="index.php" method="POST" enctype="multipart/form-data">
            <div class="row">
                <div class="col-md-3 mb-3"><label class="form-label">Título</label><input type="text" name="titulo" class="form-control" required></div>
                <div class="col-md-3 mb-3">
                    <label class="form-label">Cliente</label>
                    <select name="cliente_id" class="form-select" required>
                        <?php $res = $conn->query("SELECT id, nombre FROM clientes ORDER BY nombre ASC");
                        while ($c = $res->fetch_assoc()) echo "<option value='{$c['id']}'>{$c['nombre']}</option>"; ?>
                    </select>
                </div>
                <div class="col-md-3 mb-3">
                    <label class="form-label">Categoría</label>
                    <select name="categoria_id" class="form-select" required>
                        <?php $res = $conn->query("SELECT id, nombre FROM categorias ORDER BY nombre ASC");
                        while ($cat = $res->fetch_assoc()) echo "<option value='{$cat['id']}'>{$cat['nombre']}</option>"; ?>
                    </select>
                </div>
                <div class="col-md-3 mb-3"><label class="form-label">Archivo PDF</label><input type="file" name="archivo" class="form-control" accept=".pdf" required></div>
            </div>
            <button type="submit" name="subir" class="btn btn-primary w-100">Subir Documento</button>
        </form>
    </div>
</div>

<div class="card shadow">
    <div class="card-header">
        <h4 class="mb-0">Documentos Activos</h4>
    </div>
    <div class="card-body">
        <table id="dataTable" class="table table-striped table-hover">
            <thead class="table-dark">
                <tr>
                    <th>Título</th>
                    <th>Cliente</th>
                    <th>Categoría</th>
                    <th>Usuario</th>
                    <th>Acciones</th>
                </tr>
            </thead>
            <tbody>
                <?php
                $sql = "SELECT d.*, u.nombre_completo as autor, c.nombre as cli, cat.nombre as cata 
                        FROM documentos d 
                        LEFT JOIN usuarios u ON d.usuario_subio_id = u.id 
                        INNER JOIN clientes c ON d.cliente_id = c.id
                        INNER JOIN categorias cat ON d.categoria_id = cat.id
                        WHERE d.activo = 1 ORDER BY d.id DESC";
                $res = $conn->query($sql);
                while ($row = $res->fetch_assoc()): ?>
                    <tr>
                        <td><strong><?= $row['titulo'] ?></strong> <span class="badge rounded-pill bg-primary">v<?= $row['version'] ?></span></td>
                        <td><?= $row['cli'] ?></td>
                        <td><?= $row['cata'] ?></td>
                        <td class="text-muted"><?= $row['autor'] ?: 'Sistema' ?></td>
                        <td>
                            <a href="quality/ver_archivo.php?id=<?= $row['id'] ?>" target="_blank" class="btn btn-sm btn-info text-white">Ver</a>
                            <button onclick="abrirReporteModal(<?= $row['id'] ?>, '<?= $row['titulo'] ?>')" class="btn btn-sm btn-warning">⚠️</button>
                            <?php if (in_array($_SESSION['rol'], ['Admin', 'Tecnico'])): ?>
                                <a href="index.php?borrar_id=<?= $row['id'] ?>" class="btn btn-sm btn-danger" onclick="return confirm('¿Eliminar?')">Borrar</a>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Modal para Reportes -->
<div class="modal fade" id="modalReporte" tabindex="-1">
    <div class="modal-dialog">
        <form method="POST" action="index.php" class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">⚠️ Reportar Error</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <p>Error en: <strong id="nombre_doc_modal"></strong></p>
                <input type="hidden" name="doc_id_reporte" id="input_doc_id">
                <textarea name="mensaje_error" class="form-control" rows="3" placeholder="Describe el error o la mejora sugerida..." required></textarea>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                <button type="submit" name="enviar_reporte" class="btn btn-warning">Enviar Reporte</button>
            </div>
        </form>
    </div>
</div>

<script>
    // Muevo la función JS específica de esta página aquí, fuera del footer.
    function abrirReporteModal(id, titulo) {
        document.getElementById('input_doc_id').value = id;
        document.getElementById('nombre_doc_modal').innerText = titulo;
        var myModal = new bootstrap.Modal(document.getElementById('modalReporte'));
        myModal.show();
    }
</script>

<?php require_once 'templates/footer.php'; ?>