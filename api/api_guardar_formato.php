<?php
// api_guardar_formato.php - CON SOBRESCRITURA DE ARCHIVO
error_reporting(0);
ini_set('display_errors', 0);

header('Content-Type: application/json; charset=utf-8');
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST, OPTIONS");

try {
    // 1. CONEXIÓN
    if (file_exists('../config/db.php')) include '../config/db.php';
    elseif (file_exists('../db.php')) include '../db.php';
    else 
        $conn = null;

    // 2. RECIBIR DATOS
    $input = file_get_contents('php://input');
    $data = json_decode($input, true);

    if (!$data) throw new Exception("Datos vacíos");

    // Variables Comunes
    $nombre_auditor = $data['nombre_auditor'] ?? 'Desconocido';
    $nombre_formato = $data['nombre_formato'] ?? 'General';
    $unidad = $data['unidad'] ?? ''; 
    $fecha_reporte = $data['fecha'] ?? date("Y-m-d"); 
    
    // --- LÓGICA DE NOMBRE DE ARCHIVO ---
    
    // Por defecto, creamos nombre nuevo con la hora actual
    $fecha_para_nombre = date("Y-m-d_H-i-s");
    $es_edicion = false;

    if ($conn && isset($data['id_reporte']) && !empty($data['id_reporte'])) {
        $id = intval($data['id_reporte']);
        // Buscamos la fecha original en la BD para saber qué archivo sobrescribir
        $res = $conn->query("SELECT fecha_creacion FROM reportes_calidad WHERE id = $id");
        if ($res && $row = $res->fetch_assoc()) {
            // Convertimos la fecha de SQL (2023-10-25 15:30:00) al formato de archivo (2023-10-25_15-30-00)
            $fecha_sql = $row['fecha_creacion'];
            $fecha_para_nombre = str_replace([' ', ':'], ['_', '-'], $fecha_sql);
            $es_edicion = true;
        }
    }

    // Limpiar y definir rutas
    function limpiar($s) { return preg_replace('/[^A-Za-z0-9\_]/', '', str_replace(' ', '_', $s)); }
    $f_inspector = limpiar($nombre_auditor);
    $f_formato = limpiar($nombre_formato);

    $ruta_base = "../../formatos/"; 
    $ruta_carpeta = $ruta_base . $f_inspector . "/" . $f_formato . "/";

    if (!file_exists($ruta_carpeta)) {
        @mkdir($ruta_carpeta, 0777, true);
    }

    // El nombre del archivo se basa en la fecha original si es edición (sobrescribe), o actual si es nuevo
    $nombre_archivo = $fecha_para_nombre . ".csv";
    $ruta_completa = $ruta_carpeta . $nombre_archivo;

    // ---------------------------------------------------------
    // PASO A: GENERACIÓN DEL CSV (Igual que antes)
    // ---------------------------------------------------------
    
    $fp = @fopen($ruta_completa, 'w'); // 'w' trunca el archivo a 0 y sobrescribe
    
    if ($fp) {
        @fputs($fp, "\xEF\xBB\xBF"); // BOM

        // LÓGICA DE FORMATOS
        if ($nombre_formato == "Checklist_Arranque") {
            fputcsv($fp, ["REPORTE: CHECKLIST ARRANQUE (QC-283) Y 3 PIEZAS (QC-281)"]);
            fputcsv($fp, ["Fecha:", $fecha_reporte, "Unidad:", $unidad]);
            fputcsv($fp, ["Inspector:", $nombre_auditor, "Coordinador:", $data['coordinador'] ?? '']);
            fputcsv($fp, [""]); 
            fputcsv($fp, ["--- VERIFICACION DE ARRANQUE ---"]);
            fputcsv($fp, ["CONDICION", "SI", "NO", "N/A", "ACCION CORRECTIVA"]);
            foreach (($data['inspeccion'] ?? []) as $row) {
                fputcsv($fp, [$row['condicion'] ?? '', ($row['estado']=='SI')?'X':'', ($row['estado']=='NO')?'X':'', ($row['estado']=='NA')?'X':'', $row['accion'] ?? '']);
            }
            fputcsv($fp, [""]);
            fputcsv($fp, ["Comentarios:", $data['comentarios'] ?? '']);

        } elseif ($nombre_formato == "Auditoria_Final") {
            fputcsv($fp, ["REPORTE: AUDITORIA FINAL (QC-208)"]);
            fputcsv($fp, ["Fecha:", $fecha_reporte, "Unidad:", $unidad, "Sup:", $data['coordinador'] ?? '']);
            fputcsv($fp, ["PARTE", "CLIENTE", "ORDEN", "CANT", "|", "F.INSP", "F.RECH", "|", "P.INSP", "P.BOLSA", "P.ETIQ", "P.RECH", "COMENTARIOS"]);
            foreach (($data['inspeccion'] ?? []) as $row) {
                fputcsv($fp, [$row['parte'], $row['cliente'], $row['orden'], $row['cant_orden'], "|", $row['fin_insp'], $row['fin_rech'], "|", $row['pre_insp'], $row['pre_bolsa'], $row['pre_etiqueta'], $row['pre_rech'], $row['defectos']]);
            }

        } elseif ($nombre_formato == "Inspeccion_Remachado") {
            fputcsv($fp, ["REPORTE REMACHADO (QC-206)"]);
            $ex = $data['datos_extra'] ?? [];
            fputcsv($fp, ["Fecha:", $fecha_reporte, "Op:", $ex['operador']??"", "Lib:", $data['coordinador']??""]);
            fputcsv($fp, ["Ens:", $ex['ensamble'], "Apl:", $ex['aplicador'], "Ter:", $ex['terminal'], "Gage:", $ex['gage']]);
            fputcsv($fp, ["HORA", "VISUAL", "JALON", "ALTURA"]);
            foreach (($data['inspeccion'] ?? []) as $row) {
                fputcsv($fp, [$row['hora'], $row['visual'], $row['jalon'], $row['altura']]);
            }
            fputcsv($fp, ["Comentarios:", $data['comentarios'] ?? '']);

        } elseif ($nombre_formato == "Inspeccion_Corte") {
            fputcsv($fp, ["INSPECCION CORTE (QC-205)"]);
            fputcsv($fp, ["Fecha:", $fecha_reporte, "Mq:", $unidad, "Op:", $data['operador']??""]);
            fputcsv($fp, ["ORDEN", "CANT", "DESC", "LONG", "D2", "D3", "INSP", "DEFECTO", "C.INSP"]);
            foreach (($data['inspeccion'] ?? []) as $row) {
                fputcsv($fp, [$row['orden'], $row['cant_orden'], $row['descripcion'], $row['longitud'], $row['desp2'], $row['desp3'], $row['inspector'], $row['defecto'], $row['cant_insp']]);
            }

        } elseif ($nombre_formato == "Auditoria_Proceso") {
            fputcsv($fp, ["AUDITORIA PROCESO (QC-207)"]);
            fputcsv($fp, ["Fecha:", $fecha_reporte, "Area:", $unidad, "Sup:", $data['coordinador']??""]);
            fputcsv($fp, ["PARTE", "CLIENTE", "ORDEN", "MISMA", "OP", "C.ORD", "C.INSP", "C.RECH", "DEFECTOS"]);
            foreach (($data['inspeccion'] ?? []) as $row) {
                fputcsv($fp, [$row['parte'], $row['cliente'], $row['orden'], $row['misma']?'SI':'NO', $row['operacion'], $row['cant_orden'], $row['cant_insp'], $row['cant_rech'], $row['defectos']]);
            }

        } elseif ($nombre_formato == "Auditoria_Empaque") {
            fputcsv($fp, ["AUDITORIA EMPAQUE (QC-220)"]);
            fputcsv($fp, ["Fecha:", $fecha_reporte, "Auditor:", $nombre_auditor]);
            fputcsv($fp, ["PARTE", "REV", "CANT", "PESO", "ETIQ", "DEST", "DISP", "COFC", "ROHS", "REACH", "COMENTARIOS"]);
            foreach (($data['inspeccion'] ?? []) as $row) {
                fputcsv($fp, [$row['parte'], $row['rev'], $row['cantidad'], $row['peso'], $row['etiqueta'], $row['destino'], $row['disposicion'], $row['cofc']?'OK':'NO', $row['rohs']?'OK':'NO', $row['reach']?'OK':'NO', $row['comentarios']]);
            }

        } elseif ($nombre_formato == "Lecturas_Dimensiones") {
            fputcsv($fp, ["DIMENSIONES (QC-242)"]);
            fputcsv($fp, ["Parte:", $data['parte']??"", "Cliente:", $data['cliente']??"", "Orden:", $data['orden']??""]);
            fputcsv($fp, ["ID", "NOMINAL", "MAX/MIN", "L1", "L2", "L3"]);
            foreach (($data['inspeccion'] ?? []) as $row) {
                fputcsv($fp, [$row['id'], $row['nominal'], $row['max_min'], $row['l1'], $row['l2'], $row['l3']]);
            }

        } elseif ($nombre_formato == "Certificacion_Fixtures") {
            fputcsv($fp, ["FIXTURES (QC-263)"]);
            fputcsv($fp, ["PARTE", "REV", "CANT", "CERT POR", "FECHA", "TIPO", "ESTADO", "HALLAZGOS"]);
            foreach (($data['inspeccion'] ?? []) as $row) {
                fputcsv($fp, [$row['parte'], $row['rev'], $row['cant'], $row['cert_por'], $row['fecha'], $row['tipo'], $row['estado'], $row['hallazgos']]);
            }

        } elseif ($nombre_formato == "Registro_Temperatura") {
            fputcsv($fp, ["TEMPERATURA (QC-280)"]);
            fputcsv($fp, ["Fecha:", $fecha_reporte, "Inspector:", $nombre_auditor]);
            fputcsv($fp, ["CAUTIN", "T.REQ", "T.ACT", "COMENTARIOS"]);
            foreach (($data['inspeccion'] ?? []) as $row) {
                fputcsv($fp, [$row['cautin'], $row['req'], $row['act'], $row['comentarios']]);
            }

        } elseif ($nombre_formato == "Prueba_Electrica") {
            fputcsv($fp, ["PRUEBA ELECTRICA (QC-286)"]);
            fputcsv($fp, ["Fecha:", $fecha_reporte, "Unidad:", $unidad]);
            fputcsv($fp, ["ORDEN", "PARTE", "ACEPT", "RECH", "ETIQ", "DYNALAB", "FIX", "PROG", "PROD", "CAL", "COMENTARIOS"]);
            foreach (($data['inspeccion'] ?? []) as $row) {
                fputcsv($fp, [$row['orden'], $row['parte'], $row['acept'], $row['rech'], $row['etiqueta'], $row['dynalab'], $row['fixture'], $row['programa'], $row['prod'], $row['cal'], $row['comentarios']]);
            }
        }
        @fclose($fp);
    }
    
    // --- NUEVO: INSERTAR EN RESUMEN_CALIDAD PARA EL DASHBOARD ---
    if ($conn) {
    // Determinamos si es APROBADO o RECHAZADO basado en la lógica de tu negocio
    // Aquí un ejemplo: si hay alguna cantidad en 'pre_rech' o 'cant_rech', es RECHAZADO
    $resultado_final = "APROBADO";
    foreach (($data['inspeccion'] ?? []) as $item) {
        if (isset($item['pre_rech']) && $item['pre_rech'] > 0) { $resultado_final = "RECHAZADO"; break; }
        if (isset($item['cant_rech']) && $item['cant_rech'] > 0) { $resultado_final = "RECHAZADO"; break; }
        if (isset($item['estado']) && $item['estado'] == "NO") { $resultado_final = "RECHAZADO"; break; }
    }

    $f_inspector_bd = $conn->real_escape_string($nombre_auditor);
    $f_formato_bd = $conn->real_escape_string($nombre_formato);
    $f_archivo_bd = $conn->real_escape_string($nombre_archivo);

    // Insertamos los datos simplificados que el Dashboard necesita
    $sql_dashboard = "INSERT INTO resumen_calidad (fecha, inspector, formato, resultado, archivo_csv) 
                      VALUES (NOW(), '$f_inspector_bd', '$f_formato_bd', '$resultado_final', '$f_archivo_bd')";
    
    $conn->query($sql_dashboard);
}

    // ---------------------------------------------------------
    // PASO B: GUARDAR EN BASE DE DATOS
    // ---------------------------------------------------------
    if ($conn) {
        $datos_json = $conn->real_escape_string($input); 
        $usuario_id = isset($data['usuario_id']) ? intval($data['usuario_id']) : 0;
        $titulo_safe = $conn->real_escape_string($nombre_formato);
        
        // Si NO es edición, usamos la fecha nueva. Si ES edición, MANTENEMOS la fecha original
        // para no romper el vínculo con el nombre del archivo.
        $fecha_bd = $es_edicion ? str_replace(['_', '-'], [' ', ':'], $fecha_para_nombre) : date("Y-m-d H:i:s");

        $sql = "";
        
        if (isset($data['id_reporte']) && !empty($data['id_reporte'])) {
            $id = intval($data['id_reporte']);
            // Update: actualizamos solo el JSON, NO la fecha de creación
            $sql = "UPDATE reportes_calidad SET datos_json = '$datos_json' WHERE id = $id";
        } else {
            // Insert
            $sql = "INSERT INTO reportes_calidad (usuario_id, titulo_de_reporte, datos_json, fecha_creacion) 
                    VALUES ($usuario_id, '$titulo_safe', '$datos_json', '$fecha_bd')"; 
        }

        if (!$conn->query($sql)) {
             file_put_contents("error_sql.log", date("Y-m-d H:i:s") . " - " . $conn->error . "\n", FILE_APPEND);
        }
        $conn->close();
    }
    

    // 4. RESPUESTA FINAL
    echo json_encode([
        "success" => true, 
        "message" => $es_edicion ? "Archivo Sobrescrito" : "Archivo Creado"
    ]);

} catch (Exception $e) {
    echo json_encode(["success" => false, "message" => $e->getMessage()]);
}
?>