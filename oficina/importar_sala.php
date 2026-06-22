<?php
/**
 * importar_sala.php
 * Lee el XLS del mes actual de la carpeta compartida de sala
 * e importa SOLO los registros del día operativo actual.
 * Ejecutar via Task Scheduler cada 5 minutos.
 */

$conex = mysqli_connect('localhost', 'root', '', 'maquinas');
if (!$conex) die('Error BD: ' . mysqli_connect_error());
mysqli_set_charset($conex, 'utf8mb4');

$carpeta = 'C:\\Users\\tecni\\OneDrive\\Escritorio\\PAGOS DE MES\\';
$log     = __DIR__ . '/importar_sala_log.txt';

function escribirLog($msg) {
    global $log;
    file_put_contents($log, date('[d/m/Y H:i:s] ') . $msg . "\n", FILE_APPEND);
}

/**
 * ⚠️ REGLA DE DÍA OPERATIVO — debe ser IDÉNTICA en cerrar_dia.php (sala)
 * e importar_sala.php (oficina). Si modificas esta función, modifícala
 * en AMBOS archivos o vuelve a aparecer el bug de desincronización.
 *
 * Viernes y sábado: el casino cierra a las 4am.
 * Resto de los días (domingo a jueves): cierra a las 2am.
 * Las horas antes del corte cuentan como parte del día anterior.
 */
function fechaOperativa($timestamp = null) {
    $timestamp = $timestamp ?? time();
    $hora = (int) date('G', $timestamp);
    $diaSemanaAyer = (int) date('N', strtotime('-1 day', $timestamp)); // 1=Lun...5=Vie,6=Sáb,7=Dom
    $cutoff = in_array($diaSemanaAyer, [5, 6]) ? 4 : 2;
    if ($hora < $cutoff) {
        return date('Y-m-d', strtotime('-1 day', $timestamp));
    }
    return date('Y-m-d', $timestamp);
}

$fechaOperativa = fechaOperativa();
$diaNum = (int) date('j', strtotime($fechaOperativa));

// Solo buscar el archivo del mes actual
$meses = ['','ENERO','FEBRERO','MARZO','ABRIL','MAYO','JUNIO',
          'JULIO','AGOSTO','SEPTIEMBRE','OCTUBRE','NOVIEMBRE','DICIEMBRE'];
$mes_num = (int) date('n', strtotime($fechaOperativa));
$anio    = (int) date('Y', strtotime($fechaOperativa));
$nombre_archivo = "PAGOS-{$meses[$mes_num]}-{$anio}.xls";
$ruta_archivo   = $carpeta . $nombre_archivo;

if (!file_exists($ruta_archivo)) {
    escribirLog("Archivo no encontrado: $nombre_archivo");
    exit;
}

$contenido = @file_get_contents($ruta_archivo);
if (!$contenido) {
    escribirLog("No se pudo leer: $nombre_archivo");
    exit;
}

// Buscar solo la hoja del día operativo
preg_match('/<Worksheet ss:Name="Día ' . $diaNum . '">(.*?)<\/Worksheet>/s', $contenido, $hoja_match);

if (empty($hoja_match)) {
    escribirLog("Sin hoja para el día $diaNum en $nombre_archivo");
    exit;
}

$tabla_xml = $hoja_match[1];
preg_match_all('/<Row>(.*?)<\/Row>/s', $tabla_xml, $filas_match);
$filas = $filas_match[1];

if (empty($filas)) {
    escribirLog("Hoja vacía para día $diaNum");
    exit;
}

// Primera fila = encabezados
preg_match_all('/<Data[^>]*>(.*?)<\/Data>/s', $filas[0], $enc_match);
$encabezados = array_map('strip_tags', $enc_match[1]);
$cols = array_flip($encabezados);

$total_importados = 0;

