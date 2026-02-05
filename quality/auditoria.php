<?php
// --- LÓGICA DE LA PÁGINA ---
session_start();
include '../config/db.php';

// SEGURIDAD: Solo Admin y Calidad
$roles_permitidos = ['Admin', 'Calidad'];
if (!isset($_SESSION['usuario_id']) || !in_array($_SESSION['rol'], $roles_permitidos)) {
    header("Location: ../index.php");
    exit();
}

// FILTROS
$fecha_inicio = isset($_GET['fecha_inicio']) ? $_GET['fecha_inicio'] : date('Y-m-01');
$fecha_fin    = isset($_GET['fecha_fin'])    ? $_GET['fecha_fin']    : date('Y-m-d');

// CONSULTA DE VISTAS
$sql = "SELECT v.fecha_vista, u.nombre_completo, d.titulo, d.version, c.nombre AS cliente_nombre 
        FROM historial_vistas v
        JOIN usuarios u ON v.usuario_id = u.id
        JOIN documentos d ON v.documento_id = d.id
        JOIN clientes c ON d.cliente_id = c.id
        WHERE v.fecha_vista BETWEEN '$fecha_inicio 00:00:00' AND '$fecha_fin 23:59:59'
        ORDER BY v.fecha_vista DESC";

$res = $conn->query($sql);
$vistas = [];
if ($res && $res->num_rows > 0) {
    while ($row = $res->fetch_assoc()) {
        $vistas[] = $row;
    }
}
// --- FIN DE LÓGICA ---

require_once __DIR__ . '/../templates/header.php';
?>

<h1 class="mb-4">👁️ Auditoría de Vistas</h1>

<div class="card shadow-sm mb-4">
    <div class="card-header">
        <h5 class="mb-0">Filtrar Vistas</h5>
    </div>
    <div class="card-body">
        <form action="auditoria.php" method="GET" class="row g-3 align-items-end">
            <div class="col-md-3">
                <label class="form-label fw-bold">Desde:</label>
                <input type="date" name="fecha_inicio" class="form-control" value="<?php echo $fecha_inicio; ?>">
            </div>
            <div class="col-md-3">
                <label class="form-label fw-bold">Hasta:</label>
                <input type="date" name="fecha_fin" class="form-control" value="<?php echo $fecha_fin; ?>">
            </div>
            <div class="col-md-3">
                <button type="submit" class="btn btn-primary w-100">
                    <i class="fas fa-filter"></i> Filtrar
                </button>
            </div>
            <div class="col-md-3">
                <button type="button" class="btn btn-success w-100" onclick="descargarExcel()">
                    <i class="fas fa-file-excel"></i> Descargar Excel
                </button>
            </div>
        </form>
    </div>
</div>

<div class="card shadow">
    <div class="card-header">
        <h5 class="mb-0">Historial de Vistas</h5>
    </div>
    <div class="card-body">
        <table id="dataTable" class="table table-striped table-hover">
            <thead class="table-dark">
                <tr>
                    <th>Fecha y Hora</th>
                    <th>Usuario</th>
                    <th>Documento Visto</th>
                    <th>Versión</th>
                    <th>Cliente</th>
                </tr>
            </thead>
            <tbody>
                <?php if (!empty($vistas)): ?>
                    <?php foreach ($vistas as $row): ?>
                        <?php $fecha_bonita = date("d/m/Y H:i", strtotime($row['fecha_vista'])); ?>
                        <tr>
                            <td><?= $fecha_bonita ?></td>
                            <td><strong><?= htmlspecialchars($row['nombre_completo']) ?></strong></td>
                            <td class='text-primary'><?= htmlspecialchars($row['titulo']) ?></td>
                            <td><span class='badge bg-secondary'>v<?= htmlspecialchars($row['version']) ?></span></td>
                            <td><?= htmlspecialchars($row['cliente_nombre']) ?></td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr><td colspan='5' class='text-center p-4 text-muted'>No hay vistas registradas en este periodo.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<script>
    function descargarExcel() {
        var f_ini = document.getElementsByName('fecha_inicio')[0].value;
        var f_fin = document.getElementsByName('fecha_fin')[0].value;
        window.location.href = 'exportar_excel.php?fecha_inicio=' + f_ini + '&fecha_fin=' + f_fin;
    }
</script>

<?php require_once __DIR__ . '/../templates/footer.php'; ?>