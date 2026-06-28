<?php
date_default_timezone_set('America/Mexico_City');
/* <!-- Desarrollado por Evandher Zarco (ZarcoMecatronics) - Mayo 2026 --> */
require_once __DIR__ . '/../db.php';
require_once __DIR__ . '/../fecha_helpers.php';

define('BASE_PANEL', '/pagos_sala/panel/');
require_once __DIR__ . '/auth.php';
require_panel(); // cualquier rol

$fecha_hoy = fechaOperativa();

// --- Resumen del día ---
$res_dia = mysqli_fetch_assoc(mysqli_query($conex,
    "SELECT COUNT(*) as total_pagos, COALESCE(SUM(cantidad),0) as total_monto
     FROM pagos WHERE fecha='$fecha_hoy'"
));

// --- Estado push_pendiente ---
$pend = mysqli_fetch_assoc(mysqli_query($conex,
    "SELECT
       SUM(estado='pendiente') as pendientes,
       SUM(estado='error')     as errores,
       SUM(estado='enviado')   as enviados
     FROM push_pendiente"
));

// --- Estado push_evento ---
$ev = mysqli_fetch_assoc(mysqli_query($conex,
    "SELECT
       SUM(estado='pendiente') as pendientes,
       SUM(estado='error')     as errores,
       SUM(estado='enviado')   as enviados
     FROM push_evento"
));

// --- Últimos errores ---
$errores_res = mysqli_query($conex,
    "SELECT 'cierre' as tipo, fecha_operativa as ref, intentos, ultimo_intento, ultimo_error
     FROM push_pendiente WHERE estado='error'
     UNION ALL
     SELECT 'evento', CONCAT(tipo,'#',id_sala), intentos, ultimo_intento, ultimo_error
     FROM push_evento WHERE estado='error'
     ORDER BY ultimo_intento DESC LIMIT 10"
);

