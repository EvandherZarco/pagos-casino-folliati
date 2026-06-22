<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Visita de Proveedores</title>
    <link rel="stylesheet" href="../styles.css">
</head>
<body class="form-page">
<?php $hora = (int) date("H"); $fechaOperativa = ($hora < 4) ? date("Y-m-d", strtotime("-1 day")) : date("Y-m-d"); ?>

<form method="post" autocomplete="off">
    <h2>VISITA DE PROVEEDORES</h2>

    <div class="input-group"><div class="input-container">
        <select name="proveedor" required>
            <option value="">Selecciona proveedor</option>
            <option value="AGS">AGS</option>
            <option value="AINSWORTH">AINSWORTH</option>
            <option value="APEX">APEX</option>
            <option value="ARISTOCRAT">ARISTOCRAT</option>
            <option value="AURIFY">AURIFY</option>
            <option value="BALLY">BALLY</option>
            <option value="BETSTONE WINT">BETSTONE WINT</option>
            <option value="DREIDEL">DREIDEL</option>
            <option value="EGT">EGT</option>
            <option value="EIBE">EIBE</option>
            <option value="FBM">FBM</option>
            <option value="GOLD CLUB">GOLD CLUB</option>
            <option value="IGT">IGT</option>
            <option value="JVL">JVL</option>
            <option value="MERKUR GAMING">MERKUR GAMING</option>
            <option value="METRONIA">METRONIA</option>
            <option value="NOVOMATIC">NOVOMATIC</option>
            <option value="ORTIZ">ORTIZ</option>
            <option value="SHOW BALL">SHOW BALL</option>
            <option value="ZITRO">ZITRO</option>
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

    <div class="input-group"><div class="input-container">
        <input type="time" name="hora_inicial" required>
    </div></div>

    <div class="input-group"><div class="input-container">
        <input type="time" name="hora_final" required>
    </div></div>

    <div class="input-group"><div class="input-container">
        <input type="text" name="ingreso_al_site" placeholder="Ingreso al site" required>
    </div></div>

    <div class="input-group"><div class="input-container">
        <input type="text" name="actividades" placeholder="Actividades" required>
    </div></div>

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
