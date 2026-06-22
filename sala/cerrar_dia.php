<?php
require 'db.php';
require_once 'fecha_helpers.php'; // fechaOperativa() — compartida con index.php, lista.php, historico.php

require_once 'push_helpers.php'; // enviarPushOficina(), guardarPushPendiente()
require_once 'xls_helpers.php';  // generarXLSMes()

$hoy       = fechaOperativa();
$mes_num   = (int) date('n', strtotime($hoy));
$anio      = (int) date('Y', strtotime($hoy));
$meses_es  = ['','ENERO','FEBRERO','MARZO','ABRIL','MAYO','JUNIO','JULIO','AGOSTO','SEPTIEMBRE','OCTUBRE','NOVIEMBRE','DICIEMBRE'];
$mes_nombre = $meses_es[$mes_num];

$ruta_carpeta  = '\\\\10.10.10.132\\pagosmes\\';
$nombre_archivo = "PAGOS-{$mes_nombre}-{$anio}.xls";
$ruta_completa  = $ruta_carpeta . $nombre_archivo;

$exito = false;
$error = '';
$push_status = null; // null = no aplicó, true = éxito, false = falló (guardado en pendiente)

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['cerrar'])) {
    // 1. Recolectar las filas del día ANTES de marcarlas cerradas,
    //    para tener exactamente lo que se va a mandar a oficina.
    $filas_dia = [];
    $pagos_res = mysqli_query($conex, "SELECT * FROM pagos WHERE fecha='$hoy' AND cerrado=0 ORDER BY turno, pago_num");
    while ($p = mysqli_fetch_assoc($pagos_res)) {
        $filas_dia[] = [
            'id_sala'      => $p['id'], // identificador único real (nunca se reutiliza, aunque se borren filas) — usado para dedup
            'pago'         => $p['pago_num'], // folio visual, solo para mostrar/imprimir — NO usar para dedup
            'turno'        => $p['turno'],
            'asset'        => $p['asset'],
            'unidad'       => $p['unidad'],
            'proveedor'    => $p['proveedor'],
            'modelo'       => $p['modelo'],
            'numero_serie' => $p['numero_serie'],
            'cantidad'     => $p['cantidad'],
            'apuesta'      => $p['apuesta'],
            'nivel'        => $p['nivel'],
            'cliente'      => $p['cliente'],
            'tarjeta'      => $p['id_cliente'], // mapeo: id_cliente (sala) -> tarjeta (oficina)
            'fecha'        => $p['fecha'],
            'hora'         => $p['hora'],
            'tecnico'      => $p['tecnico'],
            'gerente'      => $p['gerente'],
        ];
    }

    // 2. Marcar hoy (día operativo) como cerrado
    mysqli_query($conex, "UPDATE pagos SET cerrado=1 WHERE fecha='$hoy' AND cerrado=0");

    // 3. Generar/actualizar el XLS del mes
    if (generarXLSMes($conex, $anio, $mes_num, $ruta_completa)) {
        $exito = true;

        // 4. Push a oficina (solo si hay filas nuevas que mandar)
        if (!empty($filas_dia)) {
            $dia_num_str = (string)(int)date('j', strtotime($hoy));
            $archivo_nombre = "PAGOS-{$mes_nombre}-{$anio}";

            // ⚠️ Si ya había un envío pendiente sin resolver para este mismo día
            // (de un cierre previo que falló), hay que FUSIONAR esas filas viejas
            // con las nuevas antes de mandar — NO basta con mandar solo lo nuevo
            // y asumir que lo viejo "ya no hace falta". Si no se fusiona, los datos
            // del intento fallido anterior se pierden silenciosamente.
            $filas_a_enviar = $filas_dia;
            $pendiente_previo = mysqli_fetch_assoc(mysqli_query($conex,
                "SELECT id, datos_json FROM push_pendiente WHERE fecha_operativa='$hoy' AND estado='pendiente'"
            ));
            if ($pendiente_previo) {
                $payload_viejo = json_decode($pendiente_previo['datos_json'], true);
                $filas_viejas = $payload_viejo['filas'] ?? [];
                $ids_nuevos = array_column($filas_dia, 'id_sala');
                foreach ($filas_viejas as $filaVieja) {
                    if (!in_array($filaVieja['id_sala'], $ids_nuevos)) {
                        $filas_a_enviar[] = $filaVieja; // evita duplicar si por algo ya estaba en ambos
                    }
                }
            }

            $payload = [
                'archivo' => $archivo_nombre,
                'dia'     => $dia_num_str,
                'filas'   => $filas_a_enviar,
            ];

            $resultado_push = enviarPushOficina($payload);

            if ($resultado_push['ok']) {
                $push_status = true;
                // Ahora sí es seguro marcar resuelto lo pendiente viejo —
                // sus filas realmente viajaron en este push (se fusionaron arriba).
                if ($pendiente_previo) {
                    mysqli_query($conex, "UPDATE push_pendiente SET estado='enviado' WHERE id={$pendiente_previo['id']}");
                }
            } else {
                $push_status = false;
                // Guarda el payload FUSIONADO (no solo lo nuevo) para que el
                // siguiente reintento no pierda lo que ya traía de antes.
                guardarPushPendiente($conex, $hoy, $archivo_nombre, $dia_num_str, $payload, $resultado_push['error']);
            }
        }

    } else {
        $error = "Los registros se cerraron pero no se pudo guardar el archivo.<br>Verifica que la carpeta exista: <strong>$ruta_carpeta</strong>";
    }
}

