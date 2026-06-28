<?php
require 'db.php';
require_once 'push_helpers.php'; // consultarPagosManualesOficina() — temporal, mientras dure la captura manual en oficina
require_once 'fecha_helpers.php'; // fechaOperativa() — IMPORTANTE: si se cambia la regla horaria, cambiar también en importar_sala.php (oficina)

$meses_es = ['','Enero','Febrero','Marzo','Abril','Mayo','Junio',
             'Julio','Agosto','Septiembre','Octubre','Noviembre','Diciembre'];

$_fecha_op = fechaOperativa();
$mes_sel  = (int)($_GET['mes']  ?? date('n', strtotime($_fecha_op)));
$anio_sel = (int)($_GET['anio'] ?? date('Y', strtotime($_fecha_op)));
$dia_sel  = (int)($_GET['dia']  ?? 0);

// --- BD ---
$dias_res = mysqli_query($conex,
    "SELECT DISTINCT fecha, COUNT(*) as pagos, SUM(cantidad) as total
     FROM pagos WHERE YEAR(fecha)=$anio_sel AND MONTH(fecha)=$mes_sel
     GROUP BY fecha ORDER BY fecha ASC"
);
$dias_bd = [];
while ($r = mysqli_fetch_assoc($dias_res)) $dias_bd[] = $r;

// --- Pagos del día ---
$pagos_dia        = [];
$total_dia        = 0;
$pagos_manuales_dia = [];
$total_manual_dia = 0;

if ($dia_sel) {
    $fecha_sel = sprintf('%04d-%02d-%02d', $anio_sel, $mes_sel, $dia_sel);

    $res = mysqli_query($conex,
        "SELECT * FROM pagos WHERE fecha='$fecha_sel' ORDER BY turno, pago_num"
    );
    while ($r = mysqli_fetch_assoc($res)) {
        $pagos_dia[] = [
            'PAGO'      => $r['pago_num'],
            'TURNO'     => $r['turno'],
            'TERMINAL'  => $r['asset'],
            'PROVEEDOR' => $r['proveedor'],
            'CANTIDAD'  => $r['cantidad'],
            'NIVEL'     => $r['nivel'],
            'CLIENTE'   => $r['cliente'],
            'HORA'      => substr($r['hora'], 0, 5),
            'TECNICO'   => $r['tecnico'],
            'GERENTE'   => $r['gerente'],
        ];
    }
    $total_dia = array_sum(array_column($pagos_dia, 'CANTIDAD'));

    $pagos_manuales_dia = consultarPagosManualesOficina($fecha_sel);
    $total_manual_dia   = array_sum(array_column($pagos_manuales_dia, 'cantidad'));
}

