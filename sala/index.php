<?php
require 'db.php';
require_once 'fecha_helpers.php'; // fechaOperativa() — la fecha real del registro debe respetar el corte de 4am/2am

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $turno       = mysqli_real_escape_string($conex, $_POST['turno'] ?? '');
    $asset       = mysqli_real_escape_string($conex, $_POST['asset'] ?? '');
    $modelo      = mysqli_real_escape_string($conex, $_POST['modelo'] ?? '');
    $numero_serie = mysqli_real_escape_string($conex, $_POST['numero_serie'] ?? '');
    $proveedor   = mysqli_real_escape_string($conex, $_POST['proveedor'] ?? '');
    $cantidad    = floatval($_POST['cantidad'] ?? 0);
    $apuesta     = floatval($_POST['apuesta'] ?? 0);
    $nivel       = mysqli_real_escape_string($conex, $_POST['nivel'] ?? '');
    $cliente     = mysqli_real_escape_string($conex, mb_strtoupper(trim($_POST['cliente'] ?? ''), 'UTF-8'));
    $id_cliente  = mysqli_real_escape_string($conex, mb_strtoupper(trim($_POST['id_cliente'] ?? ''), 'UTF-8'));
    $tecnico     = mysqli_real_escape_string($conex, $_POST['tecnico'] ?? '');
    $gerente     = mysqli_real_escape_string($conex, $_POST['gerente'] ?? '');
    $fecha       = fechaOperativa(); // antes: date('Y-m-d') — causaba pagos "huérfanos" después de medianoche
    $hora        = date('H:i:s');
    $unidad      = UNIDAD;

    // Número de pago: siguiente en este turno hoy
    $res = mysqli_fetch_assoc(mysqli_query($conex,
        "SELECT COALESCE(MAX(pago_num),0)+1 AS n FROM pagos WHERE fecha='$fecha'"
    ));
    $pago_num = (int)$res['n'];

    mysqli_query($conex,
        "INSERT INTO pagos (pago_num,turno,asset,modelo,numero_serie,unidad,cantidad,proveedor,nivel,cliente,id_cliente,apuesta,fecha,hora,tecnico,gerente)
         VALUES ('$pago_num','$turno','$asset','$modelo','$numero_serie','$unidad','$cantidad','$proveedor','$nivel','$cliente','$id_cliente','$apuesta','$fecha','$hora','$tecnico','$gerente')"
    );
    $id = mysqli_insert_id($conex);
    header("Location: imprimir.php?id=$id");
    exit;
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Registro de Pagos — Sala</title>
  <link rel="stylesheet" href="styles.css">
</head>
<body>

<div class="header">
  <h1>Registro de Pagos</h1>
  <span class="live-time" id="clock"></span>
</div>
<nav class="nav">
  <a href="index.php" class="active">Registrar</a>
  <a href="lista.php">Consultar</a>
  <a href="historico.php">Histórico</a>
  <a href="cerrar_dia.php">Cerrar Día</a>
  <a href="panel/login.php">Panel</a>
</nav>

