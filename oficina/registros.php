<!DOCTYPE html>
<!-- Desarrollado por Evandher Zarco (ZarcoMecatronics) - Mayo 2026 -->
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Registros</title>
    <link rel="stylesheet" href="styles.css">
    <style>
        .menu-btn { background:#0f5c2a !important; color:white !important; border-color:#0f5c2a !important; }
        .menu-btn:hover { background:#0a4520 !important; border-color:#0a4520 !important; color:white !important; }
        .menu-btn .btn-icon { color:white !important; }
        .menu-btn.volver { background:transparent !important; border:none !important; color:rgba(255,255,255,0.6) !important; box-shadow:none !important; }
        .menu-btn.volver:hover { color:white !important; }

        .menu-header {
            position: relative;
        }
        .btn-menu-principal {
            position: absolute;
            top: 0;
            right: 0;
            background: transparent;
            border: none;
            color: rgba(255,255,255,0.6);
            font-size: 0.8rem;
            cursor: pointer;
            text-decoration: none;
            padding: 4px 0;
            transition: color 0.2s;
        }
        .btn-menu-principal:hover {
            color: white;
        }
    </style>
</head>
<body class="menu-page">
<div class="menu-container">
    <div class="menu-header">
        <div>
            <div class="title">Registrar</div>
            <div class="subtitle">Nuevo registro de evento</div>
        </div>
        <a href="menu.php" class="btn-menu-principal">Menú principal →</a>
    </div>

    <div class="menu-grid">
        <a href="Aperturas/index.php" class="menu-btn"><span class="btn-icon">🔧</span>Aperturas</a>
        <a href="Maquinas/index.php" class="menu-btn"><span class="btn-icon">⛔</span>Fuera de servicio</a>
        <a href="pagos/index.php" class="menu-btn"><span class="btn-icon">💵</span>Pagos</a>
        <a href="handpay/index.php" class="menu-btn"><span class="btn-icon">🤝</span>Handpay</a>
        <a href="Proveedores/index.php" class="menu-btn"><span class="btn-icon">🚗</span>Proveedores</a>
        <a href="slips/index.php" class="menu-btn"><span class="btn-icon">🧾</span>Slips</a>
    </div>

    <a href="reporte_diario/index.php" class="menu-btn full-width" style="margin-bottom:10px;">
        <span class="btn-icon">📝</span>Reporte de Técnicos
    </a>
    <a href="menu.php" class="menu-btn volver">← Volver</a>
</div>
</body>
</html>
