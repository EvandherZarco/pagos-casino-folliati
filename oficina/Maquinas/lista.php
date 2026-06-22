<?php
include("../conexion.php");
$resultado = mysqli_query($conex, "SELECT * FROM offla ORDER BY fecha DESC, id DESC");
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reporte Máquinas Offla</title>
    <link rel="stylesheet" href="../styles.css">
</head>
<body class="table-page">
<a href="../reportes.php" class="volver-btn">⬅ Volver</a>

<div class="table-container">
    <div class="tabla-responsive">
        <h2 style="color:white; margin-bottom:15px;">REPORTE MAESTRO — MÁQUINAS FUERA DE SERVICIO</h2>
        <table>
            <tr>
                <th>ASSET</th>
                <th>PROVEEDOR</th>
                <th>MODELO</th>
                <th>SERIE</th>
                <th>TÉCNICO</th>
                <th>FECHA</th>
                <th>RAZÓN</th>
                <th>ACCIONES</th>
            </tr>
            <?php while ($fila = mysqli_fetch_assoc($resultado)): ?>
            <tr>
                <td><?= htmlspecialchars($fila['asset']) ?></td>
                <td><?= htmlspecialchars($fila['proveedor']) ?></td>
                <td><?= htmlspecialchars($fila['modelo']) ?></td>
                <td><?= htmlspecialchars($fila['serie']) ?></td>
                <td><?= htmlspecialchars($fila['tecnico']) ?></td>
                <td><?= date("d-m-Y", strtotime($fila['fecha'])) ?></td>
                <td><?= htmlspecialchars($fila['razon']) ?></td>
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
