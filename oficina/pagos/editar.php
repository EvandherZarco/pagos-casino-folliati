<?php
include("../conexion.php");
if (isset($_POST['actualizar'])) {
    $stmt = mysqli_prepare($conex, "UPDATE pagos SET fecha=?, asset=?, proveedor=?, cliente=?, tarjeta=?, nivel=?, cantidad=?, apuesta=?, gerente=?, tecnico=? WHERE id=?");
    mysqli_stmt_bind_param($stmt, "ssssssssssi", $_POST['fecha'], $_POST['asset'], $_POST['proveedor'], $_POST['cliente'], $_POST['tarjeta'], $_POST['nivel'], $_POST['cantidad'], $_POST['apuesta'], $_POST['gerente'], $_POST['tecnico'], (int)$_POST['id']);
    mysqli_stmt_execute($stmt);
    header("Location: lista.php"); exit;
}
$r = mysqli_fetch_assoc(mysqli_query($conex, "SELECT * FROM pagos WHERE id=".(int)$_GET['id']));
?>
<!DOCTYPE html><html lang="es"><head><meta charset="UTF-8"><title>Editar Pago</title><link rel="stylesheet" href="../styles.css"></head>
<body class="form-page">
<form method="post" autocomplete="off">
    <h2>EDITAR PAGO</h2>
    <input type="hidden" name="id" value="<?= $r['id'] ?>">
    <div class="input-group"><div class="input-container"><input type="date" name="fecha" value="<?= $r['fecha'] ?>" required></div></div>
    <div class="input-group"><div class="input-container"><input type="number" name="asset" id="asset" value="<?= $r['asset'] ?>" required></div></div>
    <div class="input-group"><div class="input-container"><input type="text" name="proveedor" id="proveedor" value="<?= htmlspecialchars($r['proveedor']) ?>" required></div></div>
    <div class="input-group"><div class="input-container"><input type="text" name="cliente" value="<?= htmlspecialchars($r['cliente']) ?>" required></div></div>
    <div class="input-group"><div class="input-container"><input type="text" name="tarjeta" value="<?= htmlspecialchars($r['tarjeta']) ?>" required></div></div>
    <div class="input-group"><div class="input-container">
        <select name="nivel" required><?php foreach(['FOLIATTI FAN','TREBOL','CORAZON','CORAZON PLUS','ESPADA','ESPADA PLUS','DIAMANTE','DIAMANTE PLUS'] as $n): ?>
            <option value="<?=$n?>" <?=$r['nivel']==$n?'selected':''?>><?=$n?></option><?php endforeach; ?>
        </select>
    </div></div>
    <div class="input-group"><div class="input-container"><input type="text" name="cantidad" value="<?= htmlspecialchars($r['cantidad']) ?>" required></div></div>
    <div class="input-group"><div class="input-container"><input type="text" name="apuesta" value="<?= htmlspecialchars($r['apuesta']) ?>" required></div></div>
    <div class="input-group"><div class="input-container">
        <select name="gerente" required><?php foreach(['GERENTE 1','GERENTE 2','GERENTE 3'] as $g): ?>
            <option value="<?=$g?>" <?=$r['gerente']==$g?'selected':''?>><?=$g?></option><?php endforeach; ?>
        </select>
    </div></div>
    <div class="input-group"><div class="input-container">
        <select name="tecnico" required><?php foreach(['TECNICO 1','TECNICO 2','EVANDHER ZARCO'] as $t): ?>
            <option value="<?=$t?>" <?=$r['tecnico']==$t?'selected':''?>><?=$t?></option><?php endforeach; ?>
        </select>
    </div></div>
    <input type="submit" name="actualizar" class="btn" value="Guardar cambios">
    <a href="lista.php" class="volver-btn" style="display:block;margin-top:10px;text-align:center;">← Cancelar</a>
</form>
<script>
let t=null;
document.getElementById('asset').addEventListener('input',function(){
    clearTimeout(t);const a=this.value.trim();if(a.length<3)return;
    t=setTimeout(()=>{fetch('/reporte/get_maquina.php?asset='+a).then(r=>r.json()).then(d=>{
        if(d.found){document.getElementById('proveedor').value=d.proveedor;}
    });},400);
});
</script>
</body></html>
