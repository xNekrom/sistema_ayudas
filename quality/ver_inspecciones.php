<?php
// --- LÓGICA DE LA PÁGINA ---
session_start();
// Seguridad: Solo Admin y Calidad pueden entrar
if (!isset($_SESSION['usuario_id']) || !in_array($_SESSION['rol'], ['Admin', 'Calidad'])) {
    header("Location: ../index.php");
    exit();
}

// --- RUTA CLAVE ---
// Esto apunta a C:\xampp\htdocs\formatos desde quality/
$ruta_base = realpath(__DIR__ . '/../../formatos/'); 

// Navegación de subcarpetas (seguridad para no salir de formatos)
$sub_dir = isset($_GET['dir']) ? $_GET['dir'] : '';
// Limpiar la entrada del usuario para prevenir ataques de path traversal
$sub_dir = str_replace(['..', './'], '', $sub_dir); 
$ruta_actual_fs = $ruta_base . DIRECTORY_SEPARATOR . $sub_dir;

// Asegurarse de que la ruta actual exista y esté dentro de la ruta base
if (!is_dir($ruta_actual_fs) || strpos($ruta_actual_fs, $ruta_base) === FALSE) {
    $ruta_actual_fs = $ruta_base;
    $sub_dir = '';
}

$contenido = [];
if (is_dir($ruta_actual_fs)) {
    $scan_content = scandir($ruta_actual_fs);
    if ($scan_content !== false) {
        foreach ($scan_content as $item) {
            if ($item == '.' || $item == '..') continue;
            $ruta_item_fs = $ruta_actual_fs . DIRECTORY_SEPARATOR . $item;
            $contenido[] = [
                'name' => $item,
                'is_dir' => is_dir($ruta_item_fs),
                'path' => $sub_dir . ($sub_dir ? '/' : '') . $item, // Ruta relativa para el enlace GET
                'mtime' => filemtime($ruta_item_fs),
                'size' => is_file($ruta_item_fs) ? filesize($ruta_item_fs) : null
            ];
        }
        // Ordenar carpetas primero, luego archivos, y alfabéticamente
        usort($contenido, function($a, $b) {
            if ($a['is_dir'] == $b['is_dir']) {
                return strcmp($a['name'], $b['name']);
            }
            return ($a['is_dir'] > $b['is_dir']) ? -1 : 1;
        });
    }
} else {
    // Si no es un directorio válido, forzamos a la ruta base
    $ruta_actual_fs = $ruta_base;
    $sub_dir = '';
    $_SESSION['flash_msg'] = "❌ La ruta solicitada no es válida o no existe.";
    $_SESSION['flash_type'] = "danger";
}
// --- FIN DE LÓGICA ---

require_once __DIR__ . '/../templates/header.php';
?>

<h1 class="mb-4">📋 Registros de Inspección</h1>

<!-- Barra de Navegación (Breadcrumb) -->
<nav aria-label="breadcrumb">
    <ol class="breadcrumb bg-grey p-2 rounded shadow-sm border">
        <li class="breadcrumb-item"><a href="ver_inspecciones.php"><i class="fas fa-folder"></i> Inicio (Formatos)</a></li>
        <?php
        if ($sub_dir != '') {
            $partes = explode('/', trim($sub_dir, '/'));
            $acumulado = '';
            foreach ($partes as $parte) {
                $acumulado .= $parte . '/';
                echo '<li class="breadcrumb-item"><a href="?dir=' . urlencode(trim($acumulado, '/')) . '">' . htmlspecialchars($parte) . '</a></li>';
            }
        }
        ?>
    </ol>
</nav>

<div class="row g-3">
    <?php
    if (!empty($contenido)) {
        foreach ($contenido as $item) {
            if ($item['is_dir']) {
                // CARPETAS (Inspectores o Tipos de Formato)
                echo '
                <div class="col-md-3 col-6">
                    <div class="card folder-card h-100 text-center p-3 shadow-sm" onclick="location.href=\'?dir=' . urlencode($item['path']) . '\'">
                        <i class="fas fa-folder text-warning display-4"></i>
                        <h6 class="mt-3 text-dark fw-bold">' . htmlspecialchars($item['name']) . '</h6>
                    </div>
                </div>';
            } else {
                // ARCHIVOS (Los reportes .csv)
                $ext = pathinfo($item['name'], PATHINFO_EXTENSION);
                if ($ext == 'csv') {
                    $fecha = date("d/m/Y H:i", $item['mtime']);
                    $size = round($item['size'] / 1024, 1) . " KB";
                    
                    echo '
                    <div class="col-12">
                        <div class="card mb-2 shadow-sm border-start border-4 border-primary">
                            <div class="card-body d-flex justify-content-between align-items-center py-2">
                                <div>
                                    <i class="fas fa-file-csv text-primary fs-4 me-2"></i>
                                    <strong>' . htmlspecialchars($item['name']) . '</strong>
                                    <span class="text-muted ms-2 small">(' . $fecha . ' - ' . $size . ')</span>
                                </div>
                                <div>
                                    <a href="detalle_inspeccion.php?archivo=' . urlencode($item['path']) . '" class="btn btn-sm btn-info me-1" title="Ver detalle">
                                        <i class="fas fa-eye"></i> Ver Detalle
                                    </a>
                                    <a href="/formatos/' . urlencode($item['path']) . '" class="btn btn-sm btn-success" download="' . htmlspecialchars($item['name']) . '" title="Descargar Excel">
                                        <i class="fas fa-download"></i> Excel
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>';
                }
            }
        }
    } else {
        echo '<div class="alert alert-info w-100">📂 Carpeta vacía o inaccesible.</div>';
    }
    ?>
</div>

<style>
    /* Estilos específicos para la vista de inspecciones */
    .folder-card { 
        cursor: pointer; 
        transition: transform 0.2s ease-in-out, box-shadow 0.2s ease-in-out; 
    }
    .folder-card:hover { 
        transform: translateY(-5px); 
        box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.15) !important; 
    }
</style>

<?php require_once __DIR__ . '/../templates/footer.php'; ?>