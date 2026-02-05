<?php
// --- LÓGICA DE LA PÁGINA ---
error_reporting(0); // Suprimir errores para archivos CSV mal formados o faltantes
session_start();

if (!isset($_SESSION['usuario_id'])) die("Acceso denegado.");

$archivo_nombre_csv = $_GET['archivo'] ?? '';
if (empty($archivo_nombre_csv)) die("Error: Archivo no especificado.");

// Ruta al directorio de formatos (sube dos niveles y entra en 'formatos')
$ruta_base_formatos = realpath(__DIR__ . '/../../formatos/');
$ruta_completa_csv = $ruta_base_formatos . DIRECTORY_SEPARATOR . $archivo_nombre_csv;
// Validar que la ruta resultante esté dentro de la ruta base
if (strpos($ruta_completa_csv, $ruta_base_formatos) !== 0) {
    die("Acceso denegado: Ruta de archivo inválida.");
}

if (!file_exists($ruta_completa_csv)) {
    die("Error: El archivo CSV no fue encontrado en la ruta esperada: " . htmlspecialchars($ruta_completa_csv));
}

$lineas = [];
if (($handle = fopen($ruta_completa_csv, "r")) !== FALSE) {
    // Detectar y eliminar BOM si existe
    $bom = fread($handle, 3);
    if ($bom != "\xEF\xBB\xBF") rewind($handle);

    while (($data = fgetcsv($handle, 1000, ",")) !== FALSE) {
        $lineas[] = $data;
    }
    fclose($handle);
} else {
    die("Error al abrir el archivo CSV.");
}

// --- DETECCIÓN AMPLIADA DE FORMATOS ---
$titulo = $lineas[0][0] ?? 'Reporte Genérico';
$es_auditoria_final = strpos($titulo, 'QC-208') !== false;
$es_remachado       = strpos($titulo, 'QC-206') !== false;
$es_corte           = strpos($titulo, 'QC-205') !== false;
$es_proceso         = strpos($titulo, 'QC-207') !== false;
$es_empaque         = strpos($titulo, 'QC-220') !== false;
$es_temperatura     = strpos($titulo, 'QC-280') !== false;
$es_dimensiones     = strpos($titulo, 'QC-242') !== false;
$es_3primeras       = strpos($titulo, 'QC-281') !== false;
$es_checklist_arranque = strpos($titulo, 'QC-283') !== false;


// --- FIN DE LÓGICA ---

require_once __DIR__ . '/../templates/header.php';
?>

<h1 class="mb-4">📋 Detalle de Inspección</h1>

<div class="container mt-3 no-print d-flex justify-content-between align-items-center">
    <button onclick="history.back()" class="btn btn-outline-secondary btn-sm"><i class="fas fa-arrow-left"></i> Volver</button>
    <div>
        <button onclick="window.print()" class="btn btn-primary btn-sm"><i class="fas fa-print"></i> Imprimir PDF</button>
        <a href="../../formatos/<?= urlencode($archivo_nombre_csv) ?>" class="btn btn-success btn-sm" download><i class="fas fa-download"></i> Excel</a>
    </div>
</div>

