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

// Separar usuarios por rol para el dropdown
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
    .login-card select,
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
    .login-card select option[disabled] {
      color: var(--text-muted);
      font-style: italic;
    }
    .login-card select optgroup {
      font-size: 0.78rem;
      letter-spacing: 1px;
    }
    .login-card select:focus,
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

    <form method="post">
      <input type="hidden" name="redir" value="<?= htmlspecialchars($redir) ?>">

      <div class="form-group">
        <label>Usuario</label>
        <select name="usuario" required>
          <option value="" disabled <?= !$usuario_sel ? 'selected' : '' ?>>— Selecciona un usuario —</option>
          <optgroup label="GERENTES">
            <?php foreach ($gerentes_list as $key => $nombre): ?>
            <option value="<?= $key ?>" <?= $usuario_sel === $key ? 'selected' : '' ?>>
              GT - <?= htmlspecialchars($nombre) ?>
            </option>
            <?php endforeach; ?>
          </optgroup>
          <optgroup label="TÉCNICOS">
            <?php foreach ($tecnicos_list as $key => $nombre): ?>
            <option value="<?= $key ?>" <?= $usuario_sel === $key ? 'selected' : '' ?>>
              TEC - <?= htmlspecialchars($nombre) ?>
            </option>
            <?php endforeach; ?>
          </optgroup>
        </select>
      </div>

      <div class="form-group">
        <label>Contraseña</label>
        <input type="password" name="password" placeholder="••••••••" autocomplete="current-password" required>
      </div>

      <button type="submit" class="btn-login">ENTRAR</button>
    </form>
    <a href="../index.php" class="volver">← Volver a sala</a>
  </div>
</body>
</html>
