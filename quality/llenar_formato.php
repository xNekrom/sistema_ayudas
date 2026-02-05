<?php
ob_start();
require '../vendor/autoload.php'; 
use PhpOffice\PhpSpreadsheet\IOFactory;

include '../config/db.php';

$archivo_buscado = $_GET['archivo'] ?? '';
$ruta_base_formatos = 'C:/xampp/htdocs/formatos/';
$ruta_plantilla = 'C:/xampp/htdocs/sistema_ayudas/plantilla_excel/QC.- 283 checklist de arranque.xlsx';

if (empty($archivo_buscado)) die("Error: No se especificó el archivo CSV.");

function buscarArchivo($dir, $nombre) {
    if (!is_dir($dir)) return false;
    $iterator = new RecursiveDirectoryIterator($dir);
    foreach (new RecursiveIteratorIterator($iterator) as $file) {
        if ($file->getFilename() === $nombre) return $file->getPathname();
    }
    return false;
}

$ruta_final_csv = buscarArchivo($ruta_base_formatos, $archivo_buscado);
if (!$ruta_final_csv) die("Error: No se encontró el CSV.");

$lineas = array_map('str_getcsv', file($ruta_final_csv));

try {
    $spreadsheet = IOFactory::load($ruta_plantilla);
    $sheet = $spreadsheet->getActiveSheet();

    // --- 1. DICCIONARIO DE REEMPLAZOS ---
    // Según tu archivo 2026-01-14_20-37-01.csv
    $reemplazos = [
        '[Fecha]'       => (string)($lineas[1][1] ?? ''),
        '[Unidad]'      => (string)($lineas[1][3] ?? ''),
        '[Inspector]'   => (string)($lineas[2][1] ?? ''),
        '[Coordinador]' => (string)($lineas[2][3] ?? ''),
        '[Comentarios]' => (string)($lineas[15][1] ?? '')
    ];

    // --- 2. MAPEO DE PUNTOS (Punto 1 al 8) ---
    // En el CSV las filas de puntos son de la 7 a la 14 (índices 6 a 13)
    for ($i = 6; $i <= 13; $i++) {
        $n = $i - 5; // Esto genera 1, 2, 3...
        
        // Texto de la pregunta (si usas la etiqueta [Punto X])
        $reemplazos["[Punto $n]"] = (string)($lineas[$i][0] ?? ''); 
        
        // Lógica de marcas X: Columna B=SI(1), C=NO(2), D=NA(3) en el CSV
        $reemplazos["[Punto {$n}_SI]"] = (strtoupper(trim($lineas[$i][1] ?? '')) === 'X') ? 'X' : '';
        $reemplazos["[Punto {$n}_NO]"] = (strtoupper(trim($lineas[$i][2] ?? '')) === 'X') ? 'X' : '';
        $reemplazos["[Punto {$n}_NA]"] = (strtoupper(trim($lineas[$i][3] ?? '')) === 'X') ? 'X' : '';
        
        // Acción correctiva: Columna E (índice 4) en el CSV
        $reemplazos["[Accion $n]"] = (string)($lineas[$i][4] ?? '');
    }

    // --- 3. REEMPLAZO DINÁMICO EN EL EXCEL ---
    foreach ($sheet->getRowIterator() as $row) {
        $cellIterator = $row->getCellIterator();
        $cellIterator->setIterateOnlyExistingCells(true);
        foreach ($cellIterator as $cell) {
            $valorCelda = (string)$cell->getValue(); 
            
            // Si la celda contiene exactamente una de nuestras etiquetas
            if ($valorCelda !== '' && isset($reemplazos[$valorCelda])) {
                $cell->setValue($reemplazos[$valorCelda]);
            }
        }
    }

    // --- 4. DESCARGA ---
    ob_end_clean();
    header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
    header('Content-Disposition: attachment;filename="Reporte_QC283_Correcto.xlsx"');
    $writer = IOFactory::createWriter($spreadsheet, 'Xlsx');
    $writer->save('php://output');
    exit;

} catch (Exception $e) {
    die("Error crítico: " . $e->getMessage());
}