<div class="hoja-reporte mt-4">
    <div class="header-info d-flex justify-content-between align-items-center mb-4">
        <div>
            <h3 class="fw-bold text-primary m-0"><?php echo htmlspecialchars($titulo); ?></h3>
            <span class="text-muted small">Archivo: <?php echo basename($archivo_nombre_csv); ?></span>
        </div>
        <div class="text-end">
            <p class="m-0"><strong>Fecha:</strong> <?php echo $lineas[1][1]??'--'; ?></p>
            <p class="m-0"><strong>Inspector:</strong> <?php echo $lineas[2][1]??'--'; ?></p>
        </div>
    </div>

    <?php if ($es_auditoria_final): ?>
        <table class="table table-bordered table-sm table-custom">
            <thead>
                <tr>
                    <th>Parte</th><th>Cliente</th><th>Orden</th><th>Cant</th>
                    <th class="bg-primary">F. Insp</th><th class="bg-danger">F. Rech</th>
                    <th class="bg-success">P. Insp</th><th class="bg-success">Bolsa</th><th class="bg-success">Etiq</th><th class="bg-danger">P. Rech</th>
                    <th>Defectos</th>
                </tr>
            </thead>
            <tbody>
                <?php for ($i = 5; $i < count($lineas); $i++) {
                    $r = $lineas[$i]; if (empty($r[0])) continue;
                    echo "<tr><td>{$r[0]}</td><td>{$r[1]}</td><td>{$r[2]}</td><td>{$r[3]}</td><td>{$r[5]}</td><td class='text-danger fw-bold'>{$r[6]}</td><td>{$r[8]}</td><td>{$r[9]}</td><td>{$r[10]}</td><td class='text-danger fw-bold'>{$r[11]}</td><td class='small'>{$r[12]}</td></tr>";
                } ?>
            </tbody>
        </table>

    <?php elseif ($es_corte): ?>
        <div class="mb-3 bg-light p-2 rounded small"><strong>Máquina:</strong> <?php echo $lineas[1][3]??''; ?> | <strong>Operador:</strong> <?php echo $lineas[1][5]??''; ?></div>
        <table class="table table-bordered table-sm table-custom">
            <thead>
                <tr><th>Orden</th><th>Cant</th><th>Descripción</th><th>Longitud</th><th>D2</th><th>D3</th><th>Insp</th><th>Defecto</th><th>C. Insp</th></tr>
            </thead>
            <tbody>
                <?php for ($i = 3; $i < count($lineas); $i++) {
                    $r = $lineas[$i]; if (empty($r[0]) || $r[0] == 'ORDEN') continue;
                    echo "<tr><td>{$r[0]}</td><td>{$r[1]}</td><td>{$r[2]}</td><td>{$r[3]}</td><td>{$r[4]}</td><td>{$r[5]}</td><td>{$r[6]}</td><td class='text-danger'>{$r[7]}</td><td>{$r[8]}</td></tr>";
                } ?>
            </tbody>
        </table>

    <?php elseif ($es_temperatura): ?>
        <table class="table table-bordered table-sm table-custom text-center">
            <thead><tr><th>CAUTÍN / EQUIPO</th><th>TEMP. REQUERIDA</th><th>TEMP. ACTUAL</th><th>COMENTARIOS</th></tr></thead>
            <tbody>
                <?php for ($i = 3; $i < count($lineas); $i++) {
                    $r = $lineas[$i]; if (empty($r[0]) || $r[0] == 'CAUTIN') continue;
                    $diff = abs(intval($r[1]) - intval($r[2]));
                    $color = ($diff > 10) ? 'table-danger' : '';
                    echo "<tr class='$color'><td>{$r[0]}</td><td>{$r[1]}°C</td><td>{$r[2]}°C</td><td>{$r[3]}</td></tr>";
                } ?>
            </tbody>
        </table>

    <?php elseif ($es_remachado): ?>
        <div class="row mb-3 bg-light p-2 rounded small">
            <div class="col-3"><strong>Ensamble:</strong> <?php echo $lineas[3][1]; ?></div>
            <div class="col-3"><strong>Aplicador:</strong> <?php echo $lineas[3][3]; ?></div>
            <div class="col-3"><strong>Terminal:</strong> <?php echo $lineas[3][5]; ?></div>
            <div class="col-3"><strong>Gage:</strong> <?php echo $lineas[3][7]; ?></div>
        </div>
        <table class="table table-bordered table-sm table-custom text-center">
            <thead><tr><th>HORA</th><th>VISUAL</th><th>JALÓN</th><th>ALTURA</th></tr></thead>
            <tbody>
                <?php $start = false; foreach($lineas as $r) {
                    if(isset($r[0]) && $r[0] == 'HORA') { $start = true; continue; }
                    if(!$start || empty($r[0]) || (isset($r[0]) && strpos($r[0], 'Comentarios') !== false)) continue;
                    echo "<tr><td>{$r[0]}</td><td>{$r[1]}</td><td>{$r[2]}</td><td>{$r[3]}</td></tr>";
                } ?>
            </tbody>
        </table>

    <?php elseif ($es_checklist_arranque): ?>
        <div class="row mb-3 bg-light p-2 rounded small">
            <div class="col-6"><strong>Máquina:</strong> <?php echo $lineas[1][3]??''; ?></div>
            <div class="col-6"><strong>Operador:</strong> <?php echo $lineas[1][5]??''; ?></div>
        </div>
        <table class="table table-bordered table-sm table-custom text-center">
            <thead><tr><th>Punto</th><th>SI</th><th>NO</th><th>N/A</th><th>ACCIÓN CORRECTIVA</th></tr></thead>
            <tbody>
                <?php for ($i = 6; $i <= 13; $i++) { // Puntos 1 al 8
                    $r = $lineas[$i] ?? [];
                    if (empty($r[0])) continue;
                    echo "<tr>
                            <td>{$r[0]}</td>
                            <td>" . (isset($r[1]) && strtoupper($r[1]) === 'X' ? 'X' : '') . "</td>
                            <td>" . (isset($r[2]) && strtoupper($r[2]) === 'X' ? 'X' : '') . "</td>
                            <td>" . (isset($r[3]) && strtoupper($r[3]) === 'X' ? 'X' : '') . "</td>
                            <td>" . (isset($r[4]) ? htmlspecialchars($r[4]) : '') . "</td>
                          </tr>";
                } ?>
            </tbody>
        </table>

    <?php else: // Formato genérico o no detectado ?>
        <table class="table table-bordered table-custom">
            <thead><tr><th>Punto de Inspección / Condición</th><th>Estatus</th><th>Acción Correctiva / Hallazgo</th></tr></thead>
            <tbody>
                <?php $in_table = false; foreach($lineas as $r) {
                    if(isset($r[0]) && (strpos($r[0], 'CONDICION') !== false || strpos($r[0], 'PARTE') !== false)) { $in_table = true; continue; }
                    if(!$in_table || empty($r[0]) || (isset($r[0]) && strpos($r[0], 'Comentarios') !== false)) continue;
                    $status = !empty($r[1]) ? '<span class="text-success">OK</span>' : (!empty($r[2]) ? '<span class="text-danger">FALLA</span>' : 'N/A');
                    echo "<tr><td>".htmlspecialchars($r[0])."</td><td class='text-center fw-bold'>$status</td><td>".htmlspecialchars($r[4]??$r[8]??'')."</td></tr>";
                } ?>
            </tbody>
        </table>
    <?php endif; ?>

    <div class="mt-4 row">
        <div class="col-8">
            <div class="p-3 border rounded bg-light" style="min-height: 80px;">
                <small class="fw-bold d-block text-secondary">COMENTARIOS DEL INSPECTOR:</small>
                <?php 
                $c = "Sin comentarios.";
                foreach($lineas as $l) {
                    if(isset($l[0]) && strpos($l[0], 'Comentarios') !== false && isset($l[1])) {
                        $c = htmlspecialchars($l[1]);
                        break;
                    }
                }
                echo $c;
                ?>
            </div>
        </div>
        <div class="col-4 text-center">
            <div class="mt-4 border-top border-secondary pt-2">
                <small>Firma del Auditor / Sello Calidad</small>
            </div>
        </div>
    </div>
</div>

<style>
/* Estilos específicos para la vista de detalle de inspección */
body { background: #f4f7f6; font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; }
.hoja-reporte { 
    background: white; 
    width: 250mm; 
    margin: 20px auto; 
    padding: 20mm; 
    box-shadow: 0 0 20px rgba(0,0,0,0.1); 
    border-top: 5px solid #0d6efd; /* Minty primary color */
}
.table-custom th { 
    background: #00bcd4; /* Minty info color */
    color: white; 
    font-size: 0.85rem; 
    text-align: center; 
}
.table-custom td { font-size: 0.85rem; }
.header-info { border-bottom: 2px solid #eee; margin-bottom: 20px; padding-bottom: 10px; }
@media print { 
    .no-print { display: none; } 
    .hoja-reporte { width: 100%; box-shadow: none; margin: 0; padding: 10mm; border-top: none; } 
}
</style>

<?php require_once __DIR__ . '/../templates/footer.php'; ?>