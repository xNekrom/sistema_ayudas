<?php
ob_start(); // Previene errores de salida de datos previos
require '../vendor/autoload.php';
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Border;

include '../config/db.php';

// 1. Obtención y validación del archivo CSV
$archivo_csv = $_GET['archivo'] ?? die("Error: Archivo no especificado.");
$ruta_csv = realpath(__DIR__ . "/../../plantilla_excel/" . $archivo_csv);

if (!$ruta_csv || !file_exists($ruta_csv)) {
    die("Error: El archivo de datos no existe en la ruta especificada.");
}

$lineas = array_map('str_getcsv', file($ruta_csv));
$titulo = $lineas[0][0] ?? "REPORTE DE CALIDAD";

// 2. Selección de plantilla
$plantilla = "";
if (strpos($titulo, 'QC-205') !== false) $plantilla = "QC. -205 insp de corte.xlsx";
elseif (strpos($titulo, 'QC-206') !== false) $plantilla = "QC. -206 insp de remache.xlsx";
elseif (strpos($titulo, 'QC-207') !== false) $plantilla = "QC. -207  proceso y monitoreo.xlsx";
elseif (strpos($titulo, 'QC-208') !== false) $plantilla = "QC. -208 Insp final y preempaque.xlsx";
elseif (strpos($titulo, 'QC-220') !== false) $plantilla = "QC. -220  auditoria de empaque.xlsx";
elseif (strpos($titulo, 'QC-226') !== false) $plantilla = "QC. -226  DMR.xlsx";
elseif (strpos($titulo, 'QC-242') !== false) $plantilla = "QC. -242 Lecturas de Dimensiones.xlsx";
elseif (strpos($titulo, 'QC-263') !== false) $plantilla = "QC.- 263 Certificacion de fixture.xlsx";
elseif (strpos($titulo, 'QC-280') !== false) $plantilla = "QC.- 280 Temp de cautin.xlsx";
elseif (strpos($titulo, 'QC-281') !== false) $plantilla = "QC.- 281  Ver. 03 (3 primeras piezas).xlsx";
elseif (strpos($titulo, 'QC-283') !== false) $plantilla = "QC.- 283 checklist de arranque.xlsx";
elseif (strpos($titulo, 'QC-286') !== false) $plantilla = "QC.- 286 Version 3 (prueba electrica).xlsx";

$ruta_plantilla = __DIR__ . "/plantillas/" . $plantilla;

// 3. Lógica de creación: Cargar o Generar desde cero
if (!empty($plantilla) && file_exists($ruta_plantilla)) {
    $spreadsheet = IOFactory::load($ruta_plantilla);
    $sheet = $spreadsheet->getActiveSheet();
    
    // Mapeo manual para plantillas existentes (Celdas sin guiones)
    if (isset($lineas[1])) $sheet->setCellValue('B5', $lineas[1][1]); 
    if (isset($lineas[2])) $sheet->setCellValue('H5', $lineas[2][1]); 
    $fila_inicio_datos = 10;
} else {
    $spreadsheet = new Spreadsheet();
    $sheet = $spreadsheet->getActiveSheet();
    $sheet->setTitle('Auditoría');

    // Diseño del formato desde cero
    $sheet->mergeCells('A1:H2');
    $sheet->setCellValue('A1', $titulo);
    $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(14);
    $sheet->getStyle('A1')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

    $sheet->setCellValue('A4', 'FECHA:');
    $sheet->setCellValue('B4', $lineas[1][1] ?? 'N/A');
    $sheet->setCellValue('G4', 'INSPECTOR:');
    $sheet->setCellValue('H4', $lineas[2][1] ?? 'N/A');
    $sheet->getStyle('A4:G4')->getFont()->setBold(true);

    $encabezados = ['No. PARTE', 'CLIENTE', 'REVISIÓN', 'CANT. ORDEN', 'CANT. INSP', 'RECHAZO', 'DEFECTO', 'COMENTARIOS'];
    foreach (range('A', 'H') as $i => $col) {
        $sheet->setCellValue($col . '6', $encabezados[$i]);
        $sheet->getColumnDimension($col)->setAutoSize(true);
    }
    $sheet->getStyle('A6:H6')->applyFromArray([
        'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
        'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '444444']]
    ]);
    $fila_inicio_datos = 7;
}

// 4. Llenado de datos
$fila_actual = $fila_inicio_datos;
for ($i = 5; $i < count($lineas); $i++) {
    if (!empty($lineas[$i][0])) {
        $sheet->fromArray([$lineas[$i]], null, 'A' . $fila_actual);
        $sheet->getStyle('A'.$fila_actual.':H'.$fila_actual)->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN);
        $fila_actual++;
    }
}

// 5. Descarga
if (ob_get_length()) ob_end_clean();
header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
header('Content-Disposition: attachment;filename="Reporte_Final_' . date('Ymd_His') . '.xlsx"');
header('Cache-Control: max-age=0');

$writer = new Xlsx($spreadsheet);
$writer->save('php://output');
exit();