// generarXLSMes() ahora vive en xls_helpers.php (compartida con el botón de exportar de historico.php)

// Stats del día operativo
$stats     = mysqli_fetch_assoc(mysqli_query($conex,
    "SELECT COUNT(*) as total, COALESCE(SUM(cantidad),0) as suma FROM pagos WHERE fecha='$hoy' AND cerrado=0"
));
$ya_cerrado = (int)mysqli_fetch_assoc(mysqli_query($conex,
    "SELECT COUNT(*) as n FROM pagos WHERE fecha='$hoy' AND cerrado=1"
))['n'];

// ¿Ya existe un envío pendiente sin resolver para este día operativo?
// (de un cierre previo cuyo push falló y aún no se reintenta con éxito)
$push_pendiente_previo = mysqli_fetch_assoc(mysqli_query($conex,
    "SELECT intentos, ultimo_error FROM push_pendiente WHERE fecha_operativa='$hoy' AND estado='pendiente'"
));
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Cerrar Día</title>
  <link rel="stylesheet" href="styles.css">
  <style>
    .cierre-card {
      background: rgba(255,255,255,0.06);
      border: 1px solid rgba(255,255,255,0.12);
      border-radius: var(--radius);
      padding: 32px;
      text-align: center;
      margin-bottom: 20px;
    }
    .cierre-card .numero {
      font-family: 'IBM Plex Mono', monospace;
      font-size: 3rem;
      color: var(--gold);
      display: block;
    }
    .cierre-card .label {
      font-size: 0.78rem;
      letter-spacing: 2px;
      text-transform: uppercase;
      color: var(--text-muted);
    }
    .stats-row { display: grid; grid-template-columns: 1fr 1fr; gap: 14px; margin-bottom: 24px; }
    .btn-cerrar {
      width: 100%;
      padding: 18px;
      background: #e74c3c;
      color: #fff;
      border: none;
      border-radius: var(--radius);
      font-family: 'Barlow Condensed', sans-serif;
      font-size: 1.3rem;
      font-weight: 700;
      letter-spacing: 3px;
      text-transform: uppercase;
      cursor: pointer;
      transition: background 0.2s;
    }
    .btn-cerrar:hover { background: #c0392b; }
    .btn-cerrar:disabled { background: #555; cursor: not-allowed; }
    .alert-success {
      background: rgba(46,204,113,0.1);
      border: 1px solid #2ecc71;
      color: #2ecc71;
      border-radius: var(--radius);
      padding: 20px;
      text-align: center;
      margin-bottom: 20px;
    }
    .alert-error {
      background: rgba(231,76,60,0.1);
      border: 1px solid #e74c3c;
      color: #e74c3c;
      border-radius: var(--radius);
      padding: 20px;
      text-align: center;
      margin-bottom: 20px;
    }
    .ya-cerrado {
      color: var(--text-muted);
      text-align: center;
      padding: 12px;
      font-size: 0.9rem;
    }
  </style>
</head>
<body>

<div class="header">
  <h1>Cerrar Día</h1>
  <span class="live-time" id="clock"></span>
</div>
<nav class="nav">
  <a href="index.php">Registrar</a>
  <a href="lista.php">Consultar</a>
  <a href="historico.php">Histórico</a>
  <a href="cerrar_dia.php" class="active">Cerrar Día</a>
</nav>

<div class="container">

  <?php if ($exito): ?>
    <div class="alert-success">
      ✅ Día cerrado correctamente.<br>
      Archivo guardado: <strong><?= $nombre_archivo ?></strong>
    </div>
    <?php if ($push_status === true): ?>
    <div class="alert-success" style="margin-top:-10px;">
      📡 Datos sincronizados con oficina correctamente.
    </div>
    <?php elseif ($push_status === false): ?>
    <div class="alert-error" style="margin-top:-10px;">
      ⚠️ No se pudo sincronizar con oficina en este momento. Los datos quedaron
      guardados de forma segura y se reintentará el envío automáticamente —
      no necesitas hacer nada.
    </div>
    <?php endif; ?>
    <a href="index.php" class="btn-submit" style="display:block;text-align:center;text-decoration:none;margin-top:10px;">
      Nuevo Día
    </a>

  <?php elseif ($error): ?>
    <div class="alert-error">⚠️ <?= $error ?></div>

  <?php else: ?>

    <div class="stats-row">
      <div class="cierre-card">
        <span class="numero"><?= $stats['total'] ?></span>
        <span class="label">Pagos del día</span>
      </div>
      <div class="cierre-card">
        <span class="numero">$<?= number_format((float)$stats['suma'], 2) ?></span>
        <span class="label">Total</span>
      </div>
    </div>

    <?php if ($ya_cerrado > 0): ?>
    <p class="ya-cerrado">⚠️ <?= $ya_cerrado ?> registro(s) ya cerrados hoy (no se duplicarán).</p>
    <?php endif; ?>

    <?php if ($push_pendiente_previo): ?>
    <div class="alert-error">
      📡 Ya existe un envío a oficina pendiente para este día
      (<?= (int)$push_pendiente_previo['intentos'] ?> intento(s) fallido(s)).<br>
      Si cierras de nuevo, <strong>se reemplazará</strong> ese envío pendiente
      con los datos actuales.
    </div>
    <?php endif; ?>

    <?php if ($stats['total'] == 0 && $ya_cerrado == 0): ?>
      <div class="alert-error">No hay pagos registrados hoy para cerrar.</div>
    <?php else: ?>
      <?php
        $mensaje_confirm = "¿Cerrar el día " . date('d/m/Y', strtotime($hoy)) . "?\n\n"
          . "Esto guardará los " . $stats['total'] . " pago(s) en el archivo Excel y los moverá al histórico.\n\n";
        if ($push_pendiente_previo) {
          $mensaje_confirm .= "⚠️ ATENCIÓN: Ya hay un envío pendiente a oficina sin resolver para este día. "
            . "Se REEMPLAZARÁ con los datos actuales.\n\n";
        }
        $mensaje_confirm .= "Esta acción no se puede deshacer.";
      ?>
      <form method="post" onsubmit="return confirm('<?= str_replace("\n", '\n', addslashes($mensaje_confirm)) ?>')">
        <div class="form-section" style="margin-bottom:16px;">
          <h3>Archivo destino</h3>
          <p style="font-family:monospace;font-size:0.9rem;color:var(--gold);margin-top:8px;">
            <?= $ruta_carpeta ?><strong><?= $nombre_archivo ?></strong>
          </p>
        </div>
        <button type="submit" name="cerrar" class="btn-cerrar"
          <?= $stats['total'] == 0 ? 'disabled' : '' ?>>
          ⚠ Cerrar Día <?= date('d/m/Y', strtotime($hoy)) ?>
        </button>
      </form>
    <?php endif; ?>

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
