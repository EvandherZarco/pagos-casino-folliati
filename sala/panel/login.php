<?php
/* <!-- Desarrollado por Evandher Zarco (ZarcoMecatronics) - Mayo 2026 --> */
require_once __DIR__ . '/../db.php';
require_once __DIR__ . '/auth.php';

if (!empty($_SESSION['panel_usuario'])) {
    header('Location: index.php');
    exit;
}

$error = '';
$redir = $_GET['redir'] ?? 'index.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $usuario = strtoupper(trim($_POST['usuario'] ?? ''));
    $pass    = $_POST['password'] ?? '';

    if (isset(PANEL_USUARIOS[$usuario]) && password_verify($pass, PANEL_USUARIOS[$usuario]['hash'])) {
        $_SESSION['panel_usuario'] = $usuario;
        $_SESSION['panel_nombre']  = PANEL_USUARIOS[$usuario]['nombre'];
        $_SESSION['panel_rol']     = PANEL_USUARIOS[$usuario]['rol'];
        $_SESSION['panel_inicio']  = time();
        header('Location: ' . $redir);
        exit;
    }
    $error = 'Usuario o contraseña incorrectos.';
}

$gerentes_list = [];
$tecnicos_list = [];
foreach (PANEL_USUARIOS as $key => $u) {
    if ($u['rol'] === 'gerente') $gerentes_list[$key] = $u['nombre'];
    else                         $tecnicos_list[$key] = $u['nombre'];
}
$usuario_sel = strtoupper($_POST['usuario'] ?? '');
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Panel — Acceso</title>
  <link rel="stylesheet" href="../styles.css">
  <link rel="stylesheet" href="../fonts_embedded.css">
  <style>
    body { display:flex; align-items:center; justify-content:center; min-height:100vh; }
    .login-card {
      background: var(--surface);
      border: 1px solid var(--border);
      border-radius: var(--radius);
      padding: 40px;
      width: 100%;
      max-width: 360px;
    }
    .login-card h2 {
      font-family: 'Barlow Condensed', sans-serif;
      font-size: 1.5rem;
      color: var(--gold);
      margin-bottom: 6px;
      text-align: center;
    }
    .login-card .subtitulo {
      font-size: 0.78rem;
      color: var(--text-muted);
      text-align: center;
      letter-spacing: 2px;
      text-transform: uppercase;
      margin-bottom: 28px;
    }
    .login-card .form-group { margin-bottom: 16px; }
    .login-card label {
      display: block;
      font-size: 0.78rem;
      color: var(--text-muted);
      letter-spacing: 1px;
      text-transform: uppercase;
      margin-bottom: 6px;
    }
    .login-card input {
      width: 100%;
      background: var(--input-bg);
      border: 1px solid var(--border);
      color: var(--text);
      padding: 10px 14px;
      border-radius: var(--radius);
      font-family: inherit;
      font-size: 1rem;
      box-sizing: border-box;
    }
    .login-card input:focus { outline: none; border-color: var(--gold); }
    .error-msg {
      background: rgba(231,76,60,0.15);
      border: 1px solid rgba(231,76,60,0.4);
      color: #e74c3c;
      padding: 10px 14px;
      border-radius: var(--radius);
      font-size: 0.88rem;
      margin-bottom: 16px;
    }
    .btn-login {
      width: 100%;
      padding: 12px;
      background: var(--gold);
      color: #000;
      border: none;
      border-radius: var(--radius);
      font-family: 'Barlow Condensed', sans-serif;
      font-size: 1.1rem;
      font-weight: 700;
      letter-spacing: 2px;
      cursor: pointer;
      margin-top: 8px;
    }
    .btn-login:hover { opacity: 0.9; }
    .volver { display:block; text-align:center; margin-top:18px; font-size:0.85rem; color:var(--text-muted); text-decoration:none; }
    .volver:hover { color: var(--gold); }

    /* ── Custom dropdown ── */
    .custom-select { position: relative; }
    .custom-select-trigger {
      width: 100%;
      background: var(--input-bg);
      border: 1px solid var(--border);
      color: var(--text);
      padding: 10px 36px 10px 14px;
      border-radius: var(--radius);
      font-family: inherit;
      font-size: 1rem;
      box-sizing: border-box;
      cursor: pointer;
      text-align: left;
      user-select: none;
      position: relative;
    }
    .custom-select-trigger::after {
      content: '▾';
      position: absolute;
      right: 12px;
      top: 50%;
      transform: translateY(-50%);
      color: var(--text-muted);
      pointer-events: none;
    }
    .custom-select-trigger.open { border-color: var(--gold); }
    .custom-select-trigger.placeholder { color: var(--text-muted); font-style: italic; }

    .custom-dropdown {
      display: none;
      position: absolute;
      top: calc(100% + 4px);
      left: 0; right: 0;
      background: var(--surface);
      border: 1px solid var(--border);
      border-radius: var(--radius);
      z-index: 100;
      overflow: hidden;
      box-shadow: 0 8px 24px rgba(0,0,0,0.4);
    }
    .custom-dropdown.open { display: block; }

    .dropdown-group-label {
      font-size: 0.7rem;
      letter-spacing: 2px;
      text-transform: uppercase;
      color: var(--gold);
      padding: 8px 14px 4px;
      background: rgba(212,160,23,0.08);
      border-bottom: 1px solid var(--border);
    }
    .dropdown-option {
      padding: 10px 14px;
      font-size: 0.95rem;
      color: var(--text);
      cursor: pointer;
      transition: background 0.15s;
      border-bottom: 1px solid rgba(255,255,255,0.04);
    }
    .dropdown-option:last-child { border-bottom: none; }
    .dropdown-option:hover { background: rgba(255,255,255,0.06); }
    .dropdown-option.selected { color: var(--gold); background: rgba(212,160,23,0.08); }
    .dropdown-option .role-badge {
      font-size: 0.7rem;
      letter-spacing: 1px;
      color: var(--text-muted);
      margin-right: 6px;
    }
  </style>
