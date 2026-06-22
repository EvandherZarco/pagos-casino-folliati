<?php
include("../conexion.php");
$hora = (int) date('H');
$fechaOperativa = ($hora < 4) ? date('Y-m-d', strtotime('-1 day')) : date('Y-m-d');

if (isset($_POST['actualizar'])) {
    $id         = (int) $_POST['id'];
    $asset      = trim($_POST['asset']);
    $proveedor  = trim($_POST['proveedor']);
    $modelo     = trim($_POST['modelo']);
    $serie      = trim($_POST['serie']);
    $tecnico    = trim($_POST['tecnico']);
    $tipo       = trim($_POST['tipo_de_falla']);
    $falla      = trim($_POST['falla']);
    $solucion   = trim($_POST['solucion']);
    $fecha      = trim($_POST['fecha']);
    $turno      = trim($_POST['turno']);

    $stmt = mysqli_prepare($conex, "UPDATE datos SET asset=?, proveedor=?, modelo=?, serie=?, tecnico=?, tipo_de_falla=?, falla=?, solucion=?, fecha=?, turno=? WHERE id=?");
    mysqli_stmt_bind_param($stmt, "ssssssssssi", $asset, $proveedor, $modelo, $serie, $tecnico, $tipo, $falla, $solucion, $fecha, $turno, $id);
    mysqli_stmt_execute($stmt);
    mysqli_stmt_close($stmt);
    header("Location: lista.php");
    exit;
}

$id  = (int) $_GET['id'];
$res = mysqli_query($conex, "SELECT * FROM datos WHERE id = $id");
$r   = mysqli_fetch_assoc($res);
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Editar Apertura</title>
    <link rel="stylesheet" href="../styles.css">
</head>
<body class="form-page">
<form method="post" autocomplete="off">
    <h2>EDITAR APERTURA</h2>
    <input type="hidden" name="id" value="<?= $r['id'] ?>">

    <div class="input-group"><div class="input-container">
        <input type="number" name="asset" id="asset" value="<?= htmlspecialchars($r['asset']) ?>" required>
    </div></div>
    <div class="input-group"><div class="input-container">
        <input type="text" name="proveedor" id="proveedor" value="<?= htmlspecialchars($r['proveedor']) ?>" required>
    </div></div>
    <div class="input-group"><div class="input-container">
        <input type="text" name="modelo" id="modelo" value="<?= htmlspecialchars($r['modelo']) ?>" required>
    </div></div>
    <div class="input-group"><div class="input-container">
        <input type="text" name="serie" id="serie" value="<?= htmlspecialchars($r['serie']) ?>" required>
    </div></div>
    <div class="input-group"><div class="input-container">
        <select name="tecnico" required>
            <option value="">Selecciona técnico</option>
            <?php foreach(['TECNICO 1','TECNICO 2','EVANDHER ZARCO'] as $t): ?>
            <option value="<?= $t ?>" <?= $r['tecnico']==$t?'selected':'' ?>><?= $t ?></option>
            <?php endforeach; ?>
        </select>
    </div></div>
    <div class="input-group"><div class="input-container">
        <select name="tipo_de_falla" id="tipo_falla" required>
            <option value="">Selecciona tipo</option>
            <?php foreach(['HARDWARE','SOFTWARE','SISTEMA','MANTENIMIENTO'] as $t): ?>
            <option value="<?= $t ?>" <?= $r['tipo_de_falla']==$t?'selected':'' ?>><?= $t ?></option>
            <?php endforeach; ?>
        </select>
    </div></div>
    <div class="input-group"><div class="input-container">
        <input type="text" name="falla" id="falla" value="<?= htmlspecialchars($r['falla']) ?>" list="lista_fallas" autocomplete="off" required>
        <datalist id="lista_fallas"></datalist>
    </div></div>
    <div class="input-group"><div class="input-container">
        <input type="text" name="solucion" value="<?= htmlspecialchars($r['solucion']) ?>" required>
    </div></div>
    <div class="input-group"><div class="input-container">
        <input type="date" name="fecha" value="<?= $r['fecha'] ?>" required>
    </div></div>
    <div class="input-group"><div class="input-container">
        <select name="turno" required>
            <option value="">Selecciona turno</option>
            <?php foreach(['APERTURA','INTERMEDIO','CIERRE'] as $t): ?>
            <option value="<?= $t ?>" <?= $r['turno']==$t?'selected':'' ?>><?= $t ?></option>
            <?php endforeach; ?>
        </select>
    </div></div>

    <input type="submit" name="actualizar" class="btn" value="Guardar cambios">
    <a href="lista.php" class="volver-btn" style="display:block;margin-top:10px;text-align:center;">← Cancelar</a>