// --- Última ejecución del reintentar_push (aproximado: último intento en cualquier cola) ---
$ultimo_reintento = mysqli_fetch_assoc(mysqli_query($conex,
    "SELECT MAX(ultimo_intento) as ts FROM (
       SELECT ultimo_intento FROM push_pendiente WHERE ultimo_intento IS NOT NULL
       UNION ALL
       SELECT ultimo_intento FROM push_evento WHERE ultimo_intento IS NOT NULL
     ) t"
));
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Panel — Dashboard</title>
  <link rel="stylesheet" href="../styles.css">
  <link rel="stylesheet" href="../fonts_embedded.css">
  <style>
    .panel-nav { background:rgba(0,0,0,0.3); padding:10px 20px; display:flex; gap:16px; align-items:center; border-bottom:1px solid var(--border); flex-wrap:wrap; }
    .panel-nav a { color:var(--text-muted); text-decoration:none; font-size:0.88rem; padding:6px 12px; border-radius:var(--radius); }
    .panel-nav a:hover, .panel-nav a.active { color:var(--gold); background:rgba(212,160,23,0.1); }
    .panel-nav .usuario { margin-left:auto; font-size:0.8rem; color:var(--text-muted); }
    .panel-nav .logout { color:#e74c3c !important; }

    .cards { display:grid; grid-template-columns:repeat(auto-fill,minmax(200px,1fr)); gap:14px; margin-bottom:24px; }
    .card { background:var(--surface); border:1px solid var(--border); border-radius:var(--radius); padding:20px; }
    .card .card-label { font-size:0.72rem; letter-spacing:2px; text-transform:uppercase; color:var(--text-muted); margin-bottom:8px; }
    .card .card-value { font-family:'Barlow Condensed',sans-serif; font-size:2rem; font-weight:700; }
    .card.ok .card-value  { color:#2ecc71; }
    .card.warn .card-value { color:var(--gold); }
    .card.err .card-value  { color:#e74c3c; }
    .card.neutral .card-value { color:var(--text); }

    .section-title { font-family:'Barlow Condensed',sans-serif; font-size:0.78rem; letter-spacing:3px; text-transform:uppercase; color:var(--text-muted); margin:24px 0 12px; padding-bottom:6px; border-bottom:1px solid var(--border); }

    .pipeline-grid { display:grid; grid-template-columns:1fr 1fr; gap:14px; margin-bottom:24px; }
    .pipeline-card { background:var(--surface); border:1px solid var(--border); border-radius:var(--radius); padding:16px; }
    .pipeline-card h4 { font-size:0.8rem; letter-spacing:1px; text-transform:uppercase; color:var(--text-muted); margin-bottom:12px; }
    .stat-row { display:flex; justify-content:space-between; align-items:center; padding:6px 0; border-bottom:1px solid rgba(255,255,255,0.05); font-size:0.9rem; }
    .stat-row:last-child { border-bottom:none; }
    .badge { padding:2px 10px; border-radius:4px; font-size:0.78rem; font-weight:600; }
    .badge-ok   { background:rgba(46,204,113,0.15); color:#2ecc71; }
    .badge-warn { background:rgba(212,160,23,0.15);  color:var(--gold); }
    .badge-err  { background:rgba(231,76,60,0.15);   color:#e74c3c; }

    .error-list { background:var(--surface); border:1px solid var(--border); border-radius:var(--radius); overflow:hidden; }
    .error-item { padding:12px 16px; border-bottom:1px solid var(--border); font-size:0.85rem; }
    .error-item:last-child { border-bottom:none; }
    .error-item .ref { color:var(--gold); font-weight:600; }
    .error-item .msg { color:#e74c3c; font-size:0.8rem; margin-top:4px; font-family:'IBM Plex Mono',monospace; }
    .error-item .meta { font-size:0.75rem; color:var(--text-muted); margin-top:2px; }
    .sin-errores { text-align:center; padding:30px; color:var(--text-muted); font-size:0.9rem; }

    .btn-reintentar { background:rgba(212,160,23,0.15); color:var(--gold); border:1px solid rgba(212,160,23,0.3); padding:8px 18px; border-radius:var(--radius); font-size:0.85rem; cursor:pointer; text-decoration:none; display:inline-block; margin-bottom:16px; }
    .btn-reintentar:hover { background:rgba(212,160,23,0.25); }
  </style>
</head>
<body>

<div class="header">
  <h1>Panel de Control</h1>
  <span class="live-time" id="clock"></span>
</div>

<div class="panel-nav">
  <a href="index.php" class="active">Dashboard</a>
  <a href="catalogo.php">Colaboradores</a>
  <?php if (es_tecnico()): ?>
  <a href="config.php">Configuración</a>
  <?php endif; ?>
  <a href="logout.php?redir=../lista.php">← Sala</a>
  <span class="usuario">
    <?= htmlspecialchars(panel_usuario()) ?>
    <span style="color:var(--gold);margin-left:4px;">[<?= es_tecnico() ? 'TÉCNICO' : 'GERENTE' ?>]</span>
  </span>
  <a href="logout.php" class="logout">Salir</a>
</div>

<div class="container" style="max-width:960px;">

  <!-- RESUMEN DEL DÍA -->
  <p class="section-title">Día operativo actual — <?= date('d/m/Y', strtotime($fecha_hoy)) ?></p>
  <div class="cards">
    <div class="card neutral">
      <div class="card-label">Pagos registrados</div>
      <div class="card-value"><?= (int)($res_dia['total_pagos'] ?? 0) ?></div>
    </div>
    <div class="card neutral">
      <div class="card-label">Total del día</div>
      <div class="card-value" style="font-size:1.4rem;">$<?= number_format((float)($res_dia['total_monto'] ?? 0), 2) ?></div>
    </div>
  </div>

  <!-- PAGOS DEL DÍA -->
  <p class="section-title">Pagos del día — <?= date('d/m/Y', strtotime($fecha_hoy)) ?></p>
  <?php
  $pagos_res = mysqli_query($conex, "SELECT * FROM pagos WHERE fecha='$fecha_hoy' ORDER BY turno, pago_num");
  $hay_pagos = mysqli_num_rows($pagos_res) > 0;
  ?>
  <?php if (!$hay_pagos): ?>
  <p style="color:var(--text-muted);font-size:0.9rem;margin-bottom:24px;">Sin pagos registrados hoy.</p>
  <?php else: ?>
  <div class="form-section" style="margin-bottom:24px;">
    <div class="table-wrap">
      <table>
        <thead>
          <tr>
            <th>Pago</th><th>Turno</th><th>Asset</th><th>Cliente</th>
            <th>Cantidad</th><th>Nivel</th><th>Hora</th><th>Técnico</th><th></th>
          </tr>
        </thead>
        <tbody>
          <?php while ($p = mysqli_fetch_assoc($pagos_res)): ?>
          <tr>
            <td><strong style="color:var(--gold)"><?= $p['pago_num'] ?></strong></td>
            <td><span class="badge-turno"><?= $p['turno'] ?></span></td>
            <td><?= htmlspecialchars($p['asset']) ?></td>
            <td><?= htmlspecialchars($p['cliente']) ?></td>
            <td><strong>$<?= number_format((float)$p['cantidad'], 2) ?></strong></td>
            <td style="font-size:0.83rem;"><?= htmlspecialchars($p['nivel']) ?></td>
            <td style="font-family:monospace;font-size:0.85rem;"><?= substr($p['hora'],0,5) ?></td>
            <td style="font-size:0.88rem;"><?= htmlspecialchars($p['tecnico']) ?></td>
            <td style="display:flex;gap:6px;">
              <a href="../imprimir.php?id=<?= $p['id'] ?>" class="btn btn-ghost" style="font-size:0.8rem;padding:5px 10px;" title="Imprimir">🖨</a>
              <a href="../editar.php?id=<?= $p['id'] ?>&origen=panel" class="btn btn-ghost" style="font-size:0.8rem;padding:5px 10px;" title="Editar">✏️</a>
              <a href="../eliminar.php?id=<?= $p['id'] ?>&origen=panel"
                 class="btn btn-ghost"
                 style="font-size:0.8rem;padding:5px 10px;color:#e74c3c;border-color:#e74c3c;"
                 title="Eliminar"
                 onclick="return confirm('¿Eliminar este pago?')">🗑️</a>
            </td>
          </tr>
          <?php endwhile; ?>
        </tbody>
      </table>
    </div>
  </div>
  <?php endif; ?>

  <!-- ESTADO DEL PIPELINE -->
  <p class="section-title">Estado del pipeline</p>

  <?php
  $total_problemas = (int)($pend['pendientes'] ?? 0) + (int)($pend['errores'] ?? 0)
                   + (int)($ev['pendientes']   ?? 0) + (int)($ev['errores']   ?? 0);
  if ($total_problemas > 0): ?>
  <a href="reintentar.php" class="btn-reintentar">⟳ Reintentar ahora</a>
  <?php endif; ?>

  <div class="pipeline-grid">
    <div class="pipeline-card">
      <h4>Cierres de día (push_pendiente)</h4>
      <div class="stat-row"><span>Enviados</span><span class="badge badge-ok"><?= (int)($pend['enviados'] ?? 0) ?></span></div>
      <div class="stat-row"><span>Pendientes</span><span class="badge <?= (int)($pend['pendientes']??0)>0 ? 'badge-warn':'badge-ok' ?>"><?= (int)($pend['pendientes'] ?? 0) ?></span></div>
      <div class="stat-row"><span>Con error</span><span class="badge <?= (int)($pend['errores']??0)>0 ? 'badge-err':'badge-ok' ?>"><?= (int)($pend['errores'] ?? 0) ?></span></div>
    </div>
    <div class="pipeline-card">
      <h4>Ediciones / eliminaciones (push_evento)</h4>
      <div class="stat-row"><span>Enviados</span><span class="badge badge-ok"><?= (int)($ev['enviados'] ?? 0) ?></span></div>
      <div class="stat-row"><span>Pendientes</span><span class="badge <?= (int)($ev['pendientes']??0)>0 ? 'badge-warn':'badge-ok' ?>"><?= (int)($ev['pendientes'] ?? 0) ?></span></div>
      <div class="stat-row"><span>Con error</span><span class="badge <?= (int)($ev['errores']??0)>0 ? 'badge-err':'badge-ok' ?>"><?= (int)($ev['errores'] ?? 0) ?></span></div>
    </div>
  </div>

  <?php if ($ultimo_reintento['ts']): ?>
  <p style="font-size:0.78rem;color:var(--text-muted);margin-bottom:20px;">
    Último reintento automático: <strong><?= $ultimo_reintento['ts'] ?></strong>
  </p>
  <?php endif; ?>

  <!-- ÚLTIMOS ERRORES -->
  <p class="section-title">Errores recientes</p>
  <div class="error-list">
    <?php
    $hay_errores = false;
    while ($e = mysqli_fetch_assoc($errores_res)):
      $hay_errores = true;
    ?>
    <div class="error-item">
      <span class="badge badge-err" style="margin-right:8px;"><?= strtoupper($e['tipo']) ?></span>
      <span class="ref"><?= htmlspecialchars($e['ref']) ?></span>
      <span style="margin-left:8px;font-size:0.78rem;color:var(--text-muted);"><?= (int)$e['intentos'] ?> intentos</span>
      <?php if ($e['ultimo_error']): ?>
      <div class="msg"><?= htmlspecialchars(substr($e['ultimo_error'], 0, 200)) ?></div>
      <?php endif; ?>
      <div class="meta">Último intento: <?= $e['ultimo_intento'] ?? '—' ?></div>
    </div>
    <?php endwhile; ?>
    <?php if (!$hay_errores): ?>
    <div class="sin-errores">✓ Sin errores en el pipeline</div>
    <?php endif; ?>
  </div>

</div>

<script>
function updateClock() {
  document.getElementById('clock').textContent = new Date().toLocaleTimeString('es-MX');
}
setInterval(updateClock, 1000); updateClock();
</script>
</body>
</html>
