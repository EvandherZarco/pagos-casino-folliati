<!DOCTYPE html>
<!-- Desarrollado por Evandher Zarco (ZarcoMecatronics) - Mayo 2026 -->
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Menú Principal</title>
    <link rel="stylesheet" href="styles.css">
    <style>
        .menu-btn {
            background: #0f5c2a !important;
            color: white !important;
            border-color: #0f5c2a !important;
        }
        .menu-btn:hover {
            background: #0a4520 !important;
            border-color: #0a4520 !important;
            color: white !important;
        }
        .menu-btn .btn-icon { color: white !important; }
    </style>
</head>
<body class="menu-page">
<div class="menu-container">

    <div class="menu-header">
        <div class="logo">ZM</div>
        <div>
            <div class="title">Sistema de Sala</div>
            <div class="subtitle">Panel de control · Casino</div>
        </div>
    </div>

    <div class="menu-grid">
        <a href="dashboard.php" class="menu-btn">
            <span class="btn-icon">📊</span>
            Dashboard
        </a>
        <a href="registros.php" class="menu-btn">
            <span class="btn-icon">✏️</span>
            Registrar
        </a>
        <a href="reportes.php" class="menu-btn">
            <span class="btn-icon">🔍</span>
            Consultar
        </a>
        <a href="info_maquinas.php" class="menu-btn">
            <span class="btn-icon">🎰</span>
            Máquinas
        </a>
    </div>

    <a href="directorio.php" class="menu-btn full-width" style="margin-bottom:10px;">
        <span class="btn-icon">📋</span>
        Directorio de Proveedores
    </a>

    <a href="exportar_general.php" class="menu-btn full-width" style="margin-bottom:10px;">
        <span class="btn-icon">⬇️</span>
        Descargar Reporte Maestro
    </a>

</div>
</body>
</html>
