<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Handpay</title>
    <link rel="stylesheet" href="../styles.css">
</head>
<body class="form-page">
<?php $hora = (int) date("H"); $fechaOperativa = ($hora < 4) ? date("Y-m-d", strtotime("-1 day")) : date("Y-m-d"); ?>

<form method="post" autocomplete="off">
    <h2>HANDPAY</h2>

    <div class="input-group"><div class="input-container">
        <input type="date" name="fecha" value="<?= $fechaOperativa ?>" required>
    </div></div>

    <div class="input-group"><div class="input-container">
        <input type="time" name="hora" required>
    </div></div>

    <div class="input-group"><div class="input-container">
        <input type="text" name="cantidad" placeholder="Cantidad" required>
    </div></div>

    <div class="input-group"><div class="input-container">
        <input type="text" name="blue_point" placeholder="Blue Point (0 si no aplica)" required>
    </div></div>

    <div class="input-group"><div class="input-container">
        <input type="text" name="cliente" placeholder="Cliente" required style="text-transform: uppercase">
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
        <select name="gerente" required>
            <option value="">Selecciona gerente</option>
            <option value="GERENTE 1">GERENTE 1</option>
            <option value="GERENTE 2">GERENTE 2</option>
            <option value="GERENTE 3">GERENTE 3</option>
            <option value="GERENTE 4 (MULTI)">GERENTE 4 (MULTI)</option>
            <option value="GERENTE 5 (MULTI)">GERENTE 5 (MULTI)</option>
            <option value="GERENTE 6 (MULTI)">GERENTE 6 (MULTI)</option>
        </select>
    </div></div>

    <input type="submit" name="send" class="btn" value="Enviar">
    <a href="../registros.php" class="volver-btn">← Volver</a>
</form>

<?php include("send.php") ?>

<script>
window.onload = function () {
    var m = document.getElementById("mensaje");
    if (m) setTimeout(function () { m.style.display = "none"; }, 3000);
};
</script>
</body>
</html>
