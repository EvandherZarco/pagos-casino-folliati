<?php
require 'db.php';
require_once 'push_helpers.php'; // consultarPagosManualesOficina() — temporal, mientras dure la captura manual en oficina
require_once 'fecha_helpers.php'; // fechaOperativa() — IMPORTANTE: si se cambia la regla horaria, cambiar también en cerrar_dia.php

$meses_es = ['','Enero','Febrero','Marzo','Abril','Mayo','Junio',
             'Julio','Agosto','Septiembre','Octubre','Noviembre','Diciembre'];

$meses_archivo = ['','ENERO','FEBRERO','MARZO','ABRIL','MAYO','JUNIO',
                  'JULIO','AGOSTO','SEPTIEMBRE','OCTUBRE','NOVIEMBRE','DICIEMBRE'];

$carpeta = '\\\\172.26.0.149\\pagosmes\\';

$_fecha_op = fechaOperativa();
$mes_sel  = (int)($_GET['mes']  ?? date('n', strtotime($_fecha_op)));
$anio_sel = (int)($_GET['anio'] ?? date('Y', strtotime($_fecha_op)));
$dia_sel  = (int)($_GET['dia']  ?? 0);

// Buscar archivo del mes seleccionado
$nombre_archivo = "PAGOS-{$meses_archivo[$mes_sel]}-{$anio_sel}.xls";
$ruta_archivo   = $carpeta . $nombre_archivo;
$archivo_existe = file_exists($ruta_archivo);

// Leer días disponibles del archivo XLS
$dias_disponibles = [];
$pagos_dia = [];

// Pagos manuales de oficina del día (NUEVO) — independiente del XLS,
// solo existen del lado de oficina.
$pagos_manuales_dia = [];
$total_manual_dia = 0;

if ($archivo_existe) {
    $contenido = file_get_contents($ruta_archivo);
    // Extraer nombres de hojas (días)
    preg_match_all('/<Worksheet ss:Name="(\d+)">/', $contenido, $matches);
    $dias_disponibles = $matches[1];
    sort($dias_disponibles, SORT_NUMERIC);

    // Si hay día seleccionado, extraer sus pagos
    if ($dia_sel && in_array((string)$dia_sel, $dias_disponibles)) {
        // Extraer la hoja del día
        preg_match('/<Worksheet ss:Name="' . $dia_sel . '">(.*?)<\/Worksheet>/s', $contenido, $hoja_match);
        if ($hoja_match) {
            $tabla = $hoja_match[1];
            // Extraer filas
            preg_match_all('/<Row>(.*?)<\/Row>/s', $tabla, $filas_match);
            $filas = $filas_match[1];

            $encabezados = [];
            foreach ($filas as $i => $fila) {
                preg_match_all('/<Data[^>]*>(.*?)<\/Data>/s', $fila, $celdas_match);
                $celdas = array_map('strip_tags', $celdas_match[1]);

                if ($i === 0) {
                    $encabezados = $celdas;
                    continue;
                }
                // Saltar fila de total
                if (isset($celdas[0]) && strtoupper(trim($celdas[0])) === 'TOTAL DEL DÍA') continue;
                if (empty(array_filter($celdas))) continue;

                $pago = [];
                foreach ($encabezados as $j => $col) {
                    $pago[$col] = $celdas[$j] ?? '';
                }
                if (!empty($pago)) $pagos_dia[] = $pago;
            }
        }
    }
}

if ($dia_sel) {
    $fecha_sel = sprintf('%04d-%02d-%02d', $anio_sel, $mes_sel, $dia_sel);
    $pagos_manuales_dia = consultarPagosManualesOficina($fecha_sel);
    $total_manual_dia = array_sum(array_column($pagos_manuales_dia, 'cantidad'));
}

$total_dia = array_sum(array_column($pagos_dia, 'CANTIDAD'));
$total_combinado_dia = (float)$total_dia + (float)$total_manual_dia;
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Histórico — Archivos</title>
  <link rel="stylesheet" href="styles.css">
  <style>
    .filter-card {
      background: var(--surface);
      border: 1px solid var(--border);
      border-radius: var(--radius);
      padding: 20px;
      margin-bottom: 20px;
    }
    .filter-card h3 {
      font-family: 'Barlow Condensed', sans-serif;
      font-size: 0.78rem;
      letter-spacing: 3px;
      text-transform: uppercase;
      color: var(--text-muted);
      margin-bottom: 14px;
    }
    .filter-row { display: flex; gap: 10px; align-items: center; flex-wrap: wrap; }
    .filter-row select, .filter-row input[type="number"] {
      background: var(--input-bg);
      border: 1px solid var(--border);
      color: var(--text);
      padding: 8px 12px;
      border-radius: var(--radius);
      font-family: inherit;
      font-size: 0.95rem;
    }
    .dias-grid {
      display: grid;
      grid-template-columns: repeat(auto-fill, minmax(80px, 1fr));
      gap: 10px;
      margin-bottom: 20px;
    }
    .dia-btn {
      background: var(--surface);
      border: 1px solid var(--border);
      border-radius: var(--radius);
      padding: 14px 8px;
      text-align: center;
      cursor: pointer;
      text-decoration: none;
      display: block;
      transition: all 0.18s;
    }
    .dia-btn:hover, .dia-btn.active {
      border-color: var(--gold);
      background: rgba(212,160,23,0.1);
    }
    .dia-btn .dia-num {
      font-family: 'Barlow Condensed', sans-serif;
      font-size: 1.8rem;
      font-weight: 700;
      color: var(--gold);
      display: block;
    }
    .sin-archivo {
      text-align: center;
      color: var(--text-muted);
      padding: 40px;
      font-size: 0.95rem;
    }
    .pagos-header {
      display: flex;
      justify-content: space-between;
      align-items: center;
      margin-bottom: 14px;
    }
    .pagos-header h2 {
      font-family: 'Barlow Condensed', sans-serif;
      font-size: 1.3rem;
      color: var(--gold);
    }
    .total-badge {
      background: rgba(212,160,23,0.15);
      color: var(--gold);
      padding: 6px 16px;
      border-radius: var(--radius);
      font-family: 'IBM Plex Mono', monospace;
      font-size: 0.95rem;
    }
  </style>
</head>
<body>

<div class="header">
  <h1>Histórico — Archivos</h1>
  <span class="live-time" id="clock"></span>
</div>
<nav class="nav">
  <a href="index.php">Registrar</a>
  <a href="lista.php">Consultar</a>
  <a href="historico.php">Histórico</a>
  <a href="historico_archivos.php" class="active">Archivos</a>
  <a href="cerrar_dia.php">Cerrar Día</a>
  <a href="panel/login.php">Panel</a>
</nav>

<div class="container" style="max-width:960px;">

  <!-- FILTRO MES/AÑO -->
  <div class="filter-card">
    <h3>Selecciona Mes y Año</h3>
    <form method="get" class="filter-row">
      <select name="mes">
        <?php for ($i = 1; $i <= 12; $i++): ?>
        <option value="<?=$i?>" <?= $mes_sel===$i ? 'selected':'' ?>><?= $meses_es[$i] ?></option>
        <?php endfor; ?>
      </select>
      <input type="number" name="anio" value="<?= $anio_sel ?>" min="2024" max="2030" style="width:90px;">
      <button type="submit" class="btn btn-gold">Buscar</button>
    </form>
  </div>

  <!-- DÍAS DEL MES -->
  <div class="form-section" style="margin-bottom:20px;">
    <div style="font-family:'Barlow Condensed',sans-serif;font-size:0.78rem;letter-spacing:3px;text-transform:uppercase;color:var(--text-muted);margin-bottom:14px;padding-bottom:8px;border-bottom:1px solid var(--border);">
      <?= $meses_es[$mes_sel] ?> <?= $anio_sel ?>
      <?php if ($archivo_existe): ?>
        <span style="color:var(--gold);margin-left:12px;">📄 <?= $nombre_archivo ?></span>
      <?php endif; ?>
    </div>

    <?php if (!$archivo_existe): ?>
      <p class="sin-archivo">No se encontró el archivo <strong><?= $nombre_archivo ?></strong> en la carpeta compartida.</p>
    <?php elseif (empty($dias_disponibles)): ?>
      <p class="sin-archivo">El archivo no contiene días con registros.</p>
    <?php else: ?>
      <div class="dias-grid">
        <?php foreach ($dias_disponibles as $d):
          $activo = $dia_sel === (int)$d ? 'active' : '';
        ?>
        <a href="?mes=<?=$mes_sel?>&anio=<?=$anio_sel?>&dia=<?=$d?>"
           class="dia-btn <?=$activo?>">
          <span class="dia-num"><?=$d?></span>
        </a>
        <?php endforeach; ?>
      </div>
    <?php endif; ?>
  </div>

  <!-- PAGOS DEL DÍA -->
  <?php if ($dia_sel && !empty($pagos_dia)): ?>
  <div class="form-section">
    <div class="pagos-header">
      <h2>Día <?= $dia_sel ?> de <?= $meses_es[$mes_sel] ?></h2>
      <span class="total-badge">Total: $<?= number_format($total_combinado_dia, 2) ?></span>
    </div>
    <?php if ($total_manual_dia > 0): ?>
    <p style="font-size:0.75rem;color:var(--text-muted);margin:-8px 0 14px;">
      $<?= number_format((float)$total_dia, 2) ?> de sala + $<?= number_format((float)$total_manual_dia, 2) ?> manuales (oficina)
    </p>
    <?php endif; ?>
    <div class="table-wrap">
      <table>
        <thead>
          <tr>
            <th>Pago</th><th>Turno</th><th>Terminal</th><th>Proveedor</th>
            <th>Cantidad</th><th>Nivel</th><th>Cliente</th><th>Hora</th><th>Técnico</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($pagos_dia as $p): ?>
          <tr>
            <td><strong style="color:var(--gold)"><?= htmlspecialchars($p['PAGO'] ?? '') ?></strong></td>
            <td><span class="badge-turno"><?= htmlspecialchars($p['TURNO'] ?? '') ?></span></td>
            <td><?= htmlspecialchars($p['TERMINAL'] ?? '') ?></td>
            <td><?= htmlspecialchars($p['PROVEEDOR'] ?? '') ?></td>
            <td><strong>$<?= number_format((float)($p['CANTIDAD'] ?? 0), 2) ?></strong></td>
            <td style="font-size:0.83rem;"><?= htmlspecialchars($p['NIVEL'] ?? '') ?></td>
            <td><?= htmlspecialchars($p['CLIENTE'] ?? '') ?></td>
            <td style="font-family:monospace;font-size:0.85rem;"><?= htmlspecialchars(substr($p['HORA'] ?? '', 0, 5)) ?></td>
            <td style="font-size:0.88rem;"><?= htmlspecialchars($p['TECNICO'] ?? '') ?></td>
          </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </div>
  <?php elseif ($dia_sel): ?>
    <p class="sin-archivo">Sin registros para este día.</p>
  <?php endif; ?>

  <!-- PAGOS MANUALES DE OFICINA (NUEVO) -->
  <?php if ($dia_sel && !empty($pagos_manuales_dia)): ?>
  <div class="form-section" style="margin-top:20px;">
    <h3 style="margin-bottom:10px;">
      📋 Capturados manualmente en oficina
      <span style="font-size:0.7rem;color:var(--text-muted);font-weight:400;letter-spacing:1px;">
        (solo lectura — no pasaron por sala, temporal mientras se adopta la app)
      </span>
    </h3>
    <div class="table-wrap">
      <table>
        <thead>
          <tr>
            <th>Pago</th><th>Turno</th><th>Asset</th><th>Cliente</th>
            <th>Cantidad</th><th>Nivel</th><th>Hora</th><th>Técnico</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($pagos_manuales_dia as $p): ?>
          <tr style="opacity:0.75;">
            <td><strong style="color:var(--gold);"><?= htmlspecialchars((string)($p['pago'] ?? '—')) ?></strong></td>
            <td><span class="badge-turno"><?= htmlspecialchars((string)($p['turno'] ?? '')) ?></span></td>
            <td><?= htmlspecialchars((string)($p['asset'] ?? '')) ?></td>
            <td><?= htmlspecialchars((string)($p['cliente'] ?? '')) ?></td>
            <td><strong>$<?= number_format((float)($p['cantidad'] ?? 0), 2) ?></strong></td>
            <td style="font-size:0.83rem;"><?= htmlspecialchars((string)($p['nivel'] ?? '')) ?></td>
            <td style="font-family:monospace;font-size:0.85rem;"><?= htmlspecialchars(substr((string)($p['hora'] ?? ''), 0, 5)) ?></td>
            <td style="font-size:0.88rem;"><?= htmlspecialchars((string)($p['tecnico'] ?? '')) ?></td>
          </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </div>
  <?php endif; ?>

</div>

<script>
function updateClock() {
  document.getElementById('clock').textContent = new Date().toLocaleTimeString('es-MX');
}
setInterval(updateClock, 1000); updateClock();
</script>
</body>
</html>
