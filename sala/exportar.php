<?php
require 'db.php';
require_once 'fecha_helpers.php';
require_once 'xls_helpers.php';

$meses_es = ['','ENERO','FEBRERO','MARZO','ABRIL','MAYO','JUNIO',
             'JULIO','AGOSTO','SEPTIEMBRE','OCTUBRE','NOVIEMBRE','DICIEMBRE'];

$hoy     = fechaOperativa();
$mes_num = (int) date('n', strtotime($hoy));
$anio    = (int) date('Y', strtotime($hoy));

$modo      = $_POST['modo'] ?? 'mes';
$sel_fecha = $_POST['fecha'] ?? $hoy;
$sel_mes   = (int)($_POST['mes']  ?? $mes_num);
$sel_anio  = (int)($_POST['anio'] ?? $anio);

// Si se pidió exportar, generar XLS en memoria y mandar al navegador
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['exportar'])) {

    if ($modo === 'dia') {
        $d_mes  = (int) date('n', strtotime($sel_fecha));
        $d_anio = (int) date('Y', strtotime($sel_fecha));
    } else {
        $d_mes  = $sel_mes;
        $d_anio = $sel_anio;
    }

    $nombre = "PAGOS-{$meses_es[$d_mes]}-{$d_anio}.xls";
    $ruta   = sys_get_temp_dir() . DIRECTORY_SEPARATOR . $nombre;

    if (generarXLSMes($conex, $d_anio, $d_mes, $ruta)) {
        // Mandar directo al navegador como descarga
        header('Content-Type: application/vnd.ms-excel');
        header('Content-Disposition: attachment; filename="' . $nombre . '"');
        header('Content-Length: ' . filesize($ruta));
        header('Cache-Control: no-cache');
        readfile($ruta);
        unlink($ruta); // limpiar archivo temporal
        exit;
    } else {
        $error = "No hay registros para el período seleccionado.";
    }
}

// Rango de años disponibles
$res_anio = mysqli_fetch_assoc(mysqli_query($conex, "SELECT MIN(YEAR(fecha)) as min_anio FROM pagos"));
$anio_min = (int)($res_anio['min_anio'] ?? $anio);
$anios    = range($anio_min, $anio);
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Exportar Pagos</title>
  <link rel="stylesheet" href="styles.css">
  <style>
    .modo-selector {
      display: flex;
      gap: 10px;
      margin-bottom: 20px;
    }
    .modo-btn {
      flex: 1;
      padding: 12px;
      background: rgba(255,255,255,0.06);
      border: 1px solid rgba(255,255,255,0.15);
      color: rgba(255,255,255,0.7);
      border-radius: var(--radius);
      font-family: 'Barlow Condensed', sans-serif;
      font-size: 1rem;
      letter-spacing: 1px;
      text-transform: uppercase;
      cursor: pointer;
      transition: all 0.2s;
    }
    .modo-btn.active {
      background: var(--green);
      border-color: var(--green);
      color: white;
    }
    .campo-dia { display: none; }
    .campo-mes { display: none; }
    .campo-dia.visible { display: block; }
    .campo-mes.visible { display: block; }
    .alert-error {
      background: rgba(231,76,60,0.1);
      border: 1px solid #e74c3c;
      color: #e74c3c;
      border-radius: var(--radius);
      padding: 20px;
      text-align: center;
      margin-bottom: 20px;
    }
  </style>
</head>
<body>

<div class="header">
  <h1>Exportar Pagos</h1>
  <span class="live-time" id="clock"></span>
</div>
<nav class="nav">
  <a href="index.php">Registrar</a>
  <a href="lista.php">Consultar</a>
  <a href="historico.php">Histórico</a>
  <a href="exportar.php" class="active">Exportar</a>
  <a href="panel/login.php">Panel</a>
</nav>

<div class="container">

  <?php if (!empty($error)): ?>
  <div class="alert-error">⚠️ <?= $error ?></div>
  <?php endif; ?>

  <div class="form-section">
    <h3>Selecciona el período</h3>

    <div class="modo-selector">
      <button type="button" class="modo-btn <?= $modo==='dia'?'active':'' ?>" onclick="setModo('dia')">Por día</button>
      <button type="button" class="modo-btn <?= $modo==='mes'?'active':'' ?>" onclick="setModo('mes')">Por mes</button>
    </div>

    <form method="post" id="exportForm">
      <input type="hidden" name="modo" id="modo_hidden" value="<?= htmlspecialchars($modo) ?>">

      <!-- Campo día -->
      <div class="campo-dia <?= $modo==='dia'?'visible':'' ?>" id="campo_dia">
        <div class="form-group">
          <label>Fecha</label>
          <input type="date" name="fecha" value="<?= htmlspecialchars($sel_fecha) ?>">
        </div>
      </div>

      <!-- Campos mes -->
      <div class="campo-mes <?= $modo==='mes'?'visible':'' ?>" id="campo_mes">
        <div class="form-row">
          <div class="form-group">
            <label>Mes</label>
            <select name="mes">
              <?php foreach($meses_es as $i => $m): if($i===0) continue; ?>
              <option value="<?=$i?>" <?=$sel_mes===$i?'selected':''?>><?=$m?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="form-group">
            <label>Año</label>
            <select name="anio">
              <?php foreach(array_reverse($anios) as $a): ?>
              <option value="<?=$a?>" <?=$sel_anio===$a?'selected':''?>><?=$a?></option>
              <?php endforeach; ?>
            </select>
          </div>
        </div>
      </div>

      <button type="submit" name="exportar" class="btn-submit" style="margin-top:16px;">
        ⬇ Descargar XLS
      </button>
    </form>
  </div>

</div>

<script>
function updateClock() {
  document.getElementById('clock').textContent =
    new Date().toLocaleTimeString('es-MX');
}
setInterval(updateClock, 1000);
updateClock();

function setModo(modo) {
  document.getElementById('modo_hidden').value = modo;
  document.querySelectorAll('.modo-btn').forEach(b => b.classList.remove('active'));
  event.target.classList.add('active');
  document.getElementById('campo_dia').classList.toggle('visible', modo === 'dia');
  document.getElementById('campo_mes').classList.toggle('visible', modo === 'mes');
}
</script>
</body>
</html>
