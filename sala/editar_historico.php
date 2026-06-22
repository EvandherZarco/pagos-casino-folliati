<?php
require 'db.php';
require 'renumerar.php';

$id = (int)($_GET['id'] ?? 0);
if (!$id) { header('Location: historico.php'); exit; }

$meses_es = ['','enero','febrero','marzo','abril','mayo','junio',
             'julio','agosto','septiembre','octubre','noviembre','diciembre'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $turno        = mysqli_real_escape_string($conex, $_POST['turno'] ?? '');
    $asset        = mysqli_real_escape_string($conex, $_POST['asset'] ?? '');
    $modelo       = mysqli_real_escape_string($conex, $_POST['modelo'] ?? '');
    $numero_serie = mysqli_real_escape_string($conex, $_POST['numero_serie'] ?? '');
    $proveedor    = mysqli_real_escape_string($conex, $_POST['proveedor'] ?? '');
    $cantidad     = floatval($_POST['cantidad'] ?? 0);
    $apuesta      = floatval($_POST['apuesta'] ?? 0);
    $nivel        = mysqli_real_escape_string($conex, $_POST['nivel'] ?? '');
    $cliente      = mysqli_real_escape_string($conex, $_POST['cliente'] ?? '');
    $id_cliente   = mysqli_real_escape_string($conex, $_POST['id_cliente'] ?? '');
    $tecnico      = mysqli_real_escape_string($conex, $_POST['tecnico'] ?? '');
    $gerente      = mysqli_real_escape_string($conex, $_POST['gerente'] ?? '');

    $hora = mysqli_real_escape_string(\$conex, \$_POST['hora'] ?? '');
    mysqli_query(\$conex,
        "UPDATE pagos SET turno='\$turno', asset='\$asset', modelo='\$modelo',
         numero_serie='\$numero_serie', proveedor='\$proveedor', cantidad='\$cantidad',
         apuesta='\$apuesta', nivel='\$nivel', cliente='\$cliente',
         id_cliente='\$id_cliente', tecnico='\$tecnico', gerente='\$gerente', hora='\$hora'
         WHERE id=\$id"
    );

    // Regenerar el XLS del mes correspondiente
    $r2 = mysqli_fetch_assoc(mysqli_query($conex, "SELECT fecha FROM pagos WHERE id=$id"));
    if ($r2) {
        $mes_num  = (int)date('n', strtotime($r2['fecha']));
        $anio     = (int)date('Y', strtotime($r2['fecha']));
        $ruta     = 'C:/Users/PITSTAND/Desktop/PAGOS POR MES/pagos-' . $meses_es[$mes_num] . "-$anio.xls";
        regenerarXLS($conex, $anio, $mes_num, $ruta);
    }

    // Volver al histórico del día correspondiente
    $fecha = $r2['fecha'] ?? date('Y-m-d');
    $mes   = (int)date('n', strtotime($fecha));
    $anio2 = (int)date('Y', strtotime($fecha));
    $dia   = (int)date('j', strtotime($fecha));
    renumerar_dia($conex, $fecha);
    header("Location: historico.php?mes=$mes&anio=$anio2&dia=$dia");
    exit;
}

