<?php
/* <!-- Desarrollado por Evandher Zarco (ZarcoMecatronics) - Mayo 2026 --> */
require_once __DIR__ . '/../db.php';
define('BASE_PANEL', '/pagos_sala/panel/');
require_once __DIR__ . '/auth.php';
require_panel('tecnico'); // solo técnicos

$msg = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $accion = $_POST['accion'] ?? '';
    $tabla  = $_POST['tabla']  ?? '';

    // Validar tabla permitida
    if (!in_array($tabla, ['tecnicos', 'gerentes'])) {
        $error = 'Tabla no válida.';
    } else {

        if ($accion === 'agregar') {
            $nombre = strtoupper(trim(mysqli_real_escape_string($conex, $_POST['nombre'] ?? '')));
            if ($nombre) {
                // Verificar si ya existe (incluso inactivo)
                $existe = mysqli_fetch_assoc(mysqli_query($conex,
                    "SELECT id, activo FROM $tabla WHERE nombre='$nombre'"
                ));
                if ($existe && $existe['activo']) {
                    $error = "Ya existe «$nombre» en el catálogo.";
                } elseif ($existe && !$existe['activo']) {
                    // Reactivar
                    mysqli_query($conex, "UPDATE $tabla SET activo=1 WHERE id={$existe['id']}");
                    $msg = "«$nombre» reactivado correctamente.";
                } else {
                    mysqli_query($conex, "INSERT INTO $tabla (nombre) VALUES ('$nombre')");
                    $msg = "«$nombre» agregado correctamente.";
                }
            } else {
                $error = 'El nombre no puede estar vacío.';
            }

        } elseif ($accion === 'desactivar') {
            $id = (int)($_POST['id'] ?? 0);
            if ($id) {
                mysqli_query($conex, "UPDATE $tabla SET activo=0 WHERE id=$id");
                $msg = 'Registro desactivado. No aparecerá en los formularios, pero el historial se conserva.';
            }

        } elseif ($accion === 'reactivar') {
            $id = (int)($_POST['id'] ?? 0);
            if ($id) {
                mysqli_query($conex, "UPDATE $tabla SET activo=1 WHERE id=$id");
                $msg = 'Registro reactivado.';
            }
        }
    }
}

// Leer catálogos
$tecnicos = [];
$res = mysqli_query($conex, "SELECT * FROM tecnicos ORDER BY activo DESC, nombre ASC");
while ($r = mysqli_fetch_assoc($res)) $tecnicos[] = $r;

