<?php
include("../conexion.php");
if (isset($_POST['actualizar'])) {
    $stmt = mysqli_prepare($conex, "UPDATE slips SET fecha=?, nombre=?, creditos=?, cnr=?, comentario=?, tipo=?, tecnico=?, gerente=? WHERE id=?");
    mysqli_stmt_bind_param($stmt, "ssssssssi", $_POST['fecha'], $_POST['nombre'], $_POST['creditos'], $_POST['cnr'], $_POST['comentario'], $_POST['tipo'], $_POST['tecnico'], $_POST['gerente'], (int)$_POST['id']);
    mysqli_stmt_execute($stmt);
    header("Location: lista.php"); exit;
}
$r = mysqli_fetch_assoc(mysqli_query($conex, "SELECT * FROM slips WHERE id=".(int)$_GET['id']));
?>
<!DOCTYPE html><html lang="es"><head><meta charset="UTF-8"><title>Editar Slip</title><link rel="stylesheet" href="../styles.css"></head>
<body class="form-page">
<form method="post" autocomplete="off">
    <h2>EDITAR SLIP</h2>
    <input type="hidden" name="id" value="<?= $r['id'] ?>">
    <div class="input-group"><div class="input-container"><input type="date" name="fecha" value="<?= $r['fecha'] ?>" required></div></div>
    <div class="input-group"><div class="input-container"><input type="text" name="nombre" value="<?= htmlspecialchars($r['nombre']) ?>" required></div></div>
    <div class="input-group"><div class="input-container"><input type="text" name="creditos" value="<?= htmlspecialchars($r['creditos']) ?>" required></div></div>
    <div class="input-group"><div class="input-container"><input type="text" name="cnr" value="<?= htmlspecialchars($r['cnr']) ?>" required></div></div>
    <div class="input-group"><div class="input-container"><input type="text" name="comentario" value="<?= htmlspecialchars($r['comentario']) ?>" required></div></div>
    <div class="input-group"><div class="input-container">
        <select name="tipo" required><?php foreach(['SUMA','REEMPLAZO','RESTA'] as $t): ?>
            <option value="<?=$t?>" <?=$r['tipo']==$t?'selected':''?>><?=$t?></option><?php endforeach; ?>
        </select>
    </div></div>
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
