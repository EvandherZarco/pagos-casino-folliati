<?php
include("../conexion.php");

$mes  = isset($_GET['mes'])  ? $_GET['mes']  : date('m');
$anio = isset($_GET['anio']) ? $_GET['anio'] : date('Y');
$dia  = isset($_GET['dia'])  ? (int)$_GET['dia'] : 0;

$where = "WHERE MONTH(fecha) = '$mes' AND YEAR(fecha) = '$anio'";
if ($dia > 0) $where .= " AND DAY(fecha) = '$dia'";

$resultado = mysqli_query($conex,
    "SELECT * FROM reporte_diario $where
     ORDER BY fecha ASC, FIELD(turno,'APERTURA','INTERMEDIO','CIERRE')"
);

// Dias disponibles del mes para el filtro
$diasRes = mysqli_query($conex,
    "SELECT DISTINCT DAY(fecha) as dia FROM reporte_diario
     WHERE MONTH(fecha)='$mes' AND YEAR(fecha)='$anio'
     ORDER BY dia ASC"
);
$diasDisponibles = [];
while ($d = mysqli_fetch_assoc($diasRes)) $diasDisponibles[] = $d['dia'];

$meses = ['01'=>'Enero','02'=>'Febrero','03'=>'Marzo','04'=>'Abril',
          '05'=>'Mayo','06'=>'Junio','07'=>'Julio','08'=>'Agosto',
          '09'=>'Septiembre','10'=>'Octubre','11'=>'Noviembre','12'=>'Diciembre'];
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reporte Diario Mensual</title>
    <link rel="stylesheet" href="../styles.css">
    <style>
        .filtros {
            display: flex;
            gap: 10px;
            margin-bottom: 20px;
            align-items: center;
            flex-wrap: wrap;
        }
        .filtros select {
            padding: 8px 14px;
            border-radius: 20px;
            border: none;
            font-size: 14px;
            outline: none;
        }
        .filtros button {
            padding: 8px 18px;
            border-radius: 20px;
            border: none;
            background: #6200ea;
            color: white;
            cursor: pointer;
            font-size: 14px;
        }
        .exportar-btn {
            background: #2ecc71;
            color: white;
            padding: 8px 18px;
            border-radius: 20px;
            text-decoration: none;
            font-size: 14px;
            margin-left: auto;
        }
        .limpiar-btn {
            color: rgba(255,255,255,0.6);
            font-size: 13px;
            text-decoration: none;
            padding: 8px 12px;
        }
        table td { white-space: normal; font-size: 12px; }
        .turno-badge {
            padding: 2px 8px;
            border-radius: 10px;
            font-size: 11px;
            font-weight: bold;
            background: #6200ea;
            color: white;
        }
    </style>
</head>
<body class="table-page">
<a href="../reportes.php" class="volver-btn">⬅ Volver</a>

<div class="table-container">
    <div class="filtros">
        <form method="get" style="display:flex;gap:10px;align-items:center;flex-wrap:wrap;flex:1;">
            <select name="mes">
                <?php foreach ($meses as $num => $nombre): ?>
                <option value="<?= $num ?>" <?= $mes==$num?'selected':'' ?>><?= $nombre ?></option>
                <?php endforeach; ?>
            </select>
            <select name="anio">
                <?php for ($y = 2025; $y <= date('Y'); $y++): ?>
                <option value="<?= $y ?>" <?= $anio==$y?'selected':'' ?>><?= $y ?></option>
                <?php endfor; ?>
            </select>
            <select name="dia">
                <option value="0">Todos los días</option>
                <?php foreach ($diasDisponibles as $d): ?>
                <option value="<?= $d ?>" <?= $dia==$d?'selected':'' ?>>Día <?= $d ?></option>
                <?php endforeach; ?>
            </select>
            <button type="submit">Filtrar</button>
            <?php if ($dia > 0): ?>
            <a class="limpiar-btn" href="lista.php?mes=<?= $mes ?>&anio=<?= $anio ?>">✕ Quitar filtro día</a>
            <?php endif; ?>
        </form>
        <a class="exportar-btn" href="exportar.php?mes=<?= $mes ?>&anio=<?= $anio ?>">
            ⬇ Descargar <?= $meses[$mes] ?> <?= $anio ?>
        </a>
    </div>

    <h2 style="color:white;margin-bottom:15px;">
        REPORTE DIARIO — <?= strtoupper($meses[$mes]) ?> <?= $anio ?>
        <?= $dia > 0 ? "— DÍA $dia" : '' ?>
    </h2>

    <div class="tabla-responsive">
        <table>
            <tr>
                <th>FECHA</th>
                <th>TURNO</th>
                <th>TÉCNICO</th>
                <th>BUENAS NOTICIAS</th>
                <th>MALAS NOTICIAS</th>
                <th>PENDIENTES</th>
                <th>MÁQ. FUERA</th>
                <th>EQUIPOS FALLA</th>
                <th>VISITAS PROV.</th>
                <th>COMENT. CLIENTES</th>
                <th>COMENT. COLAB.</th>
                <th>TOTAL PAGOS</th>
                <th>ACCIONES</th>
            </tr>
            <?php while ($fila = mysqli_fetch_assoc($resultado)): ?>
            <tr>
                <td><?= date('d-m-Y', strtotime($fila['fecha'])) ?></td>
                <td><span class="turno-badge"><?= $fila['turno'] ?></span></td>
                <td><?= htmlspecialchars($fila['tecnico']) ?></td>
                <td><?= htmlspecialchars($fila['buenas_noticias']) ?></td>
                <td><?= htmlspecialchars($fila['malas_noticias']) ?></td>
                <td><?= htmlspecialchars($fila['pendientes']) ?></td>
                <td><?= htmlspecialchars($fila['maquinas_fuera']) ?></td>
                <td><?= htmlspecialchars($fila['equipos_falla']) ?></td>
                <td><?= htmlspecialchars($fila['visitas_proveedor']) ?></td>
                <td><?= htmlspecialchars($fila['comentarios_clientes']) ?></td>
                <td><?= htmlspecialchars($fila['comentarios_colaboradores']) ?></td>
                <td>$<?= number_format($fila['total_pagos'], 2) ?></td>
                <td>
                    <a href="editar.php?id=<?= $fila['id'] ?>" style="margin-right:6px;">✏</a>
                    <a href="eliminar.php?id=<?= $fila['id'] ?>&mes=<?= $mes ?>&anio=<?= $anio ?>&dia=<?= $dia ?>"
                       onclick="return confirm('¿Eliminar este registro?');">🗑</a>
                </td>
            </tr>
            <?php endwhile; ?>
        </table>
    </div>
</div>
</body>
</html>
