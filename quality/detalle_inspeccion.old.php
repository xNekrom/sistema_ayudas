<?php
// SILENCIAR ERRORES MENORES (Para que no salgan warnings en pantalla)
error_reporting(0);

session_start();
if (!isset($_SESSION['usuario_id'])) die("Acceso denegado. Inicia sesión.");

if (!isset($_GET['archivo']) || empty($_GET['archivo'])) die("Error: Archivo no especificado.");

$ruta_relativa = $_GET['archivo'];
$ruta_completa = realpath("../../formatos/" . $ruta_relativa);

if (!$ruta_completa || !file_exists($ruta_completa)) die("Archivo no encontrado: " . htmlspecialchars($ruta_relativa));

// Leer CSV
$lineas = [];
if (($handle = fopen($ruta_completa, "r")) !== FALSE) {
    $bom = fread($handle, 3);
    if ($bom != "\xEF\xBB\xBF") rewind($handle);
    while (($data = fgetcsv($handle, 1000, ",")) !== FALSE) {
        $lineas[] = $data;
    }
    fclose($handle);
}

// --- DETECCIÓN INTELIGENTE DEL TIPO DE FORMATO ---
$titulo = $lineas[0][0] ?? 'Reporte Genérico';
$es_auditoria = strpos($titulo, 'QC-208') !== false;
$es_remachado = strpos($titulo, 'REMACHADO') !== false; // Nueva detección
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Detalle Inspección</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { background: #e9ecef; }
        .hoja-reporte { 
            background: white; 
            width: 230mm; 
            margin: 30px auto; 
            padding: 15mm; 
            box-shadow: 0 0 15px rgba(0,0,0,0.15); 
            border-radius: 4px; 
        }
        .table-custom th { background: #0d6efd; color: white; text-align: center; vertical-align: middle; }
        .table-custom td { vertical-align: middle; }
        @media print { 
            .no-print { display: none; } 
            .hoja-reporte { box-shadow: none; margin: 0; width: 100%; padding: 0; } 
        }
    </style>
</head>
<body>

<div class="container mt-3 mb-3 no-print d-flex justify-content-between">
    <button onclick="history.back()" class="btn btn-secondary">⬅ Volver</button>
    <div>
        <button onclick="window.print()" class="btn btn-primary me-2">🖨️ Imprimir</button>
        <a href="<?php echo '../../formatos/' . $ruta_relativa; ?>" class="btn btn-success" download>⬇ Excel</a>
    </div>
</div>

<div class="hoja-reporte">
    
    <div class="border-bottom border-3 border-dark pb-3 mb-4">
        <h4 class="fw-bold m-0 text-primary"><?php echo htmlspecialchars($titulo); ?></h4>
        <small class="text-muted">Fecha de Generación: <?php echo date("d/m/Y H:i", filemtime($ruta_completa)); ?></small>
    </div>

    <div class="card bg-light border-0 mb-4">
        <div class="card-body py-2">
            <div class="row">
                <div class="col-6 mb-1"><strong><?php echo $lineas[1][0]??''; ?></strong> <?php echo $lineas[1][1]??''; ?></div>
                <div class="col-6 mb-1"><strong><?php echo $lineas[1][2]??''; ?></strong> <?php echo $lineas[1][3]??''; ?></div>
                <div class="col-6 mb-1"><strong><?php echo $lineas[2][0]??''; ?></strong> <?php echo $lineas[2][1]??''; ?></div>
                <div class="col-6 mb-1"><strong><?php echo $lineas[2][2]??''; ?></strong> <?php echo $lineas[2][3]??''; ?></div>
            </div>
        </div>
    </div>

    <?php if ($es_auditoria): ?>
        
        <div class="table-responsive">
            <table class="table table-bordered table-sm table-custom">
                <thead>
                    <tr>
                        <th rowspan="2">No. Parte</th> <th rowspan="2">Cliente</th> <th rowspan="2">Orden</th> <th rowspan="2">Cant.</th>
                        <th colspan="2" class="bg-primary">Insp. Final</th>
                        <th colspan="4" class="bg-success">Pre-Empaque</th>
                        <th rowspan="2">Defectos</th>
                    </tr>
                    <tr>
                        <th>Insp</th> <th class="bg-danger">Rech</th>
                        <th class="bg-success">Insp</th> <th class="bg-success">Bolsa</th> <th class="bg-success">Etiq</th> <th class="bg-danger">Rech</th>
                    </tr>
                </thead>
                <tbody>
                    <?php 
                    for ($i = 5; $i < count($lineas); $i++) {
                        $r = $lineas[$i]; 
                        if (empty($r[0]) && empty($r[1])) continue; 
                        echo "<tr>
                            <td>{$r[0]}</td> <td>{$r[1]}</td> <td>{$r[2]}</td> <td>{$r[3]}</td> 
                            <td class='text-center'>".($r[5]??'')."</td> <td class='text-center text-danger fw-bold'>".($r[6]??'')."</td>
                            <td class='text-center'>".($r[8]??'')."</td> <td class='text-center'>".($r[9]??'')."</td> 
                            <td class='text-center'>".($r[10]??'')."</td> <td class='text-center text-danger fw-bold'>".($r[11]??'')."</td>
                            <td class='small'>".($r[13]??'')."</td>
                        </tr>";
                    } 
                    ?>
                </tbody>
            </table>
        </div>

    <?php elseif ($es_remachado): ?>

        <div class="mb-3 p-3 border rounded">
            <h6 class="fw-bold text-secondary">DATOS DEL PROCESO</h6>
            <div class="row">
                <div class="col-md-6"><strong>Ensamble:</strong> <?php echo $lineas[5][1] ?? '--'; ?></div>
                <div class="col-md-6"><strong>Aplicador:</strong> <?php echo $lineas[5][3] ?? '--'; ?></div>
                <div class="col-md-6"><strong>Terminal:</strong> <?php echo $lineas[6][1] ?? '--'; ?></div>
                <div class="col-md-6"><strong>Gage:</strong> <?php echo $lineas[6][3] ?? '--'; ?></div>
            </div>
        </div>

        <table class="table table-striped table-bordered table-custom text-center">
            <thead>
                <tr>
                    <th>HORA</th>
                    <th>VISUAL</th>
                    <th>PRUEBA JALÓN</th>
                    <th>ALTURA TERMINAL</th>
                </tr>
            </thead>
            <tbody>
                <?php 
                // En Remachado, los datos empiezan después de la cabecera "HORA" (aprox fila 10)
                $inicio_datos = false;
                foreach ($lineas as $r) {
                    // Detectar dónde empieza la tabla
                    if (($r[0] ?? '') == 'HORA') {
                        $inicio_datos = true;
                        continue;
                    }
                    if (!$inicio_datos) continue; // Saltar filas anteriores
                    if (strpos(($r[0]??''), 'Comentarios') !== false) break; // Fin tabla
                    if (empty($r[0])) continue;

                    echo "<tr>
                        <td class='fw-bold'>".($r[0]??'')."</td>
                        <td>".($r[1]??'')."</td>
                        <td>".($r[2]??'')."</td>
                        <td>".($r[3]??'')."</td>
                    </tr>";
                }
                ?>
            </tbody>
        </table>

    <?php else: ?>

        <table class="table table-striped table-bordered table-custom">
            <thead>
                <tr>
                    <th style="width: 60%;">Condición a Revisar</th> 
                    <th style="width: 15%;">Estatus</th> 
                    <th>Acción Correctiva</th>
                </tr>
            </thead>
            <tbody>
                <?php 
                for ($i = 5; $i < count($lineas); $i++) {
                    $r = $lineas[$i]; 
                    if (strpos(($r[0] ?? ''), 'Comentarios:') !== false) break;
                    if (empty($r[0])) continue;
                    
                    $st = "N/A"; $cl = "text-muted";
                    if (!empty($r[1])) { $st="CUMPLE"; $cl="text-success fw-bold"; }
                    if (!empty($r[2])) { $st="NO CUMPLE"; $cl="text-danger fw-bold"; }

                    echo "<tr>
                        <td>{$r[0]}</td> 
                        <td class='text-center $cl'>$st</td> 
                        <td>".($r[8]??'')."</td>
                    </tr>";
                }
                ?>
            </tbody>
        </table>

    <?php endif; ?>

    <div class="mt-4 p-3 border rounded bg-light">
        <strong>Comentarios Finales:</strong>
        <p class="mb-0 text-muted fst-italic">
            <?php 
            $comm = "";
            foreach($lineas as $l) {
                if(isset($l[0]) && strpos($l[0], 'Comentarios') !== false) {
                    $comm = $l[1];
                }
            }
            echo $comm ? $comm : "Sin comentarios adicionales.";
            ?>
        </p>
    </div>

</div>
</body>
</html>