$gerentes = [];
$res = mysqli_query($conex, "SELECT * FROM gerentes ORDER BY activo DESC, nombre ASC");
while ($r = mysqli_fetch_assoc($res)) $gerentes[] = $r;
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Panel — Catálogo</title>
  <link rel="stylesheet" href="../styles.css">
  <link rel="stylesheet" href="../fonts_embedded.css">
  <style>
    .panel-nav { background:rgba(0,0,0,0.3); padding:10px 20px; display:flex; gap:16px; align-items:center; border-bottom:1px solid var(--border); flex-wrap:wrap; }
    .panel-nav a { color:var(--text-muted); text-decoration:none; font-size:0.88rem; padding:6px 12px; border-radius:var(--radius); }
    .panel-nav a:hover, .panel-nav a.active { color:var(--gold); background:rgba(212,160,23,0.1); }
    .panel-nav .usuario { margin-left:auto; font-size:0.8rem; color:var(--text-muted); }
    .panel-nav .logout { color:#e74c3c !important; }

    .catalogo-grid { display:grid; grid-template-columns:1fr 1fr; gap:20px; }
    @media(max-width:600px){ .catalogo-grid { grid-template-columns:1fr; } }

    .catalogo-card { background:var(--surface); border:1px solid var(--border); border-radius:var(--radius); padding:20px; }
    .catalogo-card h3 { font-family:'Barlow Condensed',sans-serif; font-size:1.1rem; color:var(--gold); margin-bottom:16px; }

    .agregar-form { display:flex; gap:8px; margin-bottom:16px; }
    .agregar-form input {
      flex:1; background:var(--input-bg); border:1px solid var(--border);
      color:var(--text); padding:8px 12px; border-radius:var(--radius);
      font-family:inherit; font-size:0.95rem; text-transform:uppercase;
    }
    .agregar-form button { background:var(--gold); color:#000; border:none; padding:8px 16px; border-radius:var(--radius); font-weight:700; cursor:pointer; font-size:0.9rem; }

    .lista-item { display:flex; justify-content:space-between; align-items:center; padding:10px 0; border-bottom:1px solid rgba(255,255,255,0.05); }
    .lista-item:last-child { border-bottom:none; }
    .lista-item .nombre { font-size:0.92rem; color:var(--text); }
    .lista-item.inactivo .nombre { color:var(--text-muted); text-decoration:line-through; }
    .btn-des { background:rgba(231,76,60,0.15); color:#e74c3c; border:1px solid rgba(231,76,60,0.3); padding:4px 12px; border-radius:4px; font-size:0.78rem; cursor:pointer; }
    .btn-reac { background:rgba(46,204,113,0.15); color:#2ecc71; border:1px solid rgba(46,204,113,0.3); padding:4px 12px; border-radius:4px; font-size:0.78rem; cursor:pointer; }
    .badge-inactivo { font-size:0.7rem; color:var(--text-muted); margin-left:6px; }

    .msg-ok  { background:rgba(46,204,113,0.15); border:1px solid rgba(46,204,113,0.3); color:#2ecc71; padding:10px 14px; border-radius:var(--radius); margin-bottom:16px; font-size:0.88rem; }
    .msg-err { background:rgba(231,76,60,0.15);  border:1px solid rgba(231,76,60,0.3);  color:#e74c3c;  padding:10px 14px; border-radius:var(--radius); margin-bottom:16px; font-size:0.88rem; }

    .aviso { font-size:0.75rem; color:var(--text-muted); margin-top:12px; line-height:1.5; }
  </style>
</head>
<body>

<div class="header">
  <h1>Colaboradores</h1>
  <span class="live-time" id="clock"></span>
</div>

<div class="panel-nav">
  <a href="index.php">Dashboard</a>
  <a href="catalogo.php" class="active">Colaboradores</a>
  <?php if (es_tecnico()): ?>
  <a href="config.php">Configuración</a>
  <?php endif; ?>
  <a href="logout.php?redir=../lista.php">← Sala</a>
  <span class="usuario">
    <?= htmlspecialchars(panel_usuario()) ?>
    <span style="color:var(--gold);margin-left:4px;">[TÉCNICO]</span>
  </span>
  <a href="logout.php" class="logout">Salir</a>
</div>

<div class="container" style="max-width:900px;">

  <?php if ($msg):  ?><div class="msg-ok"><?= htmlspecialchars($msg) ?></div><?php endif; ?>
  <?php if ($error):?><div class="msg-err"><?= htmlspecialchars($error) ?></div><?php endif; ?>

  <div class="catalogo-grid">

    <!-- TÉCNICOS -->
    <div class="catalogo-card">
      <h3>Técnicos</h3>
      <form method="post" class="agregar-form">
        <input type="hidden" name="accion" value="agregar">
        <input type="hidden" name="tabla" value="tecnicos">
        <input type="text" name="nombre" placeholder="NOMBRE COMPLETO" required>
        <button type="submit">+ Agregar</button>
      </form>
      <?php foreach ($tecnicos as $t): ?>
      <div class="lista-item <?= $t['activo'] ? '' : 'inactivo' ?>">
        <span class="nombre">
          <?= htmlspecialchars($t['nombre']) ?>
          <?php if (!$t['activo']): ?><span class="badge-inactivo">(inactivo)</span><?php endif; ?>
        </span>
        <form method="post" style="margin:0;">
          <input type="hidden" name="tabla" value="tecnicos">
          <input type="hidden" name="id" value="<?= $t['id'] ?>">
          <?php if ($t['activo']): ?>
            <input type="hidden" name="accion" value="desactivar">
            <button type="submit" class="btn-des"
              onclick="return confirm('¿Desactivar a <?= htmlspecialchars($t['nombre']) ?>? Dejará de aparecer en los formularios.')">
              Desactivar
            </button>
          <?php else: ?>
            <input type="hidden" name="accion" value="reactivar">
            <button type="submit" class="btn-reac">Reactivar</button>
          <?php endif; ?>
        </form>
      </div>
      <?php endforeach; ?>
      <p class="aviso">⚠️ Desactivar no borra el historial — el técnico sigue apareciendo en pagos anteriores. Solo deja de aparecer en los formularios nuevos.</p>
    </div>

    <!-- GERENTES -->
    <div class="catalogo-card">
      <h3>Gerentes</h3>
      <form method="post" class="agregar-form">
        <input type="hidden" name="accion" value="agregar">
        <input type="hidden" name="tabla" value="gerentes">
        <input type="text" name="nombre" placeholder="NOMBRE COMPLETO" required>
        <button type="submit">+ Agregar</button>
      </form>
      <?php foreach ($gerentes as $g): ?>
      <div class="lista-item <?= $g['activo'] ? '' : 'inactivo' ?>">
        <span class="nombre">
          <?= htmlspecialchars($g['nombre']) ?>
          <?php if (!$g['activo']): ?><span class="badge-inactivo">(inactivo)</span><?php endif; ?>
        </span>
        <form method="post" style="margin:0;">
          <input type="hidden" name="tabla" value="gerentes">
          <input type="hidden" name="id" value="<?= $g['id'] ?>">
          <?php if ($g['activo']): ?>
            <input type="hidden" name="accion" value="desactivar">
            <button type="submit" class="btn-des"
              onclick="return confirm('¿Desactivar a <?= htmlspecialchars($g['nombre']) ?>? Dejará de aparecer en los formularios.')">
              Desactivar
            </button>
          <?php else: ?>
            <input type="hidden" name="accion" value="reactivar">
            <button type="submit" class="btn-reac">Reactivar</button>
          <?php endif; ?>
        </form>
      </div>
      <?php endforeach; ?>
      <p class="aviso">⚠️ Desactivar no borra el historial — el gerente sigue apareciendo en pagos anteriores. Solo deja de aparecer en los formularios nuevos.</p>
    </div>

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