<div class="container">
  <?php include 'aviso_push_pendiente.php'; ?>

  <form method="post" id="pagoForm" autocomplete="off">

    <!-- TURNO -->
    <div class="form-section">
      <h3>Turno</h3>
      <div class="turno-selector">
        <button type="button" class="turno-btn" data-turno="A">A</button>
        <button type="button" class="turno-btn" data-turno="B">B</button>
        <button type="button" class="turno-btn" data-turno="C">C</button>
      </div>
      <input type="hidden" name="turno" id="turno_hidden">
    </div>

    <!-- TERMINAL -->
    <div class="form-section">
      <h3>Terminal</h3>
      <div class="form-group">
        <label>Asset</label>
        <input type="number" id="asset_input" placeholder="Número de asset..." min="0">
        <input type="hidden" name="asset"         id="asset_hidden">
        <input type="hidden" name="modelo"        id="modelo_hidden">
        <input type="hidden" name="numero_serie"  id="serie_hidden">
        <input type="hidden" name="proveedor"     id="proveedor_hidden">
        <div class="autocomplete-info" id="maquina_info">
          <div class="full-width">
            <span>Proveedor</span>
            <strong id="info_proveedor">—</strong>
          </div>
        </div>
      </div>
    </div>

    <!-- PAGO -->
    <div class="form-section">
      <h3>Datos del Pago</h3>
      <div class="form-row">
        <div class="form-group">
          <label>Cantidad ($)</label>
          <input type="number" name="cantidad" step="0.01" min="0" placeholder="0.00" required>
        </div>
        <div class="form-group">
          <label>Apuesta ($)</label>
          <input type="number" name="apuesta" step="0.01" min="0" placeholder="0.00" required>
        </div>
      </div>
      <div class="form-group">
        <label>Nivel del Cliente</label>
        <select name="nivel" required>
          <option value="">Selecciona nivel</option>
          <?php foreach(['FOLIATTI FAN','TREBOL','CORAZON','CORAZON PLUS','ESPADA','ESPADA PLUS','DIAMANTE','DIAMANTE PLUS'] as $n): ?>
          <option value="<?= $n ?>"><?= $n ?></option>
          <?php endforeach; ?>
        </select>
      </div>
    </div>

    <!-- CLIENTE -->
    <div class="form-section">
      <h3>Datos del Cliente</h3>
      <div class="form-group">
        <label>Nombre</label>
        <input type="text" name="cliente" placeholder="Nombre completo" required style="text-transform:uppercase;">
      </div>
      <div class="form-group">
        <label>ID del Cliente</label>
        <input type="text" name="id_cliente" placeholder="ID" required style="text-transform:uppercase;">
      </div>
    </div>

    <!-- PERSONAL -->
    <div class="form-section">
      <h3>Personal</h3>
      <div class="form-row">
        <div class="form-group">
          <label>Técnico</label>
          <select name="tecnico" required>
            <option value="">Selecciona técnico</option>
            <?php foreach(['TECNICO 1','TECNICO 2','EVANDHER ZARCO'] as $t): ?>
            <option value="<?= $t ?>"><?= $t ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="form-group">
          <label>Gerente</label>
          <select name="gerente" required>
            <option value="">Selecciona gerente</option>
            <?php foreach(['KOSBY HERNANDEZ','GERENTE 2','GERENTE 3'] as $g): ?>
            <option value="<?= $g ?>"><?= $g ?></option>
            <?php endforeach; ?>
          </select>
        </div>
      </div>
    </div>

    <button type="submit" class="btn-submit">✓ Registrar Pago</button>
  </form>
</div>

<script>
// Reloj
function updateClock() {
  document.getElementById('clock').textContent =
    new Date().toLocaleTimeString('es-MX');
}
setInterval(updateClock, 1000);
updateClock();

// Turno
document.querySelectorAll('.turno-btn').forEach(btn => {
  btn.addEventListener('click', function () {
    document.querySelectorAll('.turno-btn').forEach(b => b.classList.remove('selected'));
    this.classList.add('selected');
    document.getElementById('turno_hidden').value = this.dataset.turno;
  });
});

// Autocomplete asset
let timer;
document.getElementById('asset_input').addEventListener('input', function () {
  clearTimeout(timer);
  const val = this.value.trim();
  document.getElementById('asset_hidden').value = val;
  if (val.length < 3) {
    document.getElementById('maquina_info').classList.remove('visible');
    return;
  }
  timer = setTimeout(() => {
    fetch(`get_maquina.php?asset=${encodeURIComponent(val)}`)
      .then(r => r.json())
      .then(data => {
        document.getElementById('modelo_hidden').value    = data.modelo        || '';
        document.getElementById('serie_hidden').value     = data.numero_serie  || '';
        document.getElementById('proveedor_hidden').value = data.proveedor     || '';
        const info = document.getElementById('maquina_info');
        if (data.modelo) {
          document.getElementById('info_proveedor').textContent = data.proveedor || '—';
          info.classList.add('visible');
        } else {
          info.classList.remove('visible');
        }
      });
  }, 400);
});

// Validación antes de enviar
document.getElementById('pagoForm').addEventListener('submit', function (e) {
  if (!document.getElementById('turno_hidden').value) {
    e.preventDefault();
    alert('Selecciona el turno antes de continuar.');
    return;
  }
  if (!document.getElementById('asset_hidden').value) {
    e.preventDefault();
    alert('Ingresa el asset de la terminal.');
    return;
  }
});
</script>
</body>
</html>
