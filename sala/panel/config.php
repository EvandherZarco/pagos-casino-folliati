<?php
/* <!-- Desarrollado por Evandher Zarco (ZarcoMecatronics) - Mayo 2026 --> */
require_once __DIR__ . '/../db.php';
define('BASE_PANEL', '/pagos_sala/panel/');
require_once __DIR__ . '/auth.php';
require_panel('tecnico'); // solo técnicos

$msg   = '';
$error = '';

// Ruta al archivo de config de IPs
// Las IPs se guardan en un archivo JSON separado para no tocar push_helpers.php
$config_file = __DIR__ . '/../push_config.json';

// Leer config actual
$config = [];
if (file_exists($config_file)) {
    $config = json_decode(file_get_contents($config_file), true) ?? [];
}
$ip1 = $config['ip1'] ?? '10.10.10.65';
$ip2 = $config['ip2'] ?? '172.26.4.192';
$puerto = $config['puerto'] ?? '80';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $accion = $_POST['accion'] ?? '';

    if ($accion === 'guardar_ips') {
        $nueva_ip1   = trim($_POST['ip1']    ?? '');
        $nueva_ip2   = trim($_POST['ip2']    ?? '');
        $nuevo_puerto = trim($_POST['puerto'] ?? '80');

        // Validación básica de formato IP
        $ip_regex = '/^(\d{1,3}\.){3}\d{1,3}$/';
        if (!preg_match($ip_regex, $nueva_ip1) || !preg_match($ip_regex, $nueva_ip2)) {
            $error = 'Formato de IP inválido. Usa el formato 192.168.1.100';
        } elseif (!is_numeric($nuevo_puerto) || $nuevo_puerto < 1 || $nuevo_puerto > 65535) {
            $error = 'Puerto inválido.';
        } else {
            $nueva_config = ['ip1' => $nueva_ip1, 'ip2' => $nueva_ip2, 'puerto' => $nuevo_puerto];
            file_put_contents($config_file, json_encode($nueva_config, JSON_PRETTY_PRINT));
            $ip1    = $nueva_ip1;
            $ip2    = $nueva_ip2;
            $puerto = $nuevo_puerto;
            $msg = 'IPs actualizadas correctamente. El pipeline usará estas IPs en el próximo envío.';
        }

    } elseif ($accion === 'probar') {
        // Probar conexión HTTP a ambas IPs
        $resultados = [];
        foreach ([$ip1, $ip2] as $ip) {
            $url = "http://$ip:$puerto/oficina/recibir_pagos.php";
            $ch  = curl_init($url);
            curl_setopt_array($ch, [
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_TIMEOUT        => 5,
                CURLOPT_CONNECTTIMEOUT => 3,
                CURLOPT_POST           => true,
                CURLOPT_POSTFIELDS     => ['ping' => '1'],
            ]);
            $resp = curl_exec($ch);
            $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $err  = curl_error($ch);
            curl_close($ch);
            $resultados[$ip] = ['code' => $code, 'error' => $err, 'resp' => substr($resp ?? '', 0, 100)];
        }
        // Mostrar resultados inline
        $msg = '';
        foreach ($resultados as $ip => $r) {
            if ($r['code'] > 0) {
                $msg .= "✓ $ip — HTTP {$r['code']}\n";
            } else {
                $msg .= "✗ $ip — Sin respuesta: {$r['error']}\n";
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Panel — Configuración</title>
  <link rel="stylesheet" href="../styles.css">
  <link rel="stylesheet" href="../fonts_embedded.css">
  <style>
    .panel-nav { background:rgba(0,0,0,0.3); padding:10px 20px; display:flex; gap:16px; align-items:center; border-bottom:1px solid var(--border); flex-wrap:wrap; }
    .panel-nav a { color:var(--text-muted); text-decoration:none; font-size:0.88rem; padding:6px 12px; border-radius:var(--radius); }
    .panel-nav a:hover, .panel-nav a.active { color:var(--gold); background:rgba(212,160,23,0.1); }
    .panel-nav .usuario { margin-left:auto; font-size:0.8rem; color:var(--text-muted); }
    .panel-nav .logout { color:#e74c3c !important; }

    .config-card { background:var(--surface); border:1px solid var(--border); border-radius:var(--radius); padding:24px; margin-bottom:20px; }
    .config-card h3 { font-family:'Barlow Condensed',sans-serif; font-size:1.1rem; color:var(--gold); margin-bottom:16px; }
    .form-row-3 { display:grid; grid-template-columns:1fr 1fr 120px; gap:12px; align-items:end; }
    .form-group label { display:block; font-size:0.75rem; letter-spacing:1px; text-transform:uppercase; color:var(--text-muted); margin-bottom:6px; }
    .form-group input { width:100%; background:var(--input-bg); border:1px solid var(--border); color:var(--text); padding:10px 12px; border-radius:var(--radius); font-family:'IBM Plex Mono',monospace; font-size:0.95rem; box-sizing:border-box; }
    .btn-row { display:flex; gap:10px; margin-top:16px; flex-wrap:wrap; }
    .btn-guardar { background:var(--gold); color:#000; border:none; padding:10px 20px; border-radius:var(--radius); font-weight:700; cursor:pointer; font-size:0.95rem; }
    .btn-probar  { background:rgba(52,152,219,0.15); color:#3498db; border:1px solid rgba(52,152,219,0.3); padding:10px 20px; border-radius:var(--radius); cursor:pointer; font-size:0.95rem; }
    .msg-ok  { background:rgba(46,204,113,0.15); border:1px solid rgba(46,204,113,0.3); color:#2ecc71; padding:12px 16px; border-radius:var(--radius); margin-bottom:16px; font-size:0.88rem; white-space:pre-line; }
    .msg-err { background:rgba(231,76,60,0.15);  border:1px solid rgba(231,76,60,0.3);  color:#e74c3c;  padding:12px 16px; border-radius:var(--radius); margin-bottom:16px; font-size:0.88rem; }
    .aviso { font-size:0.78rem; color:var(--text-muted); margin-top:14px; line-height:1.6; }
  </style>
</head>
<body>

<div class="header">
  <h1>Configuración</h1>
  <span class="live-time" id="clock"></span>
</div>

<div class="panel-nav">
  <a href="index.php">Dashboard</a>
  <a href="catalogo.php">Colaboradores</a>
  <a href="config.php" class="active">Configuración</a>
  <a href="logout.php?redir=../lista.php">← Sala</a>
  <span class="usuario">
    <?= htmlspecialchars(panel_usuario()) ?>
    <span style="color:var(--gold);margin-left:4px;">[TÉCNICO]</span>
  </span>
  <a href="logout.php" class="logout">Salir</a>
</div>

<div class="container" style="max-width:700px;">

  <?php if ($msg):  ?><div class="msg-ok"><?= htmlspecialchars($msg) ?></div><?php endif; ?>
  <?php if ($error):?><div class="msg-err"><?= htmlspecialchars($error) ?></div><?php endif; ?>

  <div class="config-card">
    <h3>IPs de la PC de Oficina</h3>
    <p style="font-size:0.85rem;color:var(--text-muted);margin-bottom:16px;">
      El pipeline intenta primero IP 1, y si falla usa IP 2 (failover). Ambas deben apuntar a la PC de oficina.
    </p>
    <form method="post">
      <input type="hidden" name="accion" value="guardar_ips">
      <div class="form-row-3">
        <div class="form-group">
          <label>IP Principal (IP 1)</label>
          <input type="text" name="ip1" value="<?= htmlspecialchars($ip1) ?>" placeholder="10.10.10.65" required>
        </div>
        <div class="form-group">
          <label>IP Secundaria (IP 2)</label>
          <input type="text" name="ip2" value="<?= htmlspecialchars($ip2) ?>" placeholder="172.26.4.192" required>
        </div>
        <div class="form-group">
          <label>Puerto</label>
          <input type="number" name="puerto" value="<?= htmlspecialchars($puerto) ?>" min="1" max="65535" required>
        </div>
      </div>
      <div class="btn-row">
        <button type="submit" class="btn-guardar">Guardar IPs</button>
      </div>
    </form>
    <form method="post" style="margin-top:10px;">
      <input type="hidden" name="accion" value="probar">
      <button type="submit" class="btn-probar">🔌 Probar conexión ahora</button>
    </form>
    <p class="aviso">
      ⚠️ Cambiar las IPs aquí actualiza <code>push_config.json</code> — el pipeline lo lee en cada envío.<br>
      Si la PC de oficina obtiene una IP nueva por DHCP, actualízala aquí sin tocar código.<br>
      IPs actuales en uso: <strong><?= htmlspecialchars($ip1) ?></strong> y <strong><?= htmlspecialchars($ip2) ?></strong>
    </p>
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
