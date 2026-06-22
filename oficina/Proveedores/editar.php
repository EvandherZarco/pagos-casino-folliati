<?php
include("../conexion.php");
if (isset($_POST['actualizar'])) {
    $stmt = mysqli_prepare($conex, "UPDATE proveedores SET proveedor=?, tecnico=?, hora_inicial=?, hora_final=?, ingreso_al_site=?, actividades=?, fecha=?, turno=? WHERE id=?");
    mysqli_stmt_bind_param($stmt, "ssssssssi", $_POST['proveedor'], $_POST['tecnico'], $_POST['hora_inicial'], $_POST['hora_final'], $_POST['ingreso_al_site'], $_POST['actividades'], $_POST['fecha'], $_POST['turno'], (int)$_POST['id']);
    mysqli_stmt_execute($stmt);
    header("Location: lista.php"); exit;
}
$r = mysqli_fetch_assoc(mysqli_query($conex, "SELECT * FROM proveedores WHERE id=".(int)$_GET['id']));
$provs = ['AGS','AINSWORTH','APEX','ARISTOCRAT','AURIFY','BALLY','BETSTONE WINT','DREIDEL','EGT','EIBE','FBM','GOLD CLUB','IGT','JVL','MERKUR GAMING','METRONIA','NOVOMATIC','ORTIZ','SHOW BALL','ZITRO'];
?>
<!DOCTYPE html><html lang="es"><head><meta charset="UTF-8"><title>Editar Proveedor</title><link rel="stylesheet" href="../styles.css"></head>
<body class="form-page">
<form method="post" autocomplete="off">
    <h2>EDITAR VISITA PROVEEDOR</h2>
    <input type="hidden" name="id" value="<?= $r['id'] ?>">
    <div class="input-group"><div class="input-container">
        <select name="proveedor" required><?php foreach($provs as $p): ?>
            <option value="<?=$p?>" <?=$r['proveedor']==$p?'selected':''?>><?=$p?></option><?php endforeach; ?>
        </select>
    </div></div>
    <div class="input-group"><div class="input-container">
        <select name="tecnico" required><?php foreach(['TECNICO 1','TECNICO 2','EVANDHER ZARCO'] as $t): ?>
            <option value="<?=$t?>" <?=$r['tecnico']==$t?'selected':''?>><?=$t?></option><?php endforeach; ?>
        </select>
    </div></div>
    <div class="input-group"><div class="input-container"><input type="time" name="hora_inicial" value="<?= $r['hora_inicial'] ?>" required></div></div>
    <div class="input-group"><div class="input-container"><input type="time" name="hora_final" value="<?= $r['hora_final'] ?>" required></div></div>
    <div class="input-group"><div class="input-container"><input type="text" name="ingreso_al_site" value="<?= htmlspecialchars($r['ingreso_al_site']) ?>" required></div></div>
    <div class="input-group"><div class="input-container"><input type="text" name="actividades" value="<?= htmlspecialchars($r['actividades']) ?>" required></div></div>
    <div class="input-group"><div class="input-container"><input type="date" name="fecha" value="<?= $r['fecha'] ?>" required></div></div>
    <div class="input-group"><div class="input-container">
        <select name="turno" required><?php foreach(['APERTURA','INTERMEDIO','CIERRE'] as $t): ?>
            <option value="<?=$t?>" <?=$r['turno']==$t?'selected':''?>><?=$t?></option><?php endforeach; ?>
        </select>
    </div></div>
    <input type="submit" name="actualizar" class="btn" value="Guardar cambios">
    <a href="lista.php" class="volver-btn" style="display:block;margin-top:10px;text-align:center;">← Cancelar</a>
</form>
</body></html>
