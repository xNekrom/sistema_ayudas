<?php
// --- LÓGICA DE LA PÁGINA ---
session_start();
include '../config/db.php';

// Seguridad
if (!isset($_SESSION['usuario_id'])) { header("Location: ../index.php"); exit(); }

// Inicialización de variables para evitar errores
$registros_validos = [];
$aprobados = 0;
$rechazados = 0;
$nombres_inspectores = [];
$conteo_inspecciones = [];

// Consulta SQL
$sql = "SELECT * FROM resumen_calidad ORDER BY fecha DESC"; // Asumiendo que 'resumen_calidad' es una vista o tabla relevante
$res = $conn->query($sql);

if ($res && $res->num_rows > 0) {
    while ($row = $res->fetch_assoc()) {
        $registros_validos[] = $row;
        
        // Lógica de colores y conteo para gráficas
        $estado = strtoupper(trim($row['resultado']));
        if ($estado == 'APROBADO') {
            $aprobados++;
        } else {
            $rechazados++;
        }

        // Datos para la gráfica de barras (Inspectores)
        $ins = $row['inspector'];
        if (!isset($conteo_inspecciones[$ins])) {
            $conteo_inspecciones[$ins] = 0;
        }
        $conteo_inspecciones[$ins]++;
    }
}

// Preparar datos para JS
$labels_barras = array_keys($conteo_inspecciones);
$data_barras = array_values($conteo_inspecciones);

// --- FIN DE LÓGICA ---

require_once __DIR__ . '/../templates/header.php';
?>

<h1 class="mb-4">Panel de Control de Calidad</h1>

<div class="row mb-4">
    <div class="col-md-6 mb-3">
        <div class="card shadow-sm p-3 border-success border-3">
            <h6 class="text-uppercase small">Inspecciones Aprobadas</h6>
            <h2 class="fw-bold text-success"><?= $aprobados ?></h2>
        </div>
    </div>
    <div class="col-md-6 mb-3">
        <div class="card shadow-sm p-3 border-danger border-3">
            <h6 class="text-uppercase small">Inspecciones Rechazadas</h6>
            <h2 class="fw-bold text-danger"><?= $rechazados ?></h2>
        </div>
    </div>
</div>

<div class="row mb-4">
    <div class="col-md-7">
        <div class="card shadow-sm p-4">
            <h6 class="fw-bold text-muted mb-3">Inspecciones por Inspector</h6>
            <canvas id="chartBar" height="150"></canvas>
        </div>
    </div>
    <div class="col-md-5">
        <div class="card shadow-sm p-4 text-center">
            <h6 class="fw-bold text-muted mb-3">Distribución de Resultados</h6>
            <div style="max-width: 280px; margin: auto;">
                <canvas id="chartPie"></canvas>
            </div>
        </div>
    </div>
</div>

<div class="card shadow-sm p-4 mb-5">
    <h6 class="fw-bold text-muted mb-4">Historial Reciente</h6>
    <div class="table-responsive">
        <table class="table table-hover align-middle" id="dataTable">
            <thead>
                <tr>
                    <th>Fecha</th>
                    <th>Inspector</th>
                    <th>Resultado</th>
                    <th class="text-center">Exportar</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($registros_validos as $r): 
                    $status = strtoupper(trim($r['resultado']));
                    $badge = ($status == 'APROBADO') ? 'bg-success' : 'bg-danger';
                ?>
                    <tr>
                        <td class="small"><?= date("d/m/y H:i", strtotime($r['fecha'])) ?></td>
                        <td class="fw-bold"><?= htmlspecialchars($r['inspector']) ?></td>
                        <td><span class="badge rounded-pill <?= $badge ?> px-3 shadow-sm"><?= $status ?></span></td>
                        <td class="text-center">
                            <a href="../quality/llenar_formato.php?archivo=<?= urlencode($r['archivo_csv']) ?>" 
                               class="btn btn-sm btn-info">
                                <i class="fas fa-file-excel"></i> Exportar
                            </a>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<script>
    // Gráfica de Barras
    const ctxBar = document.getElementById('chartBar').getContext('2d');
    new Chart(ctxBar, {
        type: 'bar',
        data: {
            labels: <?= json_encode($labels_barras) ?>,
            datasets: [{
                label: 'Cantidad',
                data: <?= json_encode($data_barras) ?>,
                backgroundColor: 'rgba(0, 188, 212, 0.7)', // Minty accent color
                borderColor: 'rgba(0, 188, 212, 1)',
                borderWidth: 1,
                borderRadius: 5
            }]
        },
        options: { 
            responsive: true,
            plugins: { legend: { display: false } },
            scales: {
                y: { beginAtZero: true }
            }
        }
    });

    // Gráfica de Pastel
    const ctxPie = document.getElementById('chartPie').getContext('2d');
    new Chart(ctxPie, {
        type: 'doughnut',
        data: {
            labels: ['Aprobados', 'Rechazados'],
            datasets: [{
                data: [<?= $aprobados ?>, <?= $rechazados ?>],
                backgroundColor: ['#28a745', '#dc3545'] // Green for approved, Red for rejected
            }]
        },
        options: { 
            responsive: true,
            cutout: '70%',
            plugins: { legend: { position: 'bottom' } }
        }
    });
</script>

<?php require_once __DIR__ . '/../templates/footer.php'; ?>