<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pagos</title>
    <link rel="stylesheet" href="../styles.css">
</head>
<body class="form-page">
<?php $hora = (int) date("H"); $fechaOperativa = ($hora < 4) ? date("Y-m-d", strtotime("-1 day")) : date("Y-m-d"); ?>

<form method="post" autocomplete="off">
    <h2>PAGOS</h2>

    <div class="input-group"><div class="input-container">
        <input type="date" name="fecha" value="<?= $fechaOperativa ?>" required>
    </div></div>

    <div class="input-group"><div class="input-container">
        <input type="number" name="asset" id="asset" placeholder="Asset" required>
    </div></div>

    <div class="input-group"><div class="input-container">
        <input type="text" name="proveedor" id="proveedor" placeholder="Proveedor" required>
    </div></div>

    <div class="input-group"><div class="input-container">
        <input type="text" name="cliente" placeholder="Cliente" required>
    </div></div>

    <div class="input-group"><div class="input-container">
        <input type="text" name="tarjeta" placeholder="Tarjeta" required>
    </div></div>

    <div class="input-group"><div class="input-container">
        <select name="nivel" required>
            <option value="">Selecciona nivel</option>
            <option value="FOLIATTI FAN">FOLIATTI FAN</option>
            <option value="TREBOL">TREBOL</option>
            <option value="CORAZON">CORAZON</option>
            <option value="CORAZON PLUS">CORAZON PLUS</option>
            <option value="ESPADA">ESPADA</option>
            <option value="ESPADA PLUS">ESPADA PLUS</option>
            <option value="DIAMANTE">DIAMANTE</option>
            <option value="DIAMANTE PLUS">DIAMANTE PLUS</option>
        </select>
    </div></div>

    <div class="input-group"><div class="input-container">
        <input type="text" name="cantidad" placeholder="Cantidad" required>
    </div></div>

    <div class="input-group"><div class="input-container">
        <input type="text" name="apuesta" placeholder="Apuesta" required>
    </div></div>

    <div class="input-group"><div class="input-container">
        <select name="gerente" required>
            <option value="">Selecciona gerente</option>
            <option value="GERENTE 1">GERENTE 1</option>
            <option value="GERENTE 2">GERENTE 2</option>
            <option value="GERENTE 3">GERENTE 3</option>
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
                    document.getElementById('proveedor').style.background = '#d4edda';
                } else {
                    document.getElementById('proveedor').value = '';
                    document.getElementById('proveedor').style.background = '';
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
