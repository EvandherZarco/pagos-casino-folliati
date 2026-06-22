<?php
require 'db.php';
require_once 'fecha_helpers.php'; // fechaOperativa() — el filtro por default debe ser el día operativo, no la fecha cruda
require_once 'push_helpers.php'; // consultarPagosManualesOficina() — temporal, mientras dure la captura manual en oficina

$filtro_fecha = mysqli_real_escape_string($conex, $_GET['fecha'] ?? fechaOperativa());
$filtro_turno = mysqli_real_escape_string($conex, $_GET['turno'] ?? '');

// Antes: WHERE fecha='$filtro_fecha' AND cerrado=0 — solo mostraba lo abierto.
// Ahora: se muestra el día completo (cerrados y abiertos), porque ya hay
// soporte para editar/eliminar pagos cerrados con sync a oficina.
$where = "WHERE fecha='$filtro_fecha'";
if ($filtro_turno) $where .= " AND turno='$filtro_turno'";

$pagos = mysqli_query($conex, "SELECT * FROM pagos $where ORDER BY turno, pago_num");
$count = mysqli_num_rows($pagos);

// Totales
$res_total = mysqli_fetch_assoc(mysqli_query($conex,
    "SELECT SUM(cantidad) AS total FROM pagos $where"
));
$total = $res_total['total'] ?? 0;

// Pagos capturados manualmente en oficina (sin id_sala) para esta misma
// fecha — solo lectura, temporal mientras dure la transición.
$pagos_manuales_oficina = consultarPagosManualesOficina($filtro_fecha);

// Conteo y total combinados (sala + manuales de oficina) para las
// tarjetas de arriba — el $count de la tabla principal NO cambia,
// sigue siendo solo lo de sala (para que "Sin registros" funcione igual).
$count_manual = count($pagos_manuales_oficina);
$total_manual = array_sum(array_column($pagos_manuales_oficina, 'cantidad'));
$count_combinado = $count + $count_manual;
$total_combinado = (float)$total + (float)$total_manual;
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Consultar Pagos</title>
  <link rel="stylesheet" href="styles.css">
  <style>
    .table-wrap table { background: transparent; }
    .table-wrap td { background: transparent; color: rgba(255,255,255,0.8); border-bottom-color: rgba(255,255,255,0.08); }
    .table-wrap tr:hover td { background: rgba(255,255,255,0.05); }
    .table-wrap th { background: rgba(0,0,0,0.3); }
    .stats-bar {
      display: flex;
      gap: 16px;
      margin-bottom: 16px;
    }
    .stat-card {
      background: rgba(255,255,255,0.06);
      border: 1px solid rgba(255,255,255,0.12);
      border-radius: var(--radius);
      padding: 14px 20px;
      flex: 1;
    }
    .stat-card .label {
      font-size: 0.72rem;
      letter-spacing: 2px;
      text-transform: uppercase;
      color: var(--text-muted);
    }
    .stat-card .value {
      font-family: 'IBM Plex Mono', monospace;
      font-size: 1.4rem;
      color: var(--gold);
      font-weight: 500;
      margin-top: 4px;
    }
    .filter-bar {
      display: flex;
      gap: 10px;
      align-items: center;
      margin-bottom: 16px;
      flex-wrap: wrap;
    }
    .filter-bar input[type="date"],
    .filter-bar select {
      background: rgba(255,255,255,0.08);
      border: 1px solid rgba(255,255,255,0.15);
      color: #ffffff;
      padding: 8px 12px;
      border-radius: var(--radius);
      font-family: inherit;
      font-size: 0.95rem;
    }
    .badge-cerrado {
      font-size: 0.62rem;
      letter-spacing: 0.5px;
      color: #2ecc71;
      border: 1px solid #2ecc71;
      border-radius: 4px;
      padding: 1px 5px;
      margin-left: 6px;
      vertical-align: middle;
      white-space: nowrap;
    }
  </style>
</head>
<body>

<div class="header">
  <h1>Registro de Pagos</h1>
  <span class="live-time" id="clock"></span>
</div>
<nav class="nav">
  <a href="index.php">Registrar</a>
  <a href="lista.php" class="active">Consultar</a>
  <a href="historico.php">Histórico</a>
  <a href="cerrar_dia.php">Cerrar Día</a>
</nav>

