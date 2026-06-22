<?php
include("../conexion.php");
$resultado = mysqli_query($conex, "SELECT * FROM proveedores ORDER BY fecha DESC, id DESC");
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reporte Proveedores</title>
    <link rel="stylesheet" href="../styles.css">
</head>
<body class="table-page">
<a href="../reportes.php" class="volver-btn">⬅ Volver</a>

<div class="table-container">
    <div class="tabla-responsive">
        <h2 style="color:white; margin-bottom:15px;">REPORTE MAESTRO — VISITA DE PROVEEDORES</h2>
        <table>
            <tr>
                <th>PROVEEDOR</th>
                <th>TÉCNICO</th>
                <th>HORA INICIAL</th>
                <th>HORA FINAL</th>
                <th>INGRESO AL SITE</th>
                <th>ACTIVIDADES</th>
                <th>FECHA</th>
                <th>TURNO</th>
                <th>ACCIONES</th>
            </tr>
            <?php while ($fila = mysqli_fetch_assoc($resultado)): ?>
            <tr>
                <td><?= htmlspecialchars($fila['proveedor']) ?></td>
                <td><?= htmlspecialchars($fila['tecnico']) ?></td>
                <td><?= date("H:i", strtotime($fila['hora_inicial'])) ?></td>
                <td><?= date("H:i", strtotime($fila['hora_final'])) ?></td>
                <td><?= htmlspecialchars($fila['ingreso_al_site']) ?></td>
                <td><?= htmlspecialchars($fila['actividades']) ?></td>
                <td><?= date("d-m-Y", strtotime($fila['fecha'])) ?></td>
                <td><?= htmlspecialchars($fila['turno']) ?></td>
                <td>
                    <a href="editar.php?id=<?= $fila['id'] ?>" style="margin-right:8px;">✏ Editar</a>
                    <a href="eliminar.php?id=<?= $fila['id'] ?>"
                       onclick="return confirm('¿Eliminar este registro?');">
                       🗑 Eliminar
                    </a>
                </td>
            </tr>
            <?php endwhile; ?>
        </table>
    </div>
</div>
</body>
</html>