</form>
<script>
const opciones = {
    HARDWARE: {placeholder:"Selecciona una falla",items:["Abanico Gabinete","Abanico Procesador","Arnes Botonera","Backplane","Bateria","Boton Jugar","Botones Generales","Cable De Red Cpu","Cable De Red Ebox","Cable Disco Duro","Cambio De Cpu","Cable DB9 (SAS)","Chip Dañado","Contadores Mecanicos","Cpu Inhibido","Disco Duro","Eliminador De Monitor","Error Botton Panel Id","Error De Touch Secundario","Fuente De Poder Cpu","Fuente De Poder Gabinete","Fuera De Servicio","Kernel","Lampara Belly","Lampara Topper","Leds Botones","Main Board","Memoria Ram","Meters Board","Microswicht","Mother Board","Multilink Down","Pantalla Top","Pantalla Touch","Sensor Optico","Spninx","Tarjeta Controladora","Tarjeta De Video","Tarjeta Sas","Touch Descalibrado","Ufo","Ups","Cherry"]},
    SOFTWARE: {placeholder:"Selecciona una falla",items:["Ajustes De Volumen","Belly Door Open","Bono Inhibido","Call Attendant","Ciclado","Cierre De Aplicación","Error Critico De Memoria","Error De Juego","Error De Plataforma Top","Error De Progresivo","Error Sas","Error De Server","Error De Sistemas","Handpay","Juego Inhibido","Licencias","Logicbox Door Open","Main Door Open","Os Dañado","Plataforma Corrupta","Plataforma Inhibida","Ram clear","Sin Conexión","Tower Loght Win","Tom","Volcado Memoria"]},
    SISTEMA: {placeholder:"Selecciona una falla",items:["Borra Puntos","Eliminador Dañado","Corte De Energia","E Box Dañado","Error De Tranferecia","Maquina Bloqueada","Falla de Lectura de Tarjeta","Tarjetero Dañado","Tarjeta Bloqueada"]},
    MANTENIMIENTO: {placeholder:"Selecciona mantenimiento",items:["Tarjetero","Ebox","Placa Madre","Cableado","Limpieza De Maquina","Entre Islas"]}
};
document.getElementById("tipo_falla").addEventListener("change", function () {
    let config = opciones[this.value];
    let input = document.getElementById("falla");
    let dl = document.getElementById("lista_fallas");
    input.value = '';
    dl.innerHTML = '';
    if (config) {
        input.placeholder = config.placeholder;
        config.items.forEach(f => { let o = document.createElement("option"); o.value = f; dl.appendChild(o); });
    }
});
let assetTimer = null;
document.getElementById('asset').addEventListener('input', function () {
    clearTimeout(assetTimer);
    const asset = this.value.trim();
    if (asset.length < 3) return;
    assetTimer = setTimeout(() => {
        fetch('/reporte/get_maquina.php?asset=' + asset).then(r=>r.json()).then(data => {
            if (data.found) {
                document.getElementById('proveedor').value = data.proveedor;
                document.getElementById('modelo').value = data.modelo;
                document.getElementById('serie').value = data.serie;
            }
        });
    }, 400);
});
</script>
</body>
</html>
