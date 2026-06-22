<?php
/* <!-- Desarrollado por Evandher Zarco (ZarcoMecatronics) - Mayo 2026 --> */
require_once __DIR__ . '/../db.php';
define('BASE_PANEL', '/pagos_sala/panel/');
require_once __DIR__ . '/auth.php';
require_panel(); // cualquier rol puede reintentar

// Llamar al script de reintentos existente
// Capturamos output para no romper la redirección
ob_start();
require_once '../reintentar_push.php';
ob_end_clean();

header('Location: index.php?reintentos=ok');
exit;
