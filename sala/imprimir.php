<?php
require 'db.php';
$id = (int)($_GET['id'] ?? 0);
if (!$id) { header('Location: index.php'); exit; }

$r = mysqli_fetch_assoc(mysqli_query($conex, "SELECT * FROM pagos WHERE id=$id"));
if (!$r) { header('Location: index.php'); exit; }

$fecha_fmt    = date('d/m/Y', strtotime($r['fecha']));
$hora_fmt     = substr($r['hora'], 0, 5);
$cantidad_fmt = '$' . number_format((float)$r['cantidad'], 2);
$apuesta_fmt  = '$' . number_format((float)$r['apuesta'], 2);
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <title>Pago <?= $r['pago_num'] ?> — Turno <?= $r['turno'] ?></title>
  <link rel="stylesheet" href="styles.css">
  <style>
    @media print {
      @page { size: letter portrait; margin: 1.5cm; }
      body { background: white; margin: 0; padding: 0; min-height: 0 !important; height: auto !important; }
      .no-print { display: none !important; }
      .receipt-wrap { padding: 0; min-height: 0 !important; }
      .ticket { page-break-after: avoid; page-break-inside: avoid; }
    }

    .receipt-wrap {
      padding: 20px;
      display: flex;
      justify-content: center;
      align-items: flex-start;
    }

    .ticket {
      width: 100%;
      max-width: 680px;
      border: 2px solid #000;
      font-family: Arial, sans-serif;
      font-size: 0.82rem;
      background: #fff;
      color: #000;
    }

    /* Encabezado */
    .ticket-header {
      display: flex;
      justify-content: space-between;
      align-items: center;
      background: #111;
      color: #fff;
      padding: 8px 16px;
    }
    .ticket-header .pago-title { font-size: 1.1rem; font-weight: bold; letter-spacing: 2px; }
    .ticket-header .turno-badge {
      background: #d4a017;
      color: #000;
      font-weight: bold;
      padding: 3px 14px;
      border-radius: 3px;
      font-size: 1rem;
    }

    /* Cuerpo en dos columnas */
    .ticket-body {
      display: grid;
      grid-template-columns: 1fr 1fr;
      gap: 0;
    }
    .ticket-col {
      border-right: 1px solid #ccc;
    }
    .ticket-col:last-child { border-right: none; }

    .ticket-body table {
      width: 100%;
      border-collapse: collapse;
    }
    .ticket-body td {
      padding: 5px 10px;
      border-bottom: 1px solid #e0e0e0;
      color: #000 !important;
      background: #fff;
    }
    .ticket-body td:first-child {
      font-weight: bold;
      text-transform: uppercase;
      font-size: 0.72rem;
      width: 40%;
      background: #f5f5f5;
      border-right: 1px solid #ddd;
    }
    .ticket-body tr:last-child td { border-bottom: none; }
    .ticket-body tr.highlight td:last-child {
      font-weight: bold;
      font-size: 1rem;
    }
  </style>
</head>
<body>

<div class="no-print" style="padding:16px;display:flex;gap:10px;justify-content:center;">
  <button onclick="window.print()" class="btn btn-gold">🖨 Imprimir</button>
  <a href="lista.php" class="btn btn-ghost">Ver Registros</a>
  <a href="index.php" class="btn btn-ghost">Nuevo Pago</a>
</div>

<div class="receipt-wrap">
  <div class="ticket">

    <div class="ticket-header">
      <span class="pago-title">PAGO <?= $r['pago_num'] ?></span>
      <span class="turno-badge">TURNO <?= $r['turno'] ?></span>
    </div>

    <div class="ticket-body">
      <!-- Columna izquierda -->
      <div class="ticket-col">
        <table>
          <tr class="highlight"><td>Cantidad</td> <td><?= $cantidad_fmt ?></td></tr>
          <tr><td>Apuesta</td>  <td><?= $apuesta_fmt ?></td></tr>
          <tr><td>Nivel</td>    <td><?= htmlspecialchars($r['nivel']) ?></td></tr>
          <tr><td>Cliente</td>  <td><?= htmlspecialchars($r['cliente']) ?></td></tr>
          <tr><td>ID</td>       <td><?= htmlspecialchars($r['id_cliente']) ?></td></tr>
          <tr><td>Técnico</td>  <td><?= htmlspecialchars($r['tecnico']) ?></td></tr>
          <tr><td>Gerente</td>  <td><?= htmlspecialchars($r['gerente']) ?></td></tr>
        </table>
      </div>
      <!-- Columna derecha -->
      <div class="ticket-col">
        <table>
          <tr><td>Terminal</td>  <td><?= htmlspecialchars($r['asset']) ?></td></tr>
          <tr><td>Unidad</td>    <td><?= htmlspecialchars($r['unidad']) ?></td></tr>
          <tr><td>Proveedor</td> <td><?= htmlspecialchars($r['proveedor']) ?></td></tr>
          <tr><td>Fecha</td>     <td><?= $fecha_fmt ?></td></tr>
          <tr><td>Hora</td>      <td><?= $hora_fmt ?></td></tr>
        </table>
      </div>
    </div>

  </div>
</div>

</body>
</html>
