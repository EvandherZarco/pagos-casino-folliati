<?php
/*
 * xls_helpers.php — Generación del archivo XLS mensual (SpreadsheetML)
 * Usado por: cerrar_dia.php (regenera el XLS automáticamente al cerrar el día)
 *            historico.php (botón "Exportar a XLS" para generar/regenerar
 *            el archivo de cualquier mes a demanda, sin necesidad de cerrar un día)
 *
 * Nombres de hoja: solo el número del día ("2", "15"), sin prefijo —
 * historico.php espera ese formato exacto para poder leerlas de vuelta.
 *
 * Desarrollado por Evandher Zarco (ZarcoMecatronics) - Junio 2026
 */

function generarXLSMes($conex, $anio, $mes_num, $ruta) {
    $dias_res = mysqli_query($conex,
        "SELECT DISTINCT fecha FROM pagos
         WHERE YEAR(fecha)=$anio AND MONTH(fecha)=$mes_num AND cerrado=1
         ORDER BY fecha ASC"
    );

    $worksheets = '';
    while ($row = mysqli_fetch_assoc($dias_res)) {
        $fecha    = $row['fecha'];
        $dia_num  = (int)date('j', strtotime($fecha));
        $fecha_fmt = date('d/m/Y', strtotime($fecha));

        $pagos = mysqli_query($conex,
            "SELECT * FROM pagos WHERE fecha='$fecha' AND cerrado=1 ORDER BY turno, pago_num"
        );

        $cols = ['PAGO','TURNO','TERMINAL','UNIDAD','PROVEEDOR','MODELO','N° SERIE',
                 'CANTIDAD','APUESTA','NIVEL','CLIENTE','ID','FECHA','HORA','TÉCNICO','GERENTE'];

        $header = '<Row>';
        foreach ($cols as $c) {
            $header .= '<Cell ss:StyleID="h"><Data ss:Type="String">' . htmlspecialchars($c) . '</Data></Cell>';
        }
        $header .= '</Row>';

        $filas = '';
        $total = 0;
        while ($p = mysqli_fetch_assoc($pagos)) {
            $total += (float)$p['cantidad'];
            $filas .= '<Row>'
                . '<Cell><Data ss:Type="Number">'  . $p['pago_num']                       . '</Data></Cell>'
                . '<Cell><Data ss:Type="String">'  . htmlspecialchars($p['turno'])         . '</Data></Cell>'
                . '<Cell><Data ss:Type="String">'  . htmlspecialchars($p['asset'])         . '</Data></Cell>'
                . '<Cell><Data ss:Type="String">'  . htmlspecialchars($p['unidad'])        . '</Data></Cell>'
                . '<Cell><Data ss:Type="String">'  . htmlspecialchars($p['proveedor'])     . '</Data></Cell>'
                . '<Cell><Data ss:Type="String">'  . htmlspecialchars($p['modelo'])        . '</Data></Cell>'
                . '<Cell><Data ss:Type="String">'  . htmlspecialchars($p['numero_serie'])  . '</Data></Cell>'
                . '<Cell><Data ss:Type="Number">'  . $p['cantidad']                        . '</Data></Cell>'
                . '<Cell><Data ss:Type="Number">'  . $p['apuesta']                         . '</Data></Cell>'
                . '<Cell><Data ss:Type="String">'  . htmlspecialchars($p['nivel'])         . '</Data></Cell>'
                . '<Cell><Data ss:Type="String">'  . htmlspecialchars($p['cliente'])       . '</Data></Cell>'
                . '<Cell><Data ss:Type="String">'  . htmlspecialchars($p['id_cliente'])    . '</Data></Cell>'
                . '<Cell><Data ss:Type="String">'  . $fecha_fmt                            . '</Data></Cell>'
                . '<Cell><Data ss:Type="String">'  . substr($p['hora'], 0, 5)              . '</Data></Cell>'
                . '<Cell><Data ss:Type="String">'  . htmlspecialchars($p['tecnico'])       . '</Data></Cell>'
                . '<Cell><Data ss:Type="String">'  . htmlspecialchars($p['gerente'])       . '</Data></Cell>'
                . '</Row>';
        }

        // Fila de total
        $filas .= '<Row>'
            . '<Cell ss:StyleID="h" ss:MergeAcross="6"><Data ss:Type="String">TOTAL DEL DÍA</Data></Cell>'
            . '<Cell ss:StyleID="h"><Data ss:Type="Number">' . $total . '</Data></Cell>'
            . '</Row>';

        $worksheets .= '<Worksheet ss:Name="' . $dia_num . '"><Table>' . $header . $filas . '</Table></Worksheet>';
    }

    if (!$worksheets) return false;

    $xml  = '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
    $xml .= '<?mso-application progid="Excel.Sheet"?>' . "\n";
    $xml .= '<Workbook xmlns="urn:schemas-microsoft-com:office:spreadsheet" xmlns:ss="urn:schemas-microsoft-com:office:spreadsheet">' . "\n";
    $xml .= '<Styles><Style ss:ID="h"><Font ss:Bold="1"/><Interior ss:Color="#FFD700" ss:Pattern="Solid"/></Style></Styles>' . "\n";
    $xml .= $worksheets . '</Workbook>';

    return file_put_contents($ruta, $xml) !== false;
}
