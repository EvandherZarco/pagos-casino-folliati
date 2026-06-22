<?php
require '../vendor/autoload.php';
include('../conexion.php');

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;

$mes  = isset($_GET['mes'])  ? $_GET['mes']  : date('m');
$anio = isset($_GET['anio']) ? $_GET['anio'] : date('Y');

$meses = ['01'=>'Enero','02'=>'Febrero','03'=>'Marzo','04'=>'Abril',
          '05'=>'Mayo','06'=>'Junio','07'=>'Julio','08'=>'Agosto',
          '09'=>'Septiembre','10'=>'Octubre','11'=>'Noviembre','12'=>'Diciembre'];

$nombreMes = $meses[$mes] ?? 'Mes';

$resultado = mysqli_query($conex,
    "SELECT * FROM reporte_diario
     WHERE MONTH(fecha) = '$mes' AND YEAR(fecha) = '$anio'
     ORDER BY fecha ASC, FIELD(turno,'APERTURA','INTERMEDIO','CIERRE')"
);

$spreadsheet = new Spreadsheet();
$sheet = $spreadsheet->getActiveSheet();
$sheet->setTitle(strtoupper($nombreMes) . '_' . $anio);

// Título
$sheet->mergeCells('A1:M1');
$sheet->setCellValue('A1', 'REPORTE DIARIO — ' . strtoupper($nombreMes) . ' ' . $anio);
$sheet->getStyle('A1')->applyFromArray([
    'font' => ['bold' => true, 'size' => 14, 'color' => ['rgb' => 'FFFFFF']],
    'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '3700B3']],
    'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER]
]);
$sheet->getRowDimension(1)->setRowHeight(25);

// Headers
$headers = ['FECHA','TURNO','TÉCNICO','BUENAS NOTICIAS','MALAS NOTICIAS','PENDIENTES',
            'MÁQ. FUERA SERVICIO','EQUIPOS FALLA','VISITAS PROVEEDOR',
            'COMENT. CLIENTES','COMENT. COLABORADORES','TOTAL PAGOS'];
$cols = range('A','L');

$sheet->fromArray($headers, NULL, 'A2');
$sheet->getStyle('A2:L2')->applyFromArray([
    'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
    'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '6200EA']],
    'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER]
]);

$fila = 3;
$totalGeneral = 0;
$fill = false;

while ($r = mysqli_fetch_assoc($resultado)) {
    $valores = [
        date('d/m/Y', strtotime($r['fecha'])),
        $r['turno'],
        $r['tecnico'],
        $r['buenas_noticias'],
        $r['malas_noticias'],
        $r['pendientes'],
        $r['maquinas_fuera'],
        $r['equipos_falla'],
        $r['visitas_proveedor'],
        $r['comentarios_clientes'],
        $r['comentarios_colaboradores'],
        floatval($r['total_pagos'])
    ];
    $sheet->fromArray($valores, NULL, 'A' . $fila);

    if ($fill) {
        $sheet->getStyle('A'.$fila.':L'.$fila)->applyFromArray([
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'EDE7F6']]
        ]);
    }

    $sheet->getStyle('A'.$fila.':L'.$fila)->getAlignment()->setWrapText(true);
    $sheet->getStyle('L'.$fila)->getNumberFormat()->setFormatCode('$#,##0.00');
    $totalGeneral += floatval($r['total_pagos']);
    $fila++;
    $fill = !$fill;
}

// Fila de total
$sheet->setCellValue('K'.$fila, 'TOTAL DEL MES:');
$sheet->setCellValue('L'.$fila, $totalGeneral);
$sheet->getStyle('K'.$fila.':L'.$fila)->applyFromArray([
    'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF'], 'size' => 11],
    'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '1F4E79']],
    'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER]
]);
$sheet->getStyle('L'.$fila)->getNumberFormat()->setFormatCode('$#,##0.00');

// Bordes
$sheet->getStyle('A2:L'.($fila))->applyFromArray([
    'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => 'CCCCCC']]]
]);

// Anchos de columna
$anchos = ['A'=>12,'B'=>12,'C'=>18,'D'=>35,'E'=>35,'F'=>35,
           'G'=>25,'H'=>25,'I'=>20,'J'=>30,'K'=>30,'L'=>14];
foreach ($anchos as $col => $ancho) {
    $sheet->getColumnDimension($col)->setWidth($ancho);
}
$sheet->freezePane('A3');

ob_end_clean();
header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
header('Content-Disposition: attachment; filename="reporte_diario_' . $nombreMes . '_' . $anio . '.xlsx"');
header('Cache-Control: max-age=0');

$writer = new Xlsx($spreadsheet);
$writer->save('php://output');
exit;
