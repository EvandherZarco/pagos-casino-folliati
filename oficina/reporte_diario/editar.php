<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Editar Reporte Diario</title>
    <link rel="stylesheet" href="../styles.css">
    <style>
        form { width: 520px; }
        .input-container textarea {
            width: 100%;
            padding: 12px 15px;
            border-radius: 14px;
            background-color: #dcdcdc;
            border: none;
            font-size: 14px;
            outline: none;
            color: #1e1e1e;
            resize: vertical;
            min-height: 60px;
            font-family: Arial, sans-serif;
            box-sizing: border-box;
            margin-bottom: 12px;
        }
        .campo-label {
            color: white;
            font-size: 12px;
            text-align: left;
            margin-bottom: 4px;
            margin-top: 4px;
            letter-spacing: 1px;
            text-transform: uppercase;
        }
    </style>
</head>
<body class="form-page">
<?php
include("../conexion.php");

if (isset($_POST['actualizar'])) {
    $id      = (int) $_POST['id'];
    $fecha   = trim($_POST['fecha']);
    $turno   = trim($_POST['turno']);
    $tecnico = trim($_POST['tecnico']);
    $buenas  = trim($_POST['buenas_noticias']) ?: 'SIN NOVEDAD';
    $malas   = trim($_POST['malas_noticias']) ?: 'SIN NOVEDAD';
    $pend    = trim($_POST['pendientes']) ?: 'SIN NOVEDAD';
    $maq     = trim($_POST['maquinas_fuera']) ?: 'SIN NOVEDAD';
    $equip   = trim($_POST['equipos_falla']) ?: 'SIN NOVEDAD';
    $visit   = trim($_POST['visitas_proveedor']) ?: 'SIN NOVEDAD';
    $cli     = trim($_POST['comentarios_clientes']) ?: 'SIN NOVEDAD';
    $colab   = trim($_POST['comentarios_colaboradores']) ?: 'SIN NOVEDAD';
    $total   = floatval($_POST['total_pagos']);

    $stmt = mysqli_prepare($conex,
        "UPDATE reporte_diario SET fecha=?, turno=?, tecnico=?, buenas_noticias=?, malas_noticias=?,
         pendientes=?, maquinas_fuera=?, equipos_falla=?, visitas_proveedor=?,
         comentarios_clientes=?, comentarios_colaboradores=?, total_pagos=? WHERE id=?"
    );
    mysqli_stmt_bind_param($stmt, "sssssssssssdi",
        $fecha, $turno, $tecnico, $buenas, $malas, $pend,
        $maq, $equip, $visit, $cli, $colab, $total, $id
    );
    mysqli_stmt_execute($stmt);
    $mes  = date('m', strtotime($fecha));
    $anio = date('Y', strtotime($fecha));
    header("Location: lista.php?mes=$mes&anio=$anio");
    exit;
}

$r = mysqli_fetch_assoc(mysqli_query($conex, "SELECT * FROM reporte_diario WHERE id=".(int)$_GET['id']));
?>
<form method="post" autocomplete="off">
    <h2>EDITAR REPORTE DIARIO</h2>
    <input type="hidden" name="id" value="<?= $r['id'] ?>">

    <div class="input-group"><div class="input-container">
        <input type="date" name="fecha" value="<?= $r['fecha'] ?>" required>
    </div></div>

    <div class="input-group"><div class="input-container">
        <select name="turno" required>
            <?php foreach(['APERTURA','INTERMEDIO','CIERRE'] as $t): ?>
            <option value="<?=$t?>" <?=$r['turno']==$t?'selected':''?>><?=$t?></option>
            <?php endforeach; ?>
        </select>
    </div></div>

    <div class="input-group"><div class="input-container">
        <select name="tecnico" required>
            <?php foreach(['TECNICO 1','TECNICO 2','EVANDHER ZARCO'] as $t): ?>
            <option value="<?=$t?>" <?=$r['tecnico']==$t?'selected':''?>><?=$t?></option>
            <?php endforeach; ?>
        </select>
    </div></div>

    <div class="campo-label">Buenas noticias</div>
    <div class="input-group"><div class="input-container">
        <textarea name="buenas_noticias"><?= htmlspecialchars($r['buenas_noticias']) ?></textarea>
    </div></div>

    <div class="campo-label">Malas noticias</div>
    <div class="input-group"><div class="input-container">
        <textarea name="malas_noticias"><?= htmlspecialchars($r['malas_noticias']) ?></textarea>
    </div></div>

    <div class="campo-label">Pendientes</div>
    <div class="input-group"><div class="input-container">
        <textarea name="pendientes"><?= htmlspecialchars($r['pendientes']) ?></textarea>
    </div></div>

    <div class="campo-label">Máquinas fuera de servicio</div>
    <div class="input-group"><div class="input-container">
        <textarea name="maquinas_fuera"><?= htmlspecialchars($r['maquinas_fuera']) ?></textarea>
    </div></div>

    <div class="campo-label">Equipos reportados por fallas</div>
    <div class="input-group"><div class="input-container">
        <textarea name="equipos_falla"><?= htmlspecialchars($r['equipos_falla']) ?></textarea>
    </div></div>

    <div class="campo-label">Visitas de proveedor</div>
    <div class="input-group"><div class="input-container">
        <textarea name="visitas_proveedor"><?= htmlspecialchars($r['visitas_proveedor']) ?></textarea>
    </div></div>

    <div class="campo-label">Comentarios de clientes</div>
    <div class="input-group"><div class="input-container">
        <textarea name="comentarios_clientes"><?= htmlspecialchars($r['comentarios_clientes']) ?></textarea>
    </div></div>

    <div class="campo-label">Comentarios de colaboradores</div>
    <div class="input-group"><div class="input-container">
        <textarea name="comentarios_colaboradores"><?= htmlspecialchars($r['comentarios_colaboradores']) ?></textarea>
    </div></div>

    <div class="campo-label">Total de pagos</div>
    <div class="input-group"><div class="input-container">
        <input type="text" name="total_pagos" value="<?= $r['total_pagos'] ?>" required>
    </div></div>

    <input type="submit" name="actualizar" class="btn" value="Guardar cambios">
    <a href="lista.php" class="volver-btn" style="display:block;margin-top:10px;text-align:center;">← Cancelar</a>
</form>
</body>
</html>
