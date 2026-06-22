<?php
include("../conexion.php");
$resultado = mysqli_query($conex, "SELECT * FROM slips ORDER BY fecha DESC, id DESC");
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reporte Slips</title>
    <link rel="stylesheet" href="../styles.css">
</head>
<body class="table-page">
<a href="../reportes.php" class="volver-btn">⬅ Volver</a>

<div class="table-container">
    <div class="tabla-responsive">
        <h2 style="color:white; margin-bottom:15px;">REPORTE MAESTRO — SLIPS</h2>
        <table>
            <tr>
                <th>FECHA</th>
                <th>NOMBRE</th>
                <th>CRÉDITOS</th>
                <th>CNR</th>
                <th>COMENTARIO</th>
                <th>TIPO</th>
                <th>TÉCNICO</th>
                <th>GERENTE</th>
                <th>ACCIONES</th>
            </tr>
            <?php while ($fila = mysqli_fetch_assoc($resultado)): ?>
            <tr>
                <td><?= date("d-m-Y", strtotime($fila['fecha'])) ?></td>
                <td><?= htmlspecialchars($fila['nombre']) ?></td>
                <td><?= htmlspecialchars($fila['creditos']) ?></td>
                <td><?= htmlspecialchars($fila['cnr']) ?></td>
                <td><?= htmlspecialchars($fila['comentario']) ?></td>
                <td><?= htmlspecialchars($fila['tipo']) ?></td>
                <td><?= htmlspecialchars($fila['tecnico']) ?></td>
                <td><?= htmlspecialchars($fila['gerente']) ?></td>
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
