<?php
include("../conexion.php");

$meses_archivo = ['','ENERO','FEBRERO','MARZO','ABRIL','MAYO','JUNIO',
                  'JULIO','AGOSTO','SEPTIEMBRE','OCTUBRE','NOVIEMBRE','DICIEMBRE'];

$carpeta = '\\\\10.10.10.132\\pagosmes\\';

// Fecha operativa: antes de las 8am se considera el día anterior
// (casino opera hasta madrugada, margen ampliado a 8am para importación)
$hora = (int) date("H");
$fechaHoy = ($hora < 8) ? date("Y-m-d", strtotime("-1 day")) : date("Y-m-d");
$fecha = isset($_GET['fecha']) ? $_GET['fecha'] : $fechaHoy;
$fecha_esc = mysqli_real_escape_string($conex, $fecha);

$pagos = [];
$total = 0;
$fuente = 'bd';

// Intentar leer de BD primero
$res = mysqli_query($conex, "SELECT * FROM pagos WHERE fecha='$fecha_esc' ORDER BY id DESC");
if ($res && mysqli_num_rows($res) > 0) {
    $fuente = 'bd';
    while ($r = mysqli_fetch_assoc($res)) $pagos[] = $r;
} else {
    // Leer del XLS correspondiente
    $fuente = 'xls';
    $mes_num  = (int) date('n', strtotime($fecha));
    $anio     = (int) date('Y', strtotime($fecha));
    $dia_num  = (int) date('j', strtotime($fecha));
    $nombre_archivo = "PAGOS-{$meses_archivo[$mes_num]}-{$anio}.xls";
    $ruta = $carpeta . $nombre_archivo;

    if (file_exists($ruta)) {
        $contenido = file_get_contents($ruta);
        // Buscar la hoja del día
        preg_match('/<Worksheet ss:Name="' . $dia_num . '">(.*?)<\/Worksheet>/s', $contenido, $hoja_match);
        if ($hoja_match) {
            preg_match_all('/<Row>(.*?)<\/Row>/s', $hoja_match[1], $filas_match);
            $filas = $filas_match[1];
            $encabezados = [];
            foreach ($filas as $i => $fila) {
                preg_match_all('/<Data[^>]*>(.*?)<\/Data>/s', $fila, $cel_match);
                $celdas = array_map('strip_tags', $cel_match[1]);
                if ($i === 0) { $encabezados = $celdas; continue; }
                if (empty(array_filter($celdas))) continue;
                $primera = strtoupper(trim($celdas[0] ?? ''));
                if (str_contains($primera, 'TOTAL')) continue;
                $pago = [];
                foreach ($encabezados as $j => $col) $pago[$col] = $celdas[$j] ?? '';
                if (!empty($pago)) $pagos[] = $pago;
            }
        }
    }
}

