<!DOCTYPE html>
<!-- Desarrollado por Evandher Zarco (ZarcoMecatronics) - Mayo 2026 -->
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Información de Máquinas</title>
    <link rel="stylesheet" href="styles.css">
    <style>
        .search-box {
            width: 520px;
            text-align: center;
        }
        .result-card {
            background: white;
            border-radius: 16px;
            padding: 24px 28px;
            margin-top: 20px;
            width: 100%;
            box-sizing: border-box;
            display: none;
        }
        .result-card h3 {
            color: #1a1a2e;
            margin: 0 0 18px 0;
            font-size: 16px;
            text-align: center;
            letter-spacing: 1px;
        }
        .campo-row {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 12px;
            background: #f0f0f0;
            border-radius: 10px;
            padding: 10px 14px;
            gap: 10px;
        }
        .campo-info {
            display: flex;
            flex-direction: column;
            flex: 1;
        }
        .campo-label {
            font-size: 10px;
            color: #888;
            text-transform: uppercase;
            letter-spacing: 1px;
            margin-bottom: 2px;
        }
        .campo-valor {
            font-size: 15px;
            font-weight: bold;
            color: #1a1a2e;
        }
        .btn-copy {
            background: #4a00e0;
            color: white;
            border: none;
            border-radius: 8px;
            padding: 6px 14px;
            font-size: 12px;
            cursor: pointer;
            white-space: nowrap;
            transition: background 0.2s;
        }
        .btn-copy:hover { background: #3700b3; }
        .btn-copy.copiado { background: #2ecc71; }
        .btn-copy-all {
            width: 100%;
            margin-top: 8px;
            padding: 12px;
            font-size: 14px;
            font-weight: bold;
            border-radius: 12px;
            background: #1a1a6e;
        }
        .btn-copy-all:hover { background: #12126e; }
        .not-found {
            color: #e74c3c;
            font-size: 14px;
            margin-top: 12px;
            display: none;
        }
        .asset-input {
            font-size: 22px !important;
            text-align: center;
            letter-spacing: 2px;
        }
    </style>
</head>
<body class="form-page">
<div class="search-box">
    <h2>INFORMACIÓN DE MÁQUINAS</h2>

    <div class="input-group">
        <div class="input-container">
            <input type="number" id="asset" class="asset-input" placeholder="Número de asset" autocomplete="off" min="1">
        </div>
    </div>

    <p class="not-found" id="not-found">⚠ Asset no encontrado en el catálogo</p>

    <div class="result-card" id="result-card">
        <h3>📋 DATOS DE LA MÁQUINA</h3>

        <div class="campo-row">
            <div class="campo-info">
                <span class="campo-label">Asset</span>
                <span class="campo-valor" id="val-asset">—</span>
            </div>
            <button class="btn-copy" onclick="copiar('val-asset', this)">Copiar</button>
        </div>

        <div class="campo-row">
            <div class="campo-info">
                <span class="campo-label">Proveedor</span>
                <span class="campo-valor" id="val-proveedor">—</span>
            </div>
            <button class="btn-copy" onclick="copiar('val-proveedor', this)">Copiar</button>
        </div>

        <div class="campo-row">
            <div class="campo-info">
                <span class="campo-label">Modelo</span>
                <span class="campo-valor" id="val-modelo">—</span>
            </div>
            <button class="btn-copy" onclick="copiar('val-modelo', this)">Copiar</button>
        </div>

        <div class="campo-row">
            <div class="campo-info">
                <span class="campo-label">Número de Serie</span>
                <span class="campo-valor" id="val-serie">—</span>
            </div>
            <button class="btn-copy" onclick="copiar('val-serie', this)">Copiar</button>
        </div>

        <button class="btn-copy btn-copy-all" onclick="copiarTodo()">📋 Copiar todo</button>
    </div>

    <a href="menu.php" class="volver-btn" style="display:block;margin-top:16px;text-align:center;">⬅ Volver al menú</a>
</div>

<script>
let debounce;

document.getElementById('asset').addEventListener('input', function () {
    clearTimeout(debounce);
    const val = this.value.trim();
    document.getElementById('result-card').style.display = 'none';
    document.getElementById('not-found').style.display = 'none';

    if (val.length < 1) return;

    debounce = setTimeout(() => buscar(val), 400);
});

function buscar(asset) {
    fetch('get_maquina.php?asset=' + encodeURIComponent(asset))
        .then(r => r.json())
        .then(data => {
            if (data.found) {
                document.getElementById('val-asset').textContent  = asset;
                document.getElementById('val-proveedor').textContent = data.proveedor;
                document.getElementById('val-modelo').textContent    = data.modelo;
                document.getElementById('val-serie').textContent     = data.serie;
                document.getElementById('result-card').style.display = 'block';
                document.getElementById('not-found').style.display   = 'none';
            } else {
                document.getElementById('result-card').style.display = 'none';
                document.getElementById('not-found').style.display   = 'block';
            }
        });
}

function copiar(id, btn) {
    const texto = document.getElementById(id).textContent;
    navigator.clipboard.writeText(texto).then(() => {
        btn.textContent = '✔ Copiado';
        btn.classList.add('copiado');
        setTimeout(() => {
            btn.textContent = 'Copiar';
            btn.classList.remove('copiado');
        }, 1500);
    });
}

function copiarTodo() {
    const asset     = document.getElementById('val-asset').textContent;
    const proveedor = document.getElementById('val-proveedor').textContent;
    const modelo    = document.getElementById('val-modelo').textContent;
    const serie     = document.getElementById('val-serie').textContent;
    const texto = `Asset: ${asset} | Proveedor: ${proveedor} | Modelo: ${modelo} | Serie: ${serie}`;
    const btn = document.querySelector('.btn-copy-all');
    navigator.clipboard.writeText(texto).then(() => {
        btn.textContent = '✔ ¡Copiado!';
        btn.classList.add('copiado');
        setTimeout(() => {
            btn.textContent = '📋 Copiar todo';
            btn.classList.remove('copiado');
        }, 1500);
    });
}
</script>
</body>
</html>
