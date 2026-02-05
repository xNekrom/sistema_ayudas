<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Panel de Calidad - Reportes</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">
    <style>
        body { background-color: #f8f9fa; }
        .folder-card { cursor: pointer; transition: transform 0.2s; }
        .folder-card:hover { transform: translateY(-5px); box-shadow: 0 4px 8px rgba(0,0,0,0.1); }
        .file-icon { font-size: 2rem; }
        .breadcrumb { background: white; padding: 10px; border-radius: 5px; }
    </style>
</head>
<body>

<nav class="navbar navbar-dark bg-primary mb-4">
  <div class="container-fluid">
    <span class="navbar-brand mb-0 h1"><i class="bi bi-shield-check"></i> Centro de Control de Calidad</span>
    <span class="text-white">Admin</span>
  </div>
</nav>

<div class="container">

    <?php
    // CONFIGURACIÓN
    $ruta_base = "../formatos/"; // Donde se guardan los archivos
    
    // Obtener la subcarpeta actual (seguridad básica para no salir del directorio)
    $sub_dir = isset($_GET['dir']) ? $_GET['dir'] : '';
    $sub_dir = str_replace('..', '', $sub_dir); // Evitar hackeo de rutas
    
    $ruta_actual = $ruta_base . $sub_dir;
    
    // Breadcrumbs (Navegación tipo: Inicio > Juan Perez > Checklist)
    echo '<nav aria-label="breadcrumb"><ol class="breadcrumb shadow-sm">';
    echo '<li class="breadcrumb-item"><a href="ver_reportes.php">Inicio</a></li>';
    
    if ($sub_dir != '') {
        $partes = explode('/', trim($sub_dir, '/'));
        $acumulado = '';
        foreach ($partes as $parte) {
            $acumulado .= $parte . '/';
            echo '<li class="breadcrumb-item"><a href="?dir=' . trim($acumulado, '/') . '">' . $parte . '</a></li>';
        }
    }
    echo '</ol></nav>';

    // Título dinámico
    if ($sub_dir == '') {
        echo '<h3 class="mb-4 text-secondary">📂 Inspectores</h3>';
    } else {
        echo '<h3 class="mb-4 text-secondary">📂 Contenido: ' . basename($sub_dir) . '</h3>';
    }
    ?>

    <div class="row g-3">
        <?php
        if (is_dir($ruta_actual)) {
            $contenido = scandir($ruta_actual);
            
            // Separar carpetas y archivos
            $carpetas = [];
            $archivos = [];

            foreach ($contenido as $item) {
                if ($item == '.' || $item == '..') continue;
                
                $ruta_item = $ruta_actual . '/' . $item;
                
                if (is_dir($ruta_item)) {
                    $carpetas[] = $item;
                } else {
                    $archivos[] = $item;
                }
            }

            // MOSTRAR CARPETAS (INSPECTORES O FORMATOS)
            foreach ($carpetas as $carpeta) {
                $link_dir = ($sub_dir == '') ? $carpeta : $sub_dir . '/' . $carpeta;
                ?>
                <div class="col-md-3 col-6">
                    <div class="card folder-card h-100 border-0 shadow-sm" onclick="window.location='?dir=<?php echo $link_dir; ?>'">
                        <div class="card-body text-center text-primary">
                            <i class="bi bi-folder-fill display-4"></i>
                            <h5 class="card-title mt-2 text-dark" style="font-size: 1rem;"><?php echo $carpeta; ?></h5>
                        </div>
                    </div>
                </div>
                <?php
            }

            // MOSTRAR ARCHIVOS (LOS CSV / EXCEL)
            if (!empty($archivos)) {
                echo '<div class="col-12 mt-4"><h5 class="border-bottom pb-2">📄 Reportes Generados</h5></div>';
                ?>
                <div class="col-12">
                    <div class="card border-0 shadow-sm">
                        <div class="card-body p-0">
                            <table class="table table-hover mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th>Nombre del Archivo</th>
                                        <th>Fecha Creación</th>
                                        <th>Tamaño</th>
                                        <th>Acción</th>
                                    </tr>
                                </thead>
                                <tbody>
                                <?php foreach ($archivos as $archivo) { 
                                    $ruta_completa = $ruta_actual . '/' . $archivo;
                                    $fecha = date("d/m/Y H:i", filemtime($ruta_completa));
                                    $size = round(filesize($ruta_completa) / 1024, 2) . " KB";
                                ?>
                                    <tr>
                                        <td>
                                            <i class="bi bi-file-earmark-spreadsheet text-success"></i> 
                                            <strong><?php echo $archivo; ?></strong>
                                        </td>
                                        <td><?php echo $fecha; ?></td>
                                        <td><?php echo $size; ?></td>
                                        <td>
                                            <a href="<?php echo $ruta_completa; ?>" class="btn btn-sm btn-success" download>
                                                <i class="bi bi-download"></i> Descargar Excel
                                            </a>
                                        </td>
                                    </tr>
                                <?php } ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
                <?php
            }
            
            if (empty($carpetas) && empty($archivos)) {
                echo '<div class="alert alert-warning">Esta carpeta está vacía.</div>';
            }

        } else {
            echo '<div class="alert alert-danger">La ruta de formatos no existe. Genera un reporte primero.</div>';
        }
        ?>
    </div>

</div>

</body>
</html>