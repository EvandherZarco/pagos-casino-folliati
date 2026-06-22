<?php
include("../conexion.php");
if (isset($_POST['actualizar'])) {
    
    $stmt = mysqli_prepare($conex, "UPDATE offla SET asset=?, proveedor=?, modelo=?, serie=?, tecnico=?, fecha=?, razon=? WHERE id=?");
    mysqli_stmt_bind_param($stmt, "sssssssi", $_POST['asset'], $_POST['proveedor'], $_POST['modelo'], $_POST['serie'], $_POST['tecnico'], $_POST['fecha'], $_POST['razon'], (int)$_POST['id']);
    mysqli_stmt_execute($stmt);
    header("Location: lista.php"); exit;
}
$r = mysqli_fetch_assoc(mysqli_query($conex, "SELECT * FROM offla WHERE id=".(int)$_GET['id']));
?>
<!DOCTYPE html><html lang="es"><head><meta charset="UTF-8"><title>Editar Offla</title><link rel="stylesheet" href="../styles.css"></head>
<body class="form-page">
<form method="post" autocomplete="off">
    <h2>EDITAR MÁQUINA OFFLA</h2>
    <input type="hidden" name="id" value="<?= $r['id'] ?>">
    <div class="input-group"><div class="input-container"><input type="number" name="asset" id="asset" value="<?= $r['asset'] ?>" required></div></div>
    <div class="input-group"><div class="input-container"><input type="text" name="proveedor" id="proveedor" value="<?= htmlspecialchars($r['proveedor']) ?>" required></div></div>
    <div class="input-group"><div class="input-container"><input type="text" name="modelo" id="modelo" value="<?= htmlspecialchars($r['modelo']) ?>" required></div></div>
    <div class="input-group"><div class="input-container"><input type="text" name="serie" id="serie" value="<?= htmlspecialchars($r['serie']) ?>" required></div></div>
    <div class="input-group"><div class="input-container">
        <select name="tecnico" required><?php foreach(['TECNICO 1','TECNICO 2','EVANDHER ZARCO'] as $t): ?>
            <option value="<?=$t?>" <?=$r['tecnico']==$t?'selected':''?>><?=$t?></option><?php endforeach; ?>
        </select>
    </div></div>
    <div class="input-group"><div class="input-container"><input type="date" name="fecha" value="<?= $r['fecha'] ?>" required></div></div>
    <div class="input-group"><div class="input-container"><input type="text" name="razon" value="<?= htmlspecialchars($r['razon']) ?>" required></div></div>
    <input type="submit" name="actualizar" class="btn" value="Guardar cambios">
    <a href="lista.php" class="volver-btn" style="display:block;margin-top:10px;text-align:center;">← Cancelar</a>
</form>
<script>
let t=null;
document.getElementById('asset').addEventListener('input',function(){
    clearTimeout(t);const a=this.value.trim();if(a.length<3)return;
    t=setTimeout(()=>{fetch('/reporte/get_maquina.php?asset='+a).then(r=>r.json()).then(d=>{
        if(d.found){document.getElementById('proveedor').value=d.proveedor;document.getElementById('modelo').value=d.modelo;document.getElementById('serie').value=d.serie;}
    });},400);
});
</script>
</body></html>
