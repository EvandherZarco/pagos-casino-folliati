<?php
/* auth.php — Validación de sesión del panel técnico/gerente */

if (session_status() === PHP_SESSION_NONE) session_start();

if (!defined('BASE_PANEL')) define('BASE_PANEL', '/pagos_sala/panel/');

function require_panel(string $rol_minimo = '') {
    if (empty($_SESSION['panel_usuario'])) {
        header('Location: ' . BASE_PANEL . 'login.php?redir=' . urlencode($_SERVER['REQUEST_URI']));
        exit;
    }
    if ($rol_minimo === 'tecnico' && $_SESSION['panel_rol'] !== 'tecnico') {
        header('Location: ' . BASE_PANEL . 'index.php?error=acceso');
        exit;
    }
}

function panel_usuario(): string  { return $_SESSION['panel_usuario']      ?? ''; }
function panel_nombre(): string   { return $_SESSION['panel_nombre']       ?? ''; }
function panel_rol(): string      { return $_SESSION['panel_rol']          ?? ''; }
function es_tecnico(): bool       { return panel_rol() === 'tecnico'; }
