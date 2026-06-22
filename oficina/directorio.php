<!DOCTYPE html>
<!-- Desarrollado por Evandher Zarco (ZarcoMecatronics) - Mayo 2026 -->
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Directorio de Proveedores</title>
    <link rel="stylesheet" href="styles.css">
    <style>
        body {
            background: #0d3320;
            min-height: 100vh;
            padding: 28px 24px;
            font-family: 'DM Sans', sans-serif;
        }
        .header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 24px;
            padding-bottom: 20px;
            border-bottom: 1px solid rgba(255,255,255,0.12);
        }
        .header h1 {
            color: white;
            font-size: 17px;
            font-weight: 600;
            letter-spacing: 0.5px;
        }
        .header a {
            color: rgba(255,255,255,0.7);
            background: rgba(255,255,255,0.1);
            border: 1px solid rgba(255,255,255,0.15);
            padding: 7px 16px;
            border-radius: 8px;
            text-decoration: none;
            font-size: 13px;
            transition: background 0.15s;
        }
        .header a:hover { background: rgba(255,255,255,0.18); }
        .buscador {
            width: 100%;
            padding: 11px 18px;
            border-radius: 10px;
            border: 1px solid rgba(255,255,255,0.15);
            background: rgba(255,255,255,0.08);
            color: white;
            font-size: 14px;
            font-family: 'DM Sans', sans-serif;
            margin-bottom: 20px;
            outline: none;
            transition: border-color 0.15s;
        }
        .buscador::placeholder { color: rgba(255,255,255,0.4); }
        .buscador:focus { border-color: rgba(255,255,255,0.4); background: rgba(255,255,255,0.12); }
        .grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(260px, 1fr));
            gap: 14px;
        }
        .card {
            background: rgba(255,255,255,0.07);
            border-radius: 12px;
            padding: 16px 18px;
            border: 1px solid rgba(255,255,255,0.1);
            transition: background 0.15s;
        }
        .card:hover { background: rgba(255,255,255,0.11); }
        .card h2 {
            color: white;
            font-size: 14px;
            font-weight: 600;
            letter-spacing: 0.5px;
            margin-bottom: 10px;
            padding-bottom: 8px;
            border-bottom: 1px solid rgba(255,255,255,0.12);
        }
        .contacto { margin-bottom: 6px; }
        .contacto .nombre {
            color: rgba(255,255,255,0.88);
            font-size: 13px;
            font-weight: 500;
        }
        .contacto .dato {
            color: rgba(255,255,255,0.5);
            font-size: 12px;
            margin-top: 1px;
        }
        .email {
            margin-top: 10px;
            padding-top: 8px;
            border-top: 1px solid rgba(255,255,255,0.08);
            color: #6ee7b7;
            font-size: 12px;
            word-break: break-all;
        }
        .link {
            color: #6ee7b7;
            font-size: 11px;
            word-break: break-all;
            display: block;
            margin-top: 4px;
        }
        .card.hidden { display: none; }
    </style>
</head>
<body>

<div class="header">
    <h1>📋 Directorio de Proveedores</h1>
    <a href="menu.php">⬅ Menú</a>
</div>

<input type="text" class="buscador" id="buscador" placeholder="Buscar proveedor, nombre o teléfono...">

<div class="grid" id="grid">

    <!-- Datos de ejemplo: los contactos reales no se publican en el repositorio -->
    <div class="card" data-search="proveedor ejemplo a contacto ejemplo uno">
        <h2>PROVEEDOR EJEMPLO A</h2>
        <div class="contacto"><div class="nombre">CONTACTO EJEMPLO 1</div><div class="dato">📞 55-0000-0000</div></div>
        <div class="email">✉ soporte@ejemplo.com</div>
    </div>

    <div class="card" data-search="proveedor ejemplo b contacto ejemplo dos">
        <h2>PROVEEDOR EJEMPLO B</h2>
        <div class="contacto"><div class="nombre">CONTACTO EJEMPLO 2</div><div class="dato">📞 55-0000-0001</div></div>
    </div>

</div>

<script>
document.getElementById('buscador').addEventListener('input', function () {
    const q = this.value.toLowerCase().trim();
    document.querySelectorAll('.card').forEach(card => {
        const texto = card.dataset.search + ' ' + card.innerText.toLowerCase();
        card.classList.toggle('hidden', q !== '' && !texto.includes(q));
    });
});
</script>
</body>
</html>
