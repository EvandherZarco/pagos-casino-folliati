<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

require 'vendor/autoload.php';
include("conexion.php");

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Worksheet\Table;
use PhpOffice\PhpSpreadsheet\Worksheet\Table\TableStyle;
use PhpOffice\PhpSpreadsheet\Shared\Date;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Alignment;

$hoy = date('d/m/Y');

// Si aún no eligió, mostrar pantalla de confirmación
if (!isset($_GET['borrar'])) { ?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Descargar Reporte</title>
    <link rel="stylesheet" href="styles.css">
    <style>
        .confirm-box {
            background: rgba(255,255,255,0.06);
            border: 1px solid rgba(255,255,255,0.12);
            border-radius: 16px;
            padding: 40px 30px;
            max-width: 400px;
            margin: 80px auto;
            text-align: center;
            box-shadow: 0 8px 32px rgba(0,0,0,0.3);
        }
        .confirm-box h2 { margin-bottom: 10px; color: #ffffff; font-size: 18px; }
        .confirm-box p  { color: rgba(255,255,255,0.6); margin-bottom: 30px; font-size: 14px; }
        .btn-si {
            background: #c0392b;
            color: white;
            padding: 12px 28px;
            border-radius: 8px;
            text-decoration: none;
            font-weight: 600;
            font-size: 14px;
            margin: 5px;
            display: inline-block;
            transition: background 0.15s;
        }
        .btn-no {
            background: #1a7a3c;
            color: white;
            padding: 12px 28px;
            border-radius: 8px;
            text-decoration: none;
            font-weight: 600;
            font-size: 14px;
            margin: 5px;
            display: inline-block;
            transition: background 0.15s;
        }
        .btn-si:hover { background: #a93226; }
        .btn-no:hover { background: #0f5c2a; }
        .btn-cancelar {
            color: rgba(255,255,255,0.4);
            font-size: 13px;
            text-decoration: none;
            display: inline-block;
            margin-top: 16px;
            transition: color 0.15s;
        }
        .btn-cancelar:hover { color: rgba(255,255,255,0.8); }
    </style>
</head>
<body style="background:#0d3320; font-family:'DM Sans',sans-serif;">
    <div class="confirm-box">
        <h2>Descargar Reporte</h2>
        <p>¿Deseas borrar todos los registros después de descargar?</p>
        <a class="btn-si" href="exportar_general.php?borrar=1">Sí, borrar después</a>
        <a class="btn-no" href="exportar_general.php?borrar=0">No, solo descargar</a>
        <br>
        <a href="menu.php" class="btn-cancelar">← Cancelar</a>
    </div>
</body>
</html>
<?php
    exit;
}

$borrar = $_GET['borrar'] === '1';
$spreadsheet = new Spreadsheet();

function crearHoja($spreadsheet, $conex, $titulo, $columnas, $sql, $letrasColumnas) {
    static $index = 0;

    $sheet = ($index == 0) ? $spreadsheet->getActiveSheet() : $spreadsheet->createSheet();
    $sheet->setTitle($titulo);
    $sheet->fromArray($columnas, NULL, 'A1');

    $resultado = mysqli_query($conex, $sql);
    $fila = 2;

    while ($row = mysqli_fetch_assoc($resultado)) {
        $col = 'A';
        foreach ($row as $key => $value) {
            if ($key == 'hora_inicial' || $key == 'hora_final' || $key == 'hora') {
                $value = Date::PHPToExcel(strtotime($value));
                $sheet->setCellValue($col . $fila, $value);
                $sheet->getStyle($col . $fila)->getNumberFormat()->setFormatCode('hh:mm');
            } else {
                $sheet->setCellValue($col . $fila, $value);
            }
            $col++;
        }
        $fila++;
    }

    $lastRow = $fila - 1;

    if ($lastRow >= 2) {
        $table = new Table("A1:{$letrasColumnas}{$lastRow}");
        $style = new TableStyle();
        $style->setTheme('TableStyleMedium2');
        $table->setStyle($style);
        $sheet->addTable($table);
    }

    foreach (range('A', $letrasColumnas) as $col) {
        $sheet->getColumnDimension($col)->setAutoSize(true);
    }

    $sheet->freezePane('A2');
    $index++;
}

function agregarTotalFila($sheet, $fila, $colLabel, $colValor, $total) {
    $filaTotal = $fila + 1;
    $sheet->setCellValue($colLabel . $filaTotal, 'TOTAL DEL DÍA:');
    $sheet->setCellValue($colValor . $filaTotal, $total);
    $sheet->getStyle($colLabel . $filaTotal . ':' . $colValor . $filaTotal)->applyFromArray([
        'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF'], 'size' => 12],
        'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '1F4E79']],
        'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER]
    ]);
}

crearHoja($spreadsheet, $conex, 'Proveedores',
    ['Proveedor','Técnico','Hora Inicial','Hora Final','Ingreso','Actividades','Fecha','Turno'],
    "SELECT proveedor, tecnico, hora_inicial, hora_final, ingreso_al_site, actividades, fecha, turno FROM proveedores ORDER BY fecha DESC",
    'H'
);

crearHoja($spreadsheet, $conex, 'Aperturas',
    ['Asset','Proveedor','Modelo','Serie','Técnico','Tipo Falla','Falla','Solución','Fecha','Turno'],
    "SELECT asset, proveedor, modelo, serie, tecnico, tipo_de_falla, falla, solucion, fecha, turno FROM datos ORDER BY fecha DESC",
    'J'
);

crearHoja($spreadsheet, $conex, 'Offla',
    ['Asset','Proveedor','Modelo','Serie','Técnico','Fecha','Razón'],
    "SELECT asset, proveedor, modelo, serie, tecnico, fecha, razon FROM offla ORDER BY fecha DESC",
    'G'
);

// Pagos con total
$spreadsheet->createSheet();
$sheetPagos = $spreadsheet->getSheet(3);
$sheetPagos->setTitle('Pagos');
$sheetPagos->fromArray(['Fecha','Asset','Proveedor','Cliente','Tarjeta','Nivel','Cantidad','Apuesta','Gerente','Técnico'], NULL, 'A1');

$resPagos = mysqli_query($conex, "SELECT fecha, asset, proveedor, cliente, tarjeta, nivel, cantidad, apuesta, gerente, tecnico FROM pagos ORDER BY fecha DESC");
$filaPagos = 2;
$totalPagos = 0;

while ($row = mysqli_fetch_assoc($resPagos)) {
    $col = 'A';
    foreach ($row as $value) { $sheetPagos->setCellValue($col++ . $filaPagos, $value); }
    $totalPagos += floatval($row['cantidad']);
    $filaPagos++;
}

$lastRowPagos = $filaPagos - 1;
if ($lastRowPagos >= 2) {
    $table = new Table("A1:J{$lastRowPagos}");
    $style = new TableStyle();
    $style->setTheme('TableStyleMedium2');
    $table->setStyle($style);
    $sheetPagos->addTable($table);
}
foreach (range('A', 'J') as $col) { $sheetPagos->getColumnDimension($col)->setAutoSize(true); }
$sheetPagos->freezePane('A2');
agregarTotalFila($sheetPagos, $filaPagos, 'F', 'G', $totalPagos);

// Handpay con total
$spreadsheet->createSheet();
$sheetHP = $spreadsheet->getSheet(4);
$sheetHP->setTitle('Handpay');
$sheetHP->fromArray(['Fecha','Hora','Cantidad','Blue Point','Cliente','Técnico','Gerente'], NULL, 'A1');

$resHP = mysqli_query($conex, "SELECT fecha, hora, cantidad, blue_point, cliente, tecnico, gerente FROM handpay ORDER BY fecha DESC");
$filaHP = 2;
$totalHP = 0;

while ($row = mysqli_fetch_assoc($resHP)) {
    $sheetHP->setCellValue('A' . $filaHP, $row['fecha']);
    $value = Date::PHPToExcel(strtotime($row['hora']));
    $sheetHP->setCellValue('B' . $filaHP, $value);
    $sheetHP->getStyle('B' . $filaHP)->getNumberFormat()->setFormatCode('hh:mm');
    $sheetHP->setCellValue('C' . $filaHP, $row['cantidad']);
    $sheetHP->setCellValue('D' . $filaHP, $row['blue_point']);
    $sheetHP->setCellValue('E' . $filaHP, $row['cliente']);
    $sheetHP->setCellValue('F' . $filaHP, $row['tecnico']);
    $sheetHP->setCellValue('G' . $filaHP, $row['gerente']);
    $totalHP += floatval($row['cantidad']);
    $filaHP++;
}

$lastRowHP = $filaHP - 1;
if ($lastRowHP >= 2) {
    $table = new Table("A1:G{$lastRowHP}");
    $style = new TableStyle();
    $style->setTheme('TableStyleMedium2');
    $table->setStyle($style);
    $sheetHP->addTable($table);
}
foreach (range('A', 'G') as $col) { $sheetHP->getColumnDimension($col)->setAutoSize(true); }
$sheetHP->freezePane('A2');
agregarTotalFila($sheetHP, $filaHP, 'B', 'C', $totalHP);

crearHoja($spreadsheet, $conex, 'Slips',
    ['Fecha','Nombre','Créditos','CNR','Comentario','Tipo','Técnico','Gerente'],
    "SELECT fecha, nombre, creditos, cnr, comentario, tipo, tecnico, gerente FROM slips ORDER BY fecha DESC",
    'H'
);

// Fecha operativa (antes de las 4am se considera el día anterior)
$horaActual = (int) date('H');
$fechaOperativa = ($horaActual < 4) ? date('Y-m-d', strtotime('-1 day')) : date('Y-m-d');

crearHoja($spreadsheet, $conex, 'Reporte de Técnicos',
    ['Fecha','Turno','Técnico','Buenas Noticias','Malas Noticias','Pendientes','Máq. Fuera de Servicio','Equipos con Falla','Visitas Proveedor','Comentarios Clientes','Comentarios Colaboradores','Total Pagos'],
    "SELECT fecha, turno, tecnico, buenas_noticias, malas_noticias, pendientes, maquinas_fuera, equipos_falla, visitas_proveedor, comentarios_clientes, comentarios_colaboradores, total_pagos FROM reporte_diario WHERE fecha = '$fechaOperativa' ORDER BY turno DESC",
    'L'
);

// Generar PDF antes de limpiar el buffer de salida
generarPDF($conex);

// Descargar Excel
ob_end_clean();

header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
header('Content-Disposition: attachment; filename="reporte_' . date('Y-m-d') . '.xlsx"');
header('Cache-Control: max-age=0');

$writer = new Xlsx($spreadsheet);
$tmpFile = tempnam(sys_get_temp_dir(), 'reporte_');
$writer->save($tmpFile);

if (file_exists($tmpFile) && filesize($tmpFile) > 0) {

    readfile($tmpFile);
    unlink($tmpFile);

    if ($borrar) {
        mysqli_query($conex, "TRUNCATE TABLE proveedores");
        mysqli_query($conex, "TRUNCATE TABLE datos");
        mysqli_query($conex, "TRUNCATE TABLE offla");
        mysqli_query($conex, "TRUNCATE TABLE pagos");
        mysqli_query($conex, "TRUNCATE TABLE handpay");
        mysqli_query($conex, "TRUNCATE TABLE slips");
        mysqli_query($conex, "TRUNCATE TABLE reporte_diario");
    }
}

exit;

function generarPDF($conex) {
    require_once(__DIR__ . '/fpdf.php');

    $fecha    = date('Y-m-d');
    $carpeta  = __DIR__ . '/historiales/';
    $archivo  = $carpeta . 'reporte_' . $fecha . '.pdf';

    if (!is_dir($carpeta)) {
        mkdir($carpeta, 0755, true);
    }

    $pdf = new FPDF('L', 'mm', 'A4'); // Horizontal para que quepan las columnas
    $pdf->SetMargins(10, 10, 10);
    $pdf->SetAutoPageBreak(true, 15);
    $pdf->AddPage();
    $pdf->SetFont('Arial', 'B', 14);
    $pdf->Cell(0, 10, 'REPORTE GENERAL - ' . date('d/m/Y'), 0, 1, 'C');
    $pdf->Ln(3);

    // ── Función interna para dibujar tabla ──
    $dibujarTabla = function($titulo, $headers, $anchos, $filas) use ($pdf) {
        $pdf->SetFont('Arial', 'B', 11);
        $pdf->SetFillColor(98, 0, 234);
        $pdf->SetTextColor(255, 255, 255);
        $pdf->Cell(0, 7, $titulo, 0, 1, 'L', true);
        $pdf->SetFont('Arial', 'B', 8);
        $pdf->SetFillColor(180, 140, 255);
        $pdf->SetTextColor(0, 0, 0);
        foreach ($headers as $i => $h) {
            $pdf->Cell($anchos[$i], 6, $h, 1, 0, 'C', true);
        }
        $pdf->Ln();
        $pdf->SetFont('Arial', '', 7);
        $pdf->SetFillColor(245, 245, 255);
        $fill = false;
        foreach ($filas as $fila) {
            $valores = array_values($fila);
            foreach ($valores as $i => $v) {
                $pdf->Cell($anchos[$i], 5, iconv('UTF-8','windows-1252//IGNORE', $v), 1, 0, 'L', $fill);
            }
            $pdf->Ln();
            $fill = !$fill;
        }
        $pdf->Ln(4);
    };

    // ── Aperturas ──
    $res = mysqli_query($conex, "SELECT asset, proveedor, modelo, tecnico, tipo_de_falla, falla, solucion, fecha, turno FROM datos ORDER BY fecha DESC");
    $filas = [];
    while ($r = mysqli_fetch_assoc($res)) $filas[] = $r;
    $dibujarTabla('APERTURAS DE MÁQUINAS',
        ['Asset','Proveedor','Modelo','Técnico','Tipo','Falla','Solución','Fecha','Turno'],
        [18, 28, 28, 28, 22, 38, 44, 22, 22],
        $filas
    );

    // ── Pagos ──
    $res = mysqli_query($conex, "SELECT fecha, asset, proveedor, cliente, nivel, cantidad, gerente, tecnico FROM pagos ORDER BY fecha DESC");
    $filas = []; $total = 0;
    while ($r = mysqli_fetch_assoc($res)) { $filas[] = $r; $total += floatval($r['cantidad']); }
    $dibujarTabla('PAGOS',
        ['Fecha','Asset','Proveedor','Cliente','Nivel','Cantidad','Gerente','Técnico'],
        [22, 18, 28, 40, 28, 22, 38, 28],
        $filas
    );
    $pdf->SetFont('Arial', 'B', 9);
    $pdf->SetFillColor(31, 78, 121);
    $pdf->SetTextColor(255, 255, 255);
    $pdf->Cell(156, 6, 'TOTAL DEL DÍA:', 1, 0, 'R', true);
    $pdf->Cell(22, 6, '$' . number_format($total, 2), 1, 1, 'C', true);
    $pdf->SetTextColor(0, 0, 0);
    $pdf->Ln(4);

    // ── Handpay ──
    $res = mysqli_query($conex, "SELECT fecha, hora, cantidad, blue_point, cliente, tecnico, gerente FROM handpay ORDER BY fecha DESC");
    $filas = []; $totalHP = 0;
    while ($r = mysqli_fetch_assoc($res)) { $filas[] = $r; $totalHP += floatval($r['cantidad']); }
    $dibujarTabla('HANDPAY',
        ['Fecha','Hora','Cantidad','Blue Point','Cliente','Técnico','Gerente'],
        [22, 18, 24, 24, 44, 30, 38],
        $filas
    );
    $pdf->SetFont('Arial', 'B', 9);
    $pdf->SetFillColor(31, 78, 121);
    $pdf->SetTextColor(255, 255, 255);
    $pdf->Cell(64, 6, 'TOTAL DEL DÍA:', 1, 0, 'R', true);
    $pdf->Cell(24, 6, '$' . number_format($totalHP, 2), 1, 1, 'C', true);
    $pdf->SetTextColor(0, 0, 0);
    $pdf->Ln(4);

    // ── Slips ──
    $res = mysqli_query($conex, "SELECT fecha, nombre, creditos, cnr, tipo, tecnico, gerente FROM slips ORDER BY fecha DESC");
    $filas = [];
    while ($r = mysqli_fetch_assoc($res)) $filas[] = $r;
    $dibujarTabla('SLIPS',
        ['Fecha','Nombre','Créditos','CNR','Tipo','Técnico','Gerente'],
        [22, 50, 22, 22, 22, 30, 32],
        $filas
    );

    // ── Proveedores ──
    $res = mysqli_query($conex, "SELECT proveedor, tecnico, hora_inicial, hora_final, actividades, fecha, turno FROM proveedores ORDER BY fecha DESC");
    $filas = [];
    while ($r = mysqli_fetch_assoc($res)) $filas[] = $r;
    $dibujarTabla('VISITA DE PROVEEDORES',
        ['Proveedor','Técnico','H. Inicial','H. Final','Actividades','Fecha','Turno'],
        [36, 30, 20, 20, 80, 22, 22],
        $filas
    );

    // ── Reporte de Técnicos (solo día operativo) ──
    $horaPDF = (int) date('H');
    $fechaPDF = ($horaPDF < 4) ? date('Y-m-d', strtotime('-1 day')) : date('Y-m-d');
    $res = mysqli_query($conex, "SELECT fecha, turno, tecnico, buenas_noticias, malas_noticias, pendientes, maquinas_fuera, equipos_falla, visitas_proveedor, comentarios_clientes, comentarios_colaboradores, total_pagos FROM reporte_diario WHERE fecha = '$fechaPDF' ORDER BY turno DESC");
    $filas = [];
    while ($r = mysqli_fetch_assoc($res)) $filas[] = $r;
    $dibujarTabla('REPORTE DE TÉCNICOS',
        ['Fecha','Turno','Técnico','Buenas','Malas','Pendientes','Máq.Fuera','Equipos','Visitas','Clientes','Colabor.','Total $'],
        [18, 18, 24, 28, 28, 26, 26, 26, 22, 22, 22, 17],
        $filas
    );

    $pdf->Output('F', $archivo);
}