$total_combinado_dia = (float)$total_dia + (float)$total_manual_dia;
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Histórico de Pagos</title>
  <link rel="stylesheet" href="styles.css">
  <style>
    .filter-card { background:rgba(255,255,255,0.06);border:1px solid rgba(255,255,255,0.12);border-radius:var(--radius);padding:20px;margin-bottom:20px; }
    .filter-card input,
    .filter-card input[type="number"],
    .filter-row input[type="number"] {
      background: rgba(255,255,255,0.08) !important;
      border: 1px solid rgba(255,255,255,0.15) !important;
      color: #ffffff !important;
      -webkit-appearance: auto;
    }
    .filter-card h3 { font-family:'Barlow Condensed',sans-serif;font-size:0.78rem;letter-spacing:3px;text-transform:uppercase;color:rgba(255,255,255,0.5);margin-bottom:14px; }
    .filter-row { display:flex;gap:10px;align-items:center;flex-wrap:wrap; }
    .filter-row select, .filter-row input[type="number"] { background:var(--input-bg);border:1px solid var(--border);color:#ffffff;padding:8px 12px;border-radius:var(--radius);font-family:inherit;font-size:0.95rem; }
    .dias-grid { display:grid;grid-template-columns:repeat(auto-fill,minmax(140px,1fr));gap:10px;margin-bottom:20px; }
    .dia-btn { background:rgba(255,255,255,0.06);border:1px solid rgba(255,255,255,0.12);border-radius:var(--radius);padding:14px;text-align:center;cursor:pointer;text-decoration:none;display:block;transition:all 0.18s; }
    .dia-btn:hover, .dia-btn.active { border-color:var(--gold);background:rgba(212,160,23,0.1); }
    .dia-btn .dia-num { font-family:'Barlow Condensed',sans-serif;font-size:1.8rem;font-weight:700;color:var(--gold);display:block; }
    .dia-btn .dia-info { font-size:0.75rem;color:var(--text-muted);margin-top:4px; }
    .sin-dias { text-align:center;color:var(--text-muted);padding:40px;font-size:0.95rem; }
    .pagos-header { display:flex;justify-content:space-between;align-items:center;margin-bottom:14px; }
    .pagos-header h2 { font-family:'Barlow Condensed',sans-serif;font-size:1.3rem;color:var(--gold); }
    .total-badge { background:rgba(212,160,23,0.15);color:var(--gold);padding:6px 16px;border-radius:var(--radius);font-family:'IBM Plex Mono',monospace;font-size:0.95rem; }
    .fuente-badge { font-size:0.72rem;letter-spacing:1px;text-transform:uppercase;padding:3px 10px;border-radius:4px;margin-left:10px; }
    .fuente-bd { background:rgba(46,204,113,0.15);color:#2ecc71; }
  </style>
</head>
<body>

<div class="header">
  <h1>Histórico de Pagos</h1>
  <span class="live-time" id="clock"></span>
</div>
<nav class="nav">
  <a href="index.php">Registrar</a>
  <a href="lista.php">Consultar</a>
  <a href="historico.php" class="active">Histórico</a>
  <a href="exportar.php">Exportar</a>
  <a href="panel/login.php">Panel</a>
</nav>

<div class="container" style="max-width:960px;">

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

  <div class="form-section" style="margin-bottom:20px;">
    <div style="font-family:'Barlow Condensed',sans-serif;font-size:0.78rem;letter-spacing:3px;text-transform:uppercase;color:rgba(255,255,255,0.5);margin-bottom:14px;padding-bottom:8px;border-bottom:1px solid var(--border);">
      <?= $meses_es[$mes_sel] ?> <?= $anio_sel ?>
      <?php if (!empty($dias_bd)): ?>
        <span class="fuente-badge fuente-bd">Base de datos</span>
      <?php endif; ?>
    </div>

    <?php if (empty($dias_bd)): ?>
      <p class="sin-dias">Sin registros para este mes.</p>
    <?php else: ?>
      <div class="dias-grid">
        <?php foreach ($dias_bd as $d):
          $d_num = (int)date('j', strtotime($d['fecha']));
          $activo = $dia_sel === $d_num ? 'active' : '';
        ?>
        <a href="?mes=<?=$mes_sel?>&anio=<?=$anio_sel?>&dia=<?=$d_num?>" class="dia-btn <?=$activo?>">
          <span class="dia-num"><?=$d_num?></span>
          <span class="dia-info"><?=$d['pagos']?> pago(s)<br>$<?=number_format((float)$d['total'],2)?></span>
        </a>
        <?php endforeach; ?>
      </div>
    <?php endif; ?>
  </div>

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
            <td><strong style="color:var(--gold)"><?= htmlspecialchars((string)($p['PAGO'] ?? '')) ?></strong></td>
            <td><span class="badge-turno"><?= htmlspecialchars((string)($p['TURNO'] ?? '')) ?></span></td>
            <td><?= htmlspecialchars((string)($p['TERMINAL'] ?? '')) ?></td>
            <td><?= htmlspecialchars((string)($p['PROVEEDOR'] ?? '')) ?></td>
            <td><strong>$<?= number_format((float)($p['CANTIDAD'] ?? 0), 2) ?></strong></td>
            <td style="font-size:0.83rem;"><?= htmlspecialchars((string)($p['NIVEL'] ?? '')) ?></td>
            <td><?= htmlspecialchars((string)($p['CLIENTE'] ?? '')) ?></td>
            <td style="font-family:monospace;font-size:0.85rem;"><?= htmlspecialchars(substr((string)($p['HORA'] ?? ''), 0, 5)) ?></td>
            <td style="font-size:0.88rem;"><?= htmlspecialchars((string)($p['TECNICO'] ?? '')) ?></td>
          </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </div>
  <?php elseif ($dia_sel): ?>
    <p class="sin-dias">Sin registros para este día.</p>
  <?php endif; ?>

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