for ($j = 1; $j < count($filas); $j++) {
    preg_match_all('/<Data[^>]*>(.*?)<\/Data>/s', $filas[$j], $cel_match);
    $celdas = array_map('strip_tags', $cel_match[1]);

    if (empty(array_filter($celdas))) continue;

    $primera = strtoupper(trim($celdas[0] ?? ''));
    if (str_contains($primera, 'TOTAL')) continue;

    $pago_num  = trim($celdas[$cols['PAGO']      ?? -1] ?? '');
    $fecha_raw = trim($celdas[$cols['FECHA']     ?? -1] ?? '');
    $terminal  = trim($celdas[$cols['TERMINAL']  ?? -1] ?? '');
    $proveedor = trim($celdas[$cols['PROVEEDOR'] ?? -1] ?? '');
    $cliente   = trim($celdas[$cols['CLIENTE']   ?? -1] ?? '');
    $tarjeta   = trim($celdas[$cols['ID']        ?? -1] ?? '');
    $nivel     = trim($celdas[$cols['NIVEL']     ?? -1] ?? '');
    $cantidad  = trim($celdas[$cols['CANTIDAD']  ?? -1] ?? '');
    $apuesta   = trim($celdas[$cols['APUESTA']   ?? -1] ?? '');
    $gerente   = trim($celdas[$cols['GERENTE']   ?? -1] ?? '');
    $tecnico   = trim($celdas[$cols['TÉCNICO']   ?? -1] ?? $celdas[$cols['TECNICO'] ?? -1] ?? '');

    if (empty($pago_num) || empty($fecha_raw)) continue;

    // Convertir fecha dd/mm/yyyy → yyyy-mm-dd
    $fecha_parts = explode('/', $fecha_raw);
    if (count($fecha_parts) === 3) {
        $fecha = $fecha_parts[2] . '-' . $fecha_parts[1] . '-' . $fecha_parts[0];
    } else {
        continue;
    }

    // Solo importar si coincide con la fecha operativa
    if ($fecha !== $fechaOperativa) continue;

    // Verificar duplicado
    $nombre_esc = mysqli_real_escape_string($conex, $nombre_archivo);
    $dia_esc    = mysqli_real_escape_string($conex, (string)$diaNum);
    $pago_esc   = mysqli_real_escape_string($conex, $pago_num);

    $check = mysqli_query($conex,
        "SELECT id FROM pagos_sala_log
         WHERE archivo='$nombre_esc' AND dia='$dia_esc' AND pago_num='$pago_esc'"
    );
    if (mysqli_num_rows($check) > 0) continue;

    // Insertar
    $fecha_esc     = mysqli_real_escape_string($conex, $fecha);
    $terminal_esc  = mysqli_real_escape_string($conex, $terminal);
    $proveedor_esc = mysqli_real_escape_string($conex, $proveedor);
    $cliente_esc   = mysqli_real_escape_string($conex, $cliente);
    $tarjeta_esc   = mysqli_real_escape_string($conex, $tarjeta);
    $nivel_esc     = mysqli_real_escape_string($conex, $nivel);
    $cantidad_esc  = mysqli_real_escape_string($conex, $cantidad);
    $apuesta_esc   = mysqli_real_escape_string($conex, $apuesta);
    $gerente_esc   = mysqli_real_escape_string($conex, $gerente);
    $tecnico_esc   = mysqli_real_escape_string($conex, $tecnico);

    $ok = mysqli_query($conex,
        "INSERT INTO pagos (fecha, asset, proveedor, cliente, tarjeta, nivel, cantidad, apuesta, gerente, tecnico)
         VALUES ('$fecha_esc','$terminal_esc','$proveedor_esc','$cliente_esc','$tarjeta_esc',
                 '$nivel_esc','$cantidad_esc','$apuesta_esc','$gerente_esc','$tecnico_esc')"
    );

    if ($ok) {
        mysqli_query($conex,
            "INSERT INTO pagos_sala_log (archivo, dia, pago_num, importado_en)
             VALUES ('$nombre_esc','$dia_esc','$pago_esc',NOW())"
        );
        $total_importados++;
    }
}

escribirLog("Fecha operativa: $fechaOperativa | Archivo: $nombre_archivo | Nuevos: $total_importados");
echo "OK: $total_importados registros importados\n";