</head>
<body>
  <div class="login-card">
    <h2>🔐 Panel</h2>
    <p class="subtitulo">Acceso restringido</p>

    <?php if ($error): ?>
    <div class="error-msg"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <?php if (isset($_GET['error']) && $_GET['error'] === 'acceso'): ?>
    <div class="error-msg">No tienes permiso para acceder a esa sección.</div>
    <?php endif; ?>

    <form method="post" id="login-form">
      <input type="hidden" name="redir" value="<?= htmlspecialchars($redir) ?>">
      <input type="hidden" name="usuario" id="usuario-hidden" value="<?= htmlspecialchars($usuario_sel) ?>">

      <div class="form-group">
        <label>Usuario</label>
        <div class="custom-select" id="custom-select">
          <div class="custom-select-trigger placeholder" id="select-trigger">— Selecciona un usuario —</div>
          <div class="custom-dropdown" id="custom-dropdown">

            <div class="dropdown-group-label">GERENTES</div>
            <?php foreach ($gerentes_list as $key => $nombre): ?>
            <div class="dropdown-option <?= $usuario_sel === $key ? 'selected' : '' ?>"
                 data-value="<?= $key ?>">
              <span class="role-badge">GT</span><?= htmlspecialchars($nombre) ?>
            </div>
            <?php endforeach; ?>

            <div class="dropdown-group-label">TÉCNICOS</div>
            <?php foreach ($tecnicos_list as $key => $nombre): ?>
            <div class="dropdown-option <?= $usuario_sel === $key ? 'selected' : '' ?>"
                 data-value="<?= $key ?>">
              <span class="role-badge">TEC</span><?= htmlspecialchars($nombre) ?>
            </div>
            <?php endforeach; ?>

          </div>
        </div>
      </div>

      <div class="form-group">
        <label>Contraseña</label>
        <input type="password" name="password" placeholder="••••••••" autocomplete="current-password" required>
      </div>

      <button type="submit" class="btn-login">ENTRAR</button>
    </form>
    <a href="../index.php" class="volver">← Volver a sala</a>
  </div>

  <script>
    const trigger   = document.getElementById('select-trigger');
    const dropdown  = document.getElementById('custom-dropdown');
    const hidden    = document.getElementById('usuario-hidden');
    const options   = document.querySelectorAll('.dropdown-option');
    const form      = document.getElementById('login-form');

    // Si ya hay un valor preseleccionado (error de login), mostrar la etiqueta
    <?php if ($usuario_sel): ?>
    (function() {
      options.forEach(opt => {
        if (opt.dataset.value === '<?= $usuario_sel ?>') {
          trigger.textContent = opt.textContent.trim();
          trigger.classList.remove('placeholder');
        }
      });
    })();
    <?php endif; ?>

    trigger.addEventListener('click', function(e) {
      e.stopPropagation();
      dropdown.classList.toggle('open');
      trigger.classList.toggle('open');
    });

    options.forEach(function(opt) {
      opt.addEventListener('click', function() {
        options.forEach(o => o.classList.remove('selected'));
        opt.classList.add('selected');
        hidden.value = opt.dataset.value;
        trigger.textContent = opt.textContent.trim();
        trigger.classList.remove('placeholder', 'open');
        dropdown.classList.remove('open');
      });
    });

    document.addEventListener('click', function() {
      dropdown.classList.remove('open');
      trigger.classList.remove('open');
    });

    // Validar que se seleccionó usuario antes de enviar
    form.addEventListener('submit', function(e) {
      if (!hidden.value) {
        e.preventDefault();
        trigger.style.borderColor = '#e74c3c';
        setTimeout(() => trigger.style.borderColor = '', 1500);
      }
    });
  </script>
</body>
</html>