<div class="container" style="max-width:960px;">

  <?php include 'aviso_push_pendiente.php'; ?>

  <!-- STATS -->
  <div class="stats-bar">
    <div class="stat-card">
      <div class="label">Registros</div>
      <div class="value"><?= $count_combinado ?></div>
      <?php if ($count_manual > 0): ?>
      <div style="font-size:0.68rem;color:var(--text-muted);margin-top:2px;">
        <?= $count ?> de sala + <?= $count_manual ?> manuales (oficina)
      </div>
      <?php endif; ?>
    </div>
    <div class="stat-card">
      <div class="label">Total del día</div>
      <div class="value">$<?= number_format($total_combinado, 2) ?></div>
      <?php if ($count_manual > 0): ?>
      <div style="font-size:0.68rem;color:var(--text-muted);margin-top:2px;">
        incluye $<?= number_format((float)$total_manual, 2) ?> de oficina
      </div>
      <?php endif; ?>
    </div>
  </div>

  <!-- FILTROS -->
  <form method="get" class="filter-bar">
    <input type="date" name="fecha" value="<?= htmlspecialchars($filtro_fecha) ?>">
    <select name="turno">
      <option value="">Todos los turnos</option>
      <?php foreach(['A','B','C'] as $t): ?>
      <option value="<?=$t?>" <?= $filtro_turno===$t ? 'selected' : '' ?>><?=$t?></option>
      <?php endforeach; ?>
    </select>
    <button type="submit" class="btn btn-gold">Filtrar</button>
  </form>

  <!-- TABLA -->
  <div class="form-section">
    <div class="table-wrap">
      <table>
        <thead>
          <tr>
            <th>Pago</th>
            <th>Turno</th>
            <th>Asset</th>
            <th>Cliente</th>
            <th>Cantidad</th>
            <th>Nivel</th>
            <th>Hora</th>
            <th>Técnico</th>
            <th></th>
          </tr>
        </thead>
        <tbody>
          <?php if ($count === 0): ?>
          <tr>
            <td colspan="9" style="text-align:center;color:var(--text-muted);padding:32px;">
              Sin registros para esta fecha
            </td>
          </tr>
          <?php else: while ($p = mysqli_fetch_assoc($pagos)): ?>
          <tr>
            <td>
              <strong style="color:var(--gold);"><?= $p['pago_num'] ?></strong>
              <?php if ((int)$p['cerrado'] === 1): ?>
              <span class="badge-cerrado">CERRADO</span>
              <?php endif; ?>
            </td>
            <td><span class="badge-turno"><?= $p['turno'] ?></span></td>
            <td><?= htmlspecialchars($p['asset']) ?></td>
            <td><?= htmlspecialchars($p['cliente']) ?></td>
            <td><strong>$<?= number_format((float)$p['cantidad'], 2) ?></strong></td>
            <td style="font-size:0.83rem;"><?= htmlspecialchars($p['nivel']) ?></td>
            <td style="font-family:monospace;font-size:0.85rem;"><?= substr($p['hora'],0,5) ?></td>
            <td style="font-size:0.88rem;"><?= htmlspecialchars($p['tecnico']) ?></td>
            <td style="display:flex;gap:6px;">
              <a href="imprimir.php?id=<?= $p['id'] ?>" class="btn btn-ghost" style="font-size:0.8rem;padding:5px 10px;" title="Imprimir">🖨</a>
              <a href="editar.php?id=<?= $p['id'] ?>" class="btn btn-ghost" style="font-size:0.8rem;padding:5px 10px;" title="Editar">✏️</a>
              <a href="eliminar.php?id=<?= $p['id'] ?>"
                 class="btn btn-ghost"
                 style="font-size:0.8rem;padding:5px 10px;color:#e74c3c;border-color:#e74c3c;"
                 title="Eliminar"
                 onclick="return confirm('¿Estás seguro de que quieres eliminar este pago?\nEsta acción no se puede deshacer.<?= (int)$p['cerrado'] === 1 ? '\\n\\nEste pago YA está cerrado y sincronizado con oficina — se notificará el borrado allá también.' : '' ?>')">🗑️</a>
            </td>
          </tr>
          <?php endwhile; endif; ?>
        </tbody>
      </table>
    </div>
  </div>

  <?php if (!empty($pagos_manuales_oficina)): ?>
  <div class="form-section" style="margin-top:24px;">
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
            <th>Pago</th>
            <th>Turno</th>
            <th>Asset</th>
            <th>Cliente</th>
            <th>Cantidad</th>
            <th>Nivel</th>
            <th>Hora</th>
            <th>Técnico</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($pagos_manuales_oficina as $p): ?>
          <tr style="opacity:0.75;">
            <td><strong style="color:var(--gold);"><?= htmlspecialchars($p['pago'] ?? '—') ?></strong></td>
            <td><span class="badge-turno"><?= htmlspecialchars($p['turno'] ?? '') ?></span></td>
            <td><?= htmlspecialchars($p['asset'] ?? '') ?></td>
            <td><?= htmlspecialchars($p['cliente'] ?? '') ?></td>
            <td><strong>$<?= number_format((float)($p['cantidad'] ?? 0), 2) ?></strong></td>
            <td style="font-size:0.83rem;"><?= htmlspecialchars($p['nivel'] ?? '') ?></td>
            <td style="font-family:monospace;font-size:0.85rem;"><?= substr($p['hora'] ?? '', 0, 5) ?></td>
            <td style="font-size:0.88rem;"><?= htmlspecialchars($p['tecnico'] ?? '') ?></td>
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
  document.getElementById('clock').textContent =
    new Date().toLocaleTimeString('es-MX');
}
setInterval(updateClock, 1000);
updateClock();
</script>
</body>
</html>