$count = count($pagos);
foreach ($pagos as $p) {
    $total += floatval($fuente === 'bd' ? ($p['cantidad'] ?? 0) : ($p['CANTIDAD'] ?? 0));
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reporte Pagos</title>
    <link rel="stylesheet" href="../styles.css">
    <style>
        .filtro-bar {
            display: flex;
            align-items: center;
            gap: 12px;
            margin-bottom: 16px;
            flex-wrap: wrap;
        }
        .filtro-bar input[type="date"] {
            padding: 8px 12px;
            border-radius: 8px;
            border: 1px solid var(--border-mid);
            font-size: 14px;
            font-family: 'DM Sans', sans-serif;
            background: var(--surface);
            color: var(--slate);
        }
        .filtro-bar button {
            padding: 8px 16px;
            background: var(--blue);
            color: white;
            border: none;
            border-radius: 8px;
            font-size: 14px;
            cursor: pointer;
            font-family: 'DM Sans', sans-serif;
        }
        .filtro-bar button:hover { background: var(--blue-dark); }
        .stats-row { display: flex; gap: 12px; margin-bottom: 16px; }
        .stat-box {
            background: var(--surface);
            border: 1px solid var(--border);
            border-radius: 8px;
            padding: 12px 20px;
            flex: 1;
        }
        .stat-box .label { font-size: 11px; color: var(--slate-soft); text-transform: uppercase; letter-spacing: 1px; }
        .stat-box .value { font-size: 1.4rem; font-weight: 600; color: var(--blue); margin-top: 4px; }
        .hoy-btn {
            font-size: 12px; color: var(--blue); text-decoration: none;
            padding: 4px 10px; border: 1px solid var(--blue); border-radius: 6px;
        }
        .hoy-btn:hover { background: var(--blue-light); }
        .fuente-badge {
            font-size: 11px; letter-spacing: 1px; text-transform: uppercase;
            padding: 3px 10px; border-radius: 4px;
        }
        .fuente-bd  { background: rgba(26,122,60,0.12); color: #1a7a3c; border: 1px solid rgba(26,122,60,0.3); }
        .fuente-xls { background: rgba(52,152,219,0.12); color: #2980b9; border: 1px solid rgba(52,152,219,0.3); }
    </style>
</head>
<body class="table-page">
<a href="../reportes.php" class="volver-btn">← Volver</a>

<div class="table-container">
    <div class="tabla-responsive">
        <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:15px;">
            <h2 style="color:white;">PAGOS DE SALA</h2>
            <span class="fuente-badge fuente-<?= $fuente ?>">
                <?= $fuente === 'bd' ? '● Base de datos' : '📄 Archivo XLS' ?>
            </span>
        </div>

        <!-- Filtro -->
        <form method="get" class="filtro-bar">
            <input type="date" name="fecha" value="<?= htmlspecialchars($fecha) ?>">
            <button type="submit">Filtrar</button>
            <?php if ($fecha !== $fechaHoy): ?>
                <a href="lista.php" class="hoy-btn">← Hoy</a>
            <?php endif; ?>
            <span style="color:rgba(255,255,255,0.5); font-size:13px;">
                <?= date("d/m/Y", strtotime($fecha)) ?>
                <?= ($fecha === $fechaHoy) ? '<span style="color:#4ade80;font-size:11px;margin-left:6px;">● HOY</span>' : '' ?>
            </span>
        </form>

        <!-- Stats -->
        <div class="stats-row">
            <div class="stat-box">
                <div class="label">Registros</div>
                <div class="value"><?= $count ?></div>
            </div>
            <div class="stat-box">
                <div class="label">Total del día</div>
                <div class="value">$<?= number_format((float)$total, 2) ?></div>
            </div>
        </div>

        <table>
            <tr>
                <th>FECHA</th><th>HORA</th><th>ASSET</th><th>PROVEEDOR</th><th>CLIENTE</th>
                <th>TARJETA</th><th>NIVEL</th><th>CANTIDAD</th><th>APUESTA</th>
                <th>GERENTE</th><th>TÉCNICO</th><th>ACCIONES</th>
            </tr>
            <?php if ($count === 0): ?>
            <tr>
                <td colspan="12" style="text-align:center; color:#94a3b8; padding:32px;">
                    Sin registros para <?= date("d/m/Y", strtotime($fecha)) ?>
                </td>
            </tr>
            <?php elseif ($fuente === 'bd'): ?>
                <?php foreach ($pagos as $p): ?>
                <tr>
                    <td><?= date("d-m-Y", strtotime($p['fecha'])) ?></td>
                    <td><?= htmlspecialchars(substr($p['hora'] ?? '', 0, 5)) ?></td>
                    <td><?= htmlspecialchars($p['asset']) ?></td>
                    <td><?= htmlspecialchars($p['proveedor']) ?></td>
                    <td><?= htmlspecialchars($p['cliente']) ?></td>
                    <td><?= htmlspecialchars($p['tarjeta']) ?></td>
                    <td><?= htmlspecialchars($p['nivel']) ?></td>
                    <td><?= htmlspecialchars($p['cantidad']) ?></td>
                    <td><?= htmlspecialchars($p['apuesta']) ?></td>
                    <td><?= htmlspecialchars($p['gerente']) ?></td>
                    <td><?= htmlspecialchars($p['tecnico']) ?></td>
                    <td>
                        <a href="editar.php?id=<?= $p['id'] ?>" style="margin-right:8px;">✏ Editar</a>
                        <a href="eliminar.php?id=<?= $p['id'] ?>"
                           onclick="return confirm('¿Eliminar este registro?');">🗑 Eliminar</a>
                    </td>
                </tr>
                <?php endforeach; ?>
            <?php else: ?>
                <?php foreach ($pagos as $p): ?>
                <tr>
                    <td><?= htmlspecialchars($p['FECHA'] ?? '') ?></td>
                    <td><?= htmlspecialchars($p['HORA'] ?? '') ?></td>
                    <td><?= htmlspecialchars($p['TERMINAL'] ?? '') ?></td>
                    <td><?= htmlspecialchars($p['PROVEEDOR'] ?? '') ?></td>
                    <td><?= htmlspecialchars($p['CLIENTE'] ?? '') ?></td>
                    <td><?= htmlspecialchars($p['ID'] ?? '') ?></td>
                    <td><?= htmlspecialchars($p['NIVEL'] ?? '') ?></td>
                    <td><?= htmlspecialchars($p['CANTIDAD'] ?? '') ?></td>
                    <td><?= htmlspecialchars($p['APUESTA'] ?? '') ?></td>
                    <td><?= htmlspecialchars($p['GERENTE'] ?? '') ?></td>
                    <td><?= htmlspecialchars($p['TÉCNICO'] ?? $p['TECNICO'] ?? '') ?></td>
                    <td style="color: rgba(255,255,255,0.3); font-size:11px;">Solo lectura</td>
                </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </table>
    </div>
</div>
</body>
</html>
