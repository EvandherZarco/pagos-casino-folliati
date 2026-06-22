<?php
include("../conexion.php");
if (isset($_POST['actualizar'])) {
    $stmt = mysqli_prepare($conex, "UPDATE handpay SET fecha=?, hora=?, cantidad=?, blue_point=?, cliente=?, tecnico=?, gerente=? WHERE id=?");
    mysqli_stmt_bind_param($stmt, "ssssssssi", $_POST['fecha'], $_POST['hora'], $_POST['cantidad'], $_POST['blue_point'], $_POST['cliente'], $_POST['tecnico'], $_POST['gerente'], (int)$_POST['id']);
    mysqli_stmt_execute($stmt);
    header("Location: lista.php"); exit;
}
$r = mysqli_fetch_assoc(mysqli_query($conex, "SELECT * FROM handpay WHERE id=".(int)$_GET['id']));
?>
<!DOCTYPE html><html lang="es"><head><meta charset="UTF-8"><title>Editar Handpay</title><link rel="stylesheet" href="../styles.css"></head>
<body class="form-page">
<form method="post" autocomplete="off">
    <h2>EDITAR HANDPAY</h2>
    <input type="hidden" name="id" value="<?= $r['id'] ?>">
    <div class="input-group"><div class="input-container"><input type="date" name="fecha" value="<?= $r['fecha'] ?>" required></div></div>
    <div class="input-group"><div class="input-container"><input type="time" name="hora" value="<?= $r['hora'] ?>" required></div></div>
    <div class="input-group"><div class="input-container"><input type="text" name="cantidad" value="<?= htmlspecialchars($r['cantidad']) ?>" required></div></div>
    <div class="input-group"><div class="input-container"><input type="text" name="blue_point" value="<?= htmlspecialchars($r['blue_point']) ?>" required></div></div>
    <div class="input-group"><div class="input-container"><input type="text" name="cliente" value="<?= htmlspecialchars($r['cliente']) ?>" required></div></div>
    <div class="input-group"><div class="input-container">
        <select name="tecnico" required><?php foreach(['TECNICO 1','TECNICO 2','EVANDHER ZARCO'] as $t): ?>
            <option value="<?=$t?>" <?=$r['tecnico']==$t?'selected':''?>><?=$t?></option><?php endforeach; ?>
        </select>
    </div></div>
    <div class="input-group"><div class="input-container">
        <select name="gerente" required><?php foreach(['GERENTE 1','GERENTE 2','GERENTE 3'] as $g): ?>
            <option value="<?=$g?>" <?=$r['gerente']==$g?'selected':''?>><?=$g?></option><?php endforeach; ?>
        </select>
    </div></div>
    <input type="submit" name="actualizar" class="btn" value="Guardar cambios">
    <a href="lista.php" class="volver-btn" style="display:block;margin-top:10px;text-align:center;">← Cancelar</a>
</form>
</body></html>
