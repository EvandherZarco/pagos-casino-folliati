<?php
require 'db.php';
require 'renumerar.php';

$id = (int)($_GET['id'] ?? 0);
if (!$id) { header('Location: historico.php'); exit; }

$meses_es = ['','enero','febrero','marzo','abril','mayo','junio',
             'julio','agosto','septiembre','octubre','noviembre','diciembre'];

$r = mysqli_fetch_assoc(mysqli_query($conex, "SELECT fecha FROM pagos WHERE id=$id AND cerrado=1"));
if (!$r) { header('Location: historico.php'); exit; }

$fecha   = $r['fecha'];
$mes_num = (int)date('n', strtotime($fecha));
$anio    = (int)date('Y', strtotime($fecha));
$dia     = (int)date('j', strtotime($fecha));
$ruta    = 'C:/Users/PITSTAND/Desktop/PAGOS POR MES/pagos-' . $meses_es[$mes_num] . "-$anio.xls";

mysqli_query($conex, "DELETE FROM pagos WHERE id=$id");
renumerar_dia($conex, $fecha);

function xe($s) {
    return htmlspecialchars(str_replace(["\r","\n"], ' ', (string)$s), ENT_XML1);
}

$dias_res = mysqli_query($conex,
    "SELECT DISTINCT fecha FROM pagos
     WHERE YEAR(fecha)=$anio AND MONTH(fecha)=$mes_num AND cerrado=1
     ORDER BY fecha ASC"
);
$worksheets = '';
while ($row = mysqli_fetch_assoc($dias_res)) {
    $f        = $row['fecha'];
    $dia_num  = (int)date('j', strtotime($f));
    $fecha_fmt = date('d/m/Y', strtotime($f));
    $pagos    = mysqli_query($conex,
        "SELECT * FROM pagos WHERE fecha='$f' AND cerrado=1 ORDER BY turno, pago_num"
    );
    $rows_xml = '<Row><Cell><Data ss:Type="Number">' . $dia_num . '</Data></Cell><Cell><Data ss:Type="String"></Data></Cell><Cell><Data ss:Type="String"></Data></Cell></Row>';
    while ($p = mysqli_fetch_assoc($pagos)) {
        $hora = substr($p['hora'], 0, 8);
        $rows_xml .= '
<Row><Cell><Data ss:Type="String"></Data></Cell><Cell ss:StyleID="h"><Data ss:Type="String">PAGO ' . $p['pago_num'] . '</Data></Cell><Cell ss:StyleID="h"><Data ss:Type="String">TURNO ' . xe($p['turno']) . '</Data></Cell></Row>
<Row><Cell/><Cell><Data ss:Type="String">TERMINAL</Data></Cell><Cell><Data ss:Type="String">' . xe($p['asset']) . '</Data></Cell></Row>
<Row><Cell/><Cell><Data ss:Type="String">UNIDAD</Data></Cell><Cell><Data ss:Type="String">' . xe($p['unidad']) . '</Data></Cell></Row>
<Row><Cell/><Cell><Data ss:Type="String">CANTIDAD</Data></Cell><Cell><Data ss:Type="Number">' . $p['cantidad'] . '</Data></Cell></Row>
<Row><Cell/><Cell><Data ss:Type="String">PROVEEDOR</Data></Cell><Cell><Data ss:Type="String">' . xe($p['proveedor']) . '</Data></Cell></Row>
<Row><Cell/><Cell><Data ss:Type="String">NIVEL</Data></Cell><Cell><Data ss:Type="String">' . xe($p['nivel']) . '</Data></Cell></Row>
<Row><Cell/><Cell><Data ss:Type="String">CLIENTE</Data></Cell><Cell><Data ss:Type="String">' . xe($p['cliente']) . '</Data></Cell></Row>
<Row><Cell/><Cell><Data ss:Type="String">ID</Data></Cell><Cell><Data ss:Type="String">' . xe($p['id_cliente']) . '</Data></Cell></Row>
<Row><Cell/><Cell><Data ss:Type="String">APUESTA</Data></Cell><Cell><Data ss:Type="Number">' . $p['apuesta'] . '</Data></Cell></Row>
<Row><Cell/><Cell><Data ss:Type="String">FECHA</Data></Cell><Cell><Data ss:Type="String">' . $fecha_fmt . '</Data></Cell></Row>
<Row><Cell/><Cell><Data ss:Type="String">HORA</Data></Cell><Cell><Data ss:Type="String">' . $hora . '</Data></Cell></Row>
<Row><Cell/><Cell><Data ss:Type="String">TECNICO</Data></Cell><Cell><Data ss:Type="String">' . xe($p['tecnico']) . '</Data></Cell></Row>
<Row><Cell/><Cell><Data ss:Type="String">GERENTE</Data></Cell><Cell><Data ss:Type="String">' . xe($p['gerente']) . '</Data></Cell></Row>
<Row><Cell/><Cell><Data ss:Type="String"></Data></Cell><Cell><Data ss:Type="String"></Data></Cell></Row>';
    }
    $worksheets .= '<Worksheet ss:Name="' . $dia_num . '"><Table>' . $rows_xml . '</Table></Worksheet>';
}
if ($worksheets) {
    $xml  = '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
    $xml .= '<?mso-application progid="Excel.Sheet"?>' . "\n";
    $xml .= '<Workbook xmlns="urn:schemas-microsoft-com:office:spreadsheet" xmlns:ss="urn:schemas-microsoft-com:office:spreadsheet">' . "\n";
    $xml .= '<Styles><Style ss:ID="h"><Font ss:Bold="1"/><Interior ss:Color="#FFFF00" ss:Pattern="Solid"/></Style></Styles>' . "\n";
    $xml .= $worksheets . '</Workbook>';
    file_put_contents($ruta, $xml);
}
header("Location: historico.php?mes=$mes_num&anio=$anio&dia=$dia");
exit;
