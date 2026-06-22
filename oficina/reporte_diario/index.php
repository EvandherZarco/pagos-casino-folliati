<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reporte de Técnicos</title>
    <link rel="stylesheet" href="../styles.css">
    <style>
        form { width: 520px; }
        .input-container textarea {
            width: 100%;
            padding: 10px 14px;
            border-radius: 8px;
            background-color: var(--surface, #f8fafc);
            border: 1px solid #cbd5e1;
            font-size: 14px;
            outline: none;
            color: #1e293b;
            resize: vertical;
            min-height: 60px;
            font-family: 'DM Sans', sans-serif;
            box-sizing: border-box;
            margin-bottom: 10px;
        }
        .input-container textarea:focus { border-color: #1a7a3c; background: white; }
        .campo-label {
            color: #475569;
            font-size: 11px;
            font-weight: 600;
            text-align: left;
            margin-bottom: 4px;
            margin-top: 4px;
            letter-spacing: 0.5px;
            text-transform: uppercase;
        }
    </style>
</head>
<body class="form-page">
<?php
include("../conexion.php");
$hora = (int) date('H');
$fechaOperativa = ($hora < 4) ? date('Y-m-d', strtotime('-1 day')) : date('Y-m-d');
?>

<form method="post" autocomplete="off">
    <h2>Reporte de Técnicos</h2>

    <div class="input-group"><div class="input-container">
        <input type="date" name="fecha" value="<?= $fechaOperativa ?>" required>
    </div></div>

    <div class="input-group"><div class="input-container">
        <select name="turno" required>
            <option value="">Selecciona turno</option>
            <option value="APERTURA">APERTURA</option>
            <option value="INTERMEDIO">INTERMEDIO</option>
            <option value="CIERRE">CIERRE</option>
        </select>
    </div></div>

    <div class="input-group"><div class="input-container">
        <select name="tecnico" required>
            <option value="">Selecciona técnico</option>
            <option value="TECNICO 1">TECNICO 1</option>
            <option value="TECNICO 2">TECNICO 2</option>
            <option value="EVANDHER ZARCO">EVANDHER ZARCO</option>
        </select>
    </div></div>

    <div class="campo-label">Buenas noticias</div>
    <div class="input-group"><div class="input-container">
        <textarea name="buenas_noticias" placeholder="SIN NOVEDAD"></textarea>
    </div></div>

    <div class="campo-label">Malas noticias</div>
    <div class="input-group"><div class="input-container">
        <textarea name="malas_noticias" placeholder="SIN NOVEDAD"></textarea>
    </div></div>

    <div class="campo-label">Pendientes</div>
    <div class="input-group"><div class="input-container">
        <textarea name="pendientes" placeholder="SIN NOVEDAD"></textarea>
    </div></div>

    <div class="campo-label">Máquinas fuera de servicio</div>
    <div class="input-group"><div class="input-container">
        <textarea name="maquinas_fuera" placeholder="SIN NOVEDAD"></textarea>
    </div></div>

    <div class="campo-label">Equipos reportados por fallas</div>
    <div class="input-group"><div class="input-container">
        <textarea name="equipos_falla" placeholder="SIN NOVEDAD"></textarea>
    </div></div>

    <div class="campo-label">Visitas de proveedor</div>
    <div class="input-group"><div class="input-container">
        <textarea name="visitas_proveedor" placeholder="SIN NOVEDAD"></textarea>
    </div></div>

    <div class="campo-label">Comentarios de clientes</div>
    <div class="input-group"><div class="input-container">
        <textarea name="comentarios_clientes" placeholder="SIN NOVEDAD"></textarea>
    </div></div>

    <div class="campo-label">Comentarios de colaboradores</div>
    <div class="input-group"><div class="input-container">
        <textarea name="comentarios_colaboradores" placeholder="SIN NOVEDAD"></textarea>
    </div></div>

    <input type="submit" name="send" class="btn" value="Guardar reporte">
    <a href="../registros.php" class="volver-btn" style="display:block;margin-top:10px;text-align:center;">← Volver</a>
</form>

<?php
if (isset($_POST['send'])) {
    $fecha     = trim($_POST['fecha']);
    $turno     = trim($_POST['turno']);
    $tecnico   = trim($_POST['tecnico']);
    $buenas    = trim($_POST['buenas_noticias']) ?: 'SIN NOVEDAD';
    $malas     = trim($_POST['malas_noticias']) ?: 'SIN NOVEDAD';
    $pendientes = trim($_POST['pendientes']) ?: 'SIN NOVEDAD';
    $maquinas  = trim($_POST['maquinas_fuera']) ?: 'SIN NOVEDAD';
    $equipos   = trim($_POST['equipos_falla']) ?: 'SIN NOVEDAD';
    $visitas   = trim($_POST['visitas_proveedor']) ?: 'SIN NOVEDAD';
    $clientes  = trim($_POST['comentarios_clientes']) ?: 'SIN NOVEDAD';
    $colab     = trim($_POST['comentarios_colaboradores']) ?: 'SIN NOVEDAD';

    $stmt = mysqli_prepare($conex,
        "INSERT INTO reporte_diario (fecha, turno, tecnico, buenas_noticias, malas_noticias, pendientes, maquinas_fuera, equipos_falla, visitas_proveedor, comentarios_clientes, comentarios_colaboradores, total_pagos)
         VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 0)"
    );
    mysqli_stmt_bind_param($stmt, "sssssssssss",
        $fecha, $turno, $tecnico, $buenas, $malas, $pendientes,
        $maquinas, $equipos, $visitas, $clientes, $colab
    );

    if (mysqli_stmt_execute($stmt)) {
        echo "<h3 id='mensaje' class='success'>✔ Reporte guardado</h3>";
    } else {
        echo "<h3 id='mensaje' class='error'>Error al guardar</h3>";
    }
    mysqli_stmt_close($stmt);
}
?>
<script>
window.onload = function () {
    var m = document.getElementById("mensaje");
    if (m) setTimeout(function () { m.style.display = "none"; }, 3000);
};
</script>
</body>
</html>
