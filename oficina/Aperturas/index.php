<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Apertura de Máquinas</title>
    <link rel="stylesheet" href="../styles.css">
</head>
<body class="form-page">
<?php $hora = (int) date("H"); $fechaOperativa = ($hora < 4) ? date("Y-m-d", strtotime("-1 day")) : date("Y-m-d"); ?>

<form method="post" autocomplete="off">
    <h2>APERTURA DE MAQUINAS</h2>

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
        <select name="tipo_de_falla" id="tipo_falla" required>
            <option value="">Selecciona tipo</option>
            <option value="HARDWARE">HARDWARE</option>
            <option value="SOFTWARE">SOFTWARE</option>
            <option value="SISTEMA">SISTEMA</option>
            <option value="MANTENIMIENTO">MANTENIMIENTO</option>
        </select>
    </div></div>

    <div class="input-group"><div class="input-container">
        <input type="text" name="falla" id="falla" placeholder="Selecciona una falla" list="lista_fallas" autocomplete="off" required>
        <datalist id="lista_fallas"></datalist>
    </div></div>

    <div class="input-group"><div class="input-container">
        <input type="text" name="solucion" placeholder="Solución" required>
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
let assetTimer = null;
document.getElementById('asset').addEventListener('input', function () {
    clearTimeout(assetTimer);
    const asset = this.value.trim();
    if (asset.length < 3) return;
    assetTimer = setTimeout(() => {
        fetch('/reporte/get_maquina.php?asset=' + asset)
            .then(r => r.json())
            .then(data => {
                const verde = '#d4edda';
                if (data.found) {
                    document.getElementById('proveedor').value = data.proveedor;
                    document.getElementById('modelo').value    = data.modelo;
                    document.getElementById('serie').value     = data.serie;
                    ['proveedor','modelo','serie'].forEach(id => document.getElementById(id).style.background = verde);
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

const opciones = {
    HARDWARE: {
        placeholder: "Selecciona una falla",
        items: ["Abanico Gabinete","Abanico Procesador","Arnes Botonera","Backplane","Bateria","Boton Jugar","Botones Generales","Cable De Red Cpu","Cable De Red Ebox","Cable Disco Duro","Cambio De Cpu","Cable DB9 (SAS)","Chip Dañado","Contadores Mecanicos","Cpu Inhibido","Disco Duro","Eliminador De Monitor","Error Botton Panel Id","Error De Touch Secundario","Fuente De Poder Cpu","Fuente De Poder Gabinete","Fuera De Servicio","Kernel","Lampara Belly","Lampara Topper","Leds Botones","Main Board","Memoria Ram","Meters Board","Microswicht","Mother Board","Multilink Down","Pantalla Top","Pantalla Touch","Sensor Optico","Spninx","Tarjeta Controladora","Tarjeta De Video","Tarjeta Sas","Touch Descalibrado","Ufo","Ups","Cherry"]
    },
    SOFTWARE: {
        placeholder: "Selecciona una falla",
        items: ["Ajustes De Volumen","Belly Door Open","Bono Inhibido","Call Attendant","Ciclado","Cierre De Aplicación","Error Critico De Memoria","Error De Juego","Error De Plataforma Top","Error De Progresivo","Error Sas","Error De Server","Error De Sistemas","Handpay","Juego Inhibido","Licencias","Logicbox Door Open","Main Door Open","Os Dañado","Plataforma Corrupta","Plataforma Inhibida","Ram clear","Sin Conexión","Tower Loght Win","Tom","Volcado Memoria"]
    },
    SISTEMA: {
        placeholder: "Selecciona una falla",
        items: ["Borra Puntos","Eliminador Dañado","Corte De Energia","E Box Dañado","Error De Tranferecia","Maquina Bloqueada","Falla de Lectura de Tarjeta","Tarjetero Dañado","Tarjeta Bloqueada"]
    },
    MANTENIMIENTO: {
        placeholder: "Selecciona mantenimiento",
        items: ["Tarjetero","Ebox","Placa Madre","Cableado","Limpieza De Maquina","Entre Islas"]
    }
};

document.getElementById("tipo_falla").addEventListener("change", function () {
    let tipo = this.value;
    let input = document.getElementById("falla");
    let datalist = document.getElementById("lista_fallas");
    let config = opciones[tipo];

    input.value = '';
    datalist.innerHTML = '';

    if (config) {
        input.placeholder = config.placeholder;
        config.items.forEach(f => {
            let o = document.createElement("option");
            o.value = f;
            datalist.appendChild(o);
        });
    } else {
        input.placeholder = 'Selecciona una falla';
    }
});
</script>
</body>
</html>
