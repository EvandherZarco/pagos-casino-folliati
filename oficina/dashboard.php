<?php
include("conexion.php");

$totalAperturas   = mysqli_fetch_assoc(mysqli_query($conex, "SELECT COUNT(*) as total FROM datos"))['total'];
$totalPagos       = mysqli_fetch_assoc(mysqli_query($conex, "SELECT COUNT(*) as total FROM pagos"))['total'];
$totalHandpay     = mysqli_fetch_assoc(mysqli_query($conex, "SELECT COUNT(*) as total FROM handpay"))['total'];
$totalSlips       = mysqli_fetch_assoc(mysqli_query($conex, "SELECT COUNT(*) as total FROM slips"))['total'];
$totalProveedores = mysqli_fetch_assoc(mysqli_query($conex, "SELECT COUNT(*) as total FROM proveedores"))['total'];

$sumaPagos   = mysqli_fetch_assoc(mysqli_query($conex, "SELECT COALESCE(SUM(cantidad),0) as total FROM pagos"))['total'];
$sumaHandpay = mysqli_fetch_assoc(mysqli_query($conex, "SELECT COALESCE(SUM(cantidad),0) as total FROM handpay"))['total'];

$ultimasAperturas = mysqli_query($conex, "SELECT asset, proveedor, falla, turno, fecha FROM datos ORDER BY id DESC LIMIT 5");
?>
<!DOCTYPE html>
<!-- Desarrollado por Evandher Zarco (ZarcoMecatronics) - Mayo 2026 -->
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard</title>
    <link rel="stylesheet" href="styles.css">
    <style>
        body {
            background: #0d3320;
            min-height: 100vh;
            padding: 28px 24px;
            font-family: 'DM Sans', sans-serif;
        }

        .dash-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 24px;
            padding-bottom: 20px;
            border-bottom: 1px solid rgba(255,255,255,0.12);
        }

        .dash-header h1 {
            color: white;
            font-size: 18px;
            font-weight: 600;
            letter-spacing: 0.5px;
        }

        .dash-header a {
            color: rgba(255,255,255,0.7);
            background: rgba(255,255,255,0.1);
            border: 1px solid rgba(255,255,255,0.15);
            padding: 7px 16px;
            border-radius: 8px;
            text-decoration: none;
            font-size: 13px;
            transition: background 0.15s;
        }

        .dash-header a:hover { background: rgba(255,255,255,0.18); }

        .cards {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
            gap: 12px;
            margin-bottom: 24px;
        }

        .card {
            background: rgba(255,255,255,0.07);
            border-radius: 12px;
            padding: 20px 16px;
            text-align: center;
            border: 1px solid rgba(255,255,255,0.1);
        }

        .card .numero {
            font-size: 38px;
            font-weight: 600;
            color: white;
            line-height: 1;
            margin-bottom: 6px;
        }

        .card .label {
            font-size: 11px;
            color: rgba(255,255,255,0.55);
            text-transform: uppercase;
            letter-spacing: 1px;
        }

        .card.dinero .numero {
            font-size: 26px;
            color: #6ee7a0;
        }

        .seccion {
            background: rgba(255,255,255,0.06);
            border-radius: 14px;
            padding: 20px;
            border: 1px solid rgba(255,255,255,0.1);
        }

        .seccion h3 {
            color: rgba(255,255,255,0.6);
            font-size: 11px;
            letter-spacing: 1px;
            margin-bottom: 14px;
            text-transform: uppercase;
            font-weight: 600;
        }

        .seccion table {
            background: transparent;
            width: 100%;
            min-width: unset;
            border-collapse: collapse;
            font-size: 13px;
        }

        .seccion th {
            background: transparent;
            color: rgba(255,255,255,0.4);
            text-align: left;
            padding: 6px 10px;
            font-weight: 500;
            font-size: 11px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .seccion td {
            background: transparent !important;
            color: rgba(255,255,255,0.85);
            padding: 9px 10px;
            border-top: 1px solid rgba(255,255,255,0.06);
            font-size: 13px;
        }

        .turno-badge {
            background: rgba(255,255,255,0.1);
            border: 1px solid rgba(255,255,255,0.15);
            padding: 2px 10px;
            border-radius: 20px;
            font-size: 11px;
            color: rgba(255,255,255,0.8);
        }

        .seccion tr:hover td { background: rgba(255,255,255,0.05) !important; }

        .refresh {
            text-align: right;
            color: rgba(255,255,255,0.3);
            font-size: 11px;
            margin-top: 16px;
        }

        .refresh a { color: rgba(255,255,255,0.3); text-decoration: none; }
        .refresh a:hover { color: rgba(255,255,255,0.5); }
    </style>
</head>
<body>

<div class="dash-header">
    <h1>📊 Dashboard</h1>
    <a href="menu.php">⬅ Menú</a>
</div>

<div class="cards">
    <div class="card">
        <div class="numero"><?= $totalAperturas ?></div>
        <div class="label">Aperturas</div>
    </div>
    <div class="card dinero">
        <div class="numero">$<?= number_format($sumaPagos, 2) ?></div>
        <div class="label">Total Pagos</div>
    </div>
    <div class="card dinero">
        <div class="numero">$<?= number_format($sumaHandpay, 2) ?></div>
        <div class="label">Total Handpay</div>
    </div>
    <div class="card">
        <div class="numero"><?= $totalSlips ?></div>
        <div class="label">Slips</div>
    </div>
    <div class="card">
        <div class="numero"><?= $totalProveedores ?></div>
        <div class="label">Visitas Prov.</div>
    </div>
</div>

<div class="seccion">
    <h3>Últimas 5 aperturas</h3>
    <table>
        <tr>
            <th>Asset</th>
            <th>Proveedor</th>
            <th>Falla</th>
            <th>Turno</th>
            <th>Fecha</th>
        </tr>
        <?php while ($fila = mysqli_fetch_assoc($ultimasAperturas)): ?>
        <tr>
            <td><?= htmlspecialchars($fila['asset']) ?></td>
            <td><?= htmlspecialchars($fila['proveedor']) ?></td>
            <td><?= htmlspecialchars($fila['falla']) ?></td>
            <td><span class="turno-badge"><?= htmlspecialchars($fila['turno']) ?></span></td>
            <td><?= date('d-m-Y', strtotime($fila['fecha'])) ?></td>
        </tr>
        <?php endwhile; ?>
    </table>
</div>

<div class="refresh">
    Actualizado: <?= date('d/m/Y H:i:s') ?>
    &nbsp;·&nbsp;
    <a href="dashboard.php">↻ Refrescar</a>
</div>

</body>
</html>