function regenerarXLS($conex, $anio, $mes_num, $ruta) {
    $dias_res = mysqli_query($conex,
        "SELECT DISTINCT fecha FROM pagos
         WHERE YEAR(fecha)=$anio AND MONTH(fecha)=$mes_num AND cerrado=1
         ORDER BY fecha ASC"
    );

    function xe($s) {
        return htmlspecialchars(str_replace(["\r","\n"], ' ', (string)$s), ENT_XML1);
    }

    $worksheets = '';
    while ($row = mysqli_fetch_assoc($dias_res)) {
        $fecha     = $row['fecha'];
        $dia_num   = (int)date('j', strtotime($fecha));
        $fecha_fmt = date('d/m/Y', strtotime($fecha));

        $pagos = mysqli_query($conex,
            "SELECT * FROM pagos WHERE fecha='$fecha' AND cerrado=1 ORDER BY turno, pago_num"
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

    if (!$worksheets) return false;

    $xml  = '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
    $xml .= '<?mso-application progid="Excel.Sheet"?>' . "\n";
    $xml .= '<Workbook xmlns="urn:schemas-microsoft-com:office:spreadsheet" xmlns:ss="urn:schemas-microsoft-com:office:spreadsheet">' . "\n";
    $xml .= '<Styles><Style ss:ID="h"><Font ss:Bold="1"/><Interior ss:Color="#FFFF00" ss:Pattern="Solid"/></Style></Styles>' . "\n";
    $xml .= $worksheets . '</Workbook>';

    return file_put_contents($ruta, $xml) !== false;
}

$r = mysqli_fetch_assoc(mysqli_query($conex, "SELECT * FROM pagos WHERE id=$id AND cerrado=1"));
if (!$r) { header('Location: historico.php'); exit; }
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Editar Pago Histórico</title>
  <link rel="stylesheet" href="styles.css">
</head>
<body>

<div class="header">
  <h1>Editar Pago Histórico</h1>
  <span class="live-time" id="clock"></span>
</div>
<nav class="nav">
  <a href="index.php">Registrar</a>
  <a href="lista.php">Consultar</a>
  <a href="historico.php" class="active">Histórico</a>
  <a href="cerrar_dia.php">Cerrar Día</a>
</nav>

<div class="container">

  <div class="form-section" style="margin-bottom:16px;padding:12px 20px;">
    <p style="font-size:0.85rem;color:var(--text-muted);">
      Editando <strong style="color:var(--gold);">Pago #<?= $r['pago_num'] ?> — Turno <?= $r['turno'] ?></strong>
      del <strong style="color:var(--gold);"><?= date('d/m/Y', strtotime($r['fecha'])) ?></strong>.
      Al guardar se actualizará el XLS del mes automáticamente.
    </p>
  </div>

  <form method="post" autocomplete="off">

    <div class="form-section">
      <h3>Turno</h3>
      <div class="turno-selector">
        <?php foreach(['A','B','C'] as $t): ?>
        <button type="button" class="turno-btn <?= $r['turno']===$t ? 'selected':'' ?>" data-turno="<?=$t?>"><?=$t?></button>
        <?php endforeach; ?>
      </div>
      <input type="hidden" name="turno" id="turno_hidden" value="<?= $r['turno'] ?>">
    </div>

    <div class="form-section">
      <h3>Terminal</h3>
      <div class="form-group">
        <label>Asset</label>
        <input type="number" id="asset_input" value="<?= htmlspecialchars($r['asset']) ?>" min="0">
        <input type="hidden" name="asset"        id="asset_hidden"     value="<?= htmlspecialchars($r['asset']) ?>">
        <input type="hidden" name="modelo"       id="modelo_hidden"    value="<?= htmlspecialchars($r['modelo']) ?>">
        <input type="hidden" name="numero_serie" id="serie_hidden"     value="<?= htmlspecialchars($r['numero_serie']) ?>">
        <input type="hidden" name="proveedor"    id="proveedor_hidden" value="<?= htmlspecialchars($r['proveedor']) ?>">
        <div class="autocomplete-info <?= $r['modelo'] ? 'visible':'' ?>" id="maquina_info">
          <div class="full-width"><span>Proveedor</span><strong id="info_proveedor"><?= htmlspecialchars($r['proveedor'] ?: '—') ?></strong></div>
        </div>
      </div>
    </div>

    <div class="form-section">
      <h3>Datos del Pago</h3>
      <div class="form-row">
        <div class="form-group">
          <label>Cantidad ($)</label>
          <input type="number" name="cantidad" step="0.01" min="0" value="<?= $r['cantidad'] ?>" required>
        </div>
        <div class="form-group">
          <label>Apuesta ($)</label>
          <input type="number" name="apuesta" step="0.01" min="0" value="<?= $r['apuesta'] ?>" required>
        </div>
      </div>
      <div class="form-group">
        <label>Nivel del Cliente</label>
        <select name="nivel" required>
          <option value="">Selecciona nivel</option>
          <?php foreach(['FOLIATTI FAN','TREBOL','CORAZON','CORAZON PLUS','ESPADA','ESPADA PLUS','DIAMANTE','DIAMANTE PLUS'] as $n): ?>
          <option value="<?=$n?>" <?= $r['nivel']===$n ? 'selected':'' ?>><?=$n?></option>
          <?php endforeach; ?>
        </select>
      </div>
    </div>

    <div class="form-section">
      <h3>Datos del Cliente</h3>
      <div class="form-group">
        <label>Nombre</label>
        <input type="text" name="cliente" value="<?= htmlspecialchars($r['cliente']) ?>" required>
      </div>
      <div class="form-group">
        <label>ID del Cliente</label>
        <input type="text" name="id_cliente" value="<?= htmlspecialchars($r['id_cliente']) ?>" required>
      </div>
    </div>

    <div class="form-section">
      <h3>Hora del Pago</h3>
      <div class="form-group">
        <label>Hora (HH:MM:SS)</label>
        <input type="time" name="hora" step="1" value="<?= substr($r['hora'],0,8) ?>" required style="color-scheme:dark;">
      </div>
    </div>

    <div class="form-section">
      <h3>Personal</h3>
      <div class="form-row">
        <div class="form-group">
          <label>Técnico</label>
          <select name="tecnico" required>
            <option value="">Selecciona técnico</option>
            <?php foreach(['TECNICO 1','TECNICO 2','EVANDHER ZARCO'] as $t): ?>
            <option value="<?=$t?>" <?= $r['tecnico']===$t ? 'selected':'' ?>><?=$t?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="form-group">
          <label>Gerente</label>
          <select name="gerente" required>
            <option value="">Selecciona gerente</option>
            <?php foreach(['KOSBY HERNANDEZ','GERENTE 2','GERENTE 3'] as $g): ?>
            <option value="<?=$g?>" <?= $r['gerente']===$g ? 'selected':'' ?>><?=$g?></option>
            <?php endforeach; ?>
          </select>
        </div>
      </div>
    </div>

    <button type="submit" class="btn-submit">✓ Guardar y Actualizar XLS</button>
  </form>
</div>

<script>
function updateClock() {
  document.getElementById('clock').textContent = new Date().toLocaleTimeString('es-MX');
}
setInterval(updateClock, 1000); updateClock();

document.querySelectorAll('.turno-btn').forEach(btn => {
  btn.addEventListener('click', function() {
    document.querySelectorAll('.turno-btn').forEach(b => b.classList.remove('selected'));
    this.classList.add('selected');
    document.getElementById('turno_hidden').value = this.dataset.turno;
  });
});

let timer;
document.getElementById('asset_input').addEventListener('input', function() {
  clearTimeout(timer);
  const val = this.value.trim();
  document.getElementById('asset_hidden').value = val;
  if (val.length < 3) { document.getElementById('maquina_info').classList.remove('visible'); return; }
  timer = setTimeout(() => {
    fetch(`get_maquina.php?asset=${encodeURIComponent(val)}`)
      .then(r => r.json())
      .then(data => {
        document.getElementById('modelo_hidden').value    = data.modelo       || '';
        document.getElementById('serie_hidden').value     = data.numero_serie || '';
        document.getElementById('proveedor_hidden').value = data.proveedor    || '';
        const info = document.getElementById('maquina_info');
        if (data.modelo) {
          document.getElementById('info_proveedor').textContent = data.proveedor    || '—';
          info.classList.add('visible');
        } else {
          info.classList.remove('visible');
        }
      });
  }, 400);
});
</script>
</body>
</html>
