<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Máquinas Fuera de Servicio</title>
    <link rel="stylesheet" href="../styles.css">
</head>
<body class="form-page">
<?php $hora = (int) date("H"); $fechaOperativa = ($hora < 4) ? date("Y-m-d", strtotime("-1 day")) : date("Y-m-d"); ?>

<form method="post" autocomplete="off">
    <h2>MÁQUINAS FUERA DE SERVICIO</h2>

    <div class="input-group"><div class="input-container">
        <input type="number" name="asset" id="asset" placeholder="Asset" required>
    </div></div>

    <div class="input-group"><div class="input-container">
        <input type="text" name="proveedor" id="proveedor" placeholder="Proveedor" required>
    </div></div>

    <div class="input-group"><div class="input-container">
        <input type="text" name="modelo" id="modelo" placeholder="Modelo" required>
    </div></div>

    <div class="input-group"><div class="input-container">
        <input type="text" name="serie" id="serie" placeholder="No. Serie" required>
    </div></div>

    <div class="input-group"><div class="input-container">
        <select name="tecnico" required>
            <option value="">Selecciona técnico</option>
            <option value="TECNICO 1">TECNICO 1</option>
            <option value="TECNICO 2">TECNICO 2</option>
            <option value="EVANDHER ZARCO">EVANDHER ZARCO</option>
        </select>
    </div></div>

    <div class="input-group"><div class="input-container">
        <input type="date" name="fecha" value="<?= $fechaOperativa ?>" required>
    </div></div>

    <div class="input-group"><div class="input-container">
        <input type="text" name="razon" placeholder="Razón" required>
    </div></div>

    <input type="submit" name="send" class="btn" value="Enviar">
    <a href="../registros.php" class="volver-btn">← Volver</a>
</form>

<?php include("send.php") ?>

<script>
let assetTimer = null;
document.getElementById('asset').addEventListener('input', function () {
    clearTimeout(assetTimer);
    const asset = this.value.trim();
    if (asset.length < 3) return;
    assetTimer = setTimeout(() => {
        fetch('/reporte/get_maquina.php?asset=' + asset)
            .then(r => r.json())
            .then(data => {
                if (data.found) {
                    document.getElementById('proveedor').value = data.proveedor;
                    document.getElementById('modelo').value    = data.modelo;
                    document.getElementById('serie').value     = data.serie;
                    ['proveedor','modelo','serie'].forEach(id => document.getElementById(id).style.background = '#d4edda');
                } else {
                    document.getElementById('proveedor').value = '';
                    document.getElementById('modelo').value    = '';
                    document.getElementById('serie').value     = '';
                    ['proveedor','modelo','serie'].forEach(id => document.getElementById(id).style.background = '');
                }
            });
    }, 400);
});

window.onload = function () {
    var m = document.getElementById("mensaje");
    if (m) setTimeout(function () { m.style.display = "none"; }, 3000);
};
</script>
</body>
</html>
