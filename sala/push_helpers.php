<?php
// push_helpers.php — Funciones compartidas para el pipeline push HTTP sala -> oficina
// Usado por: cerrar_dia.php (push inmediato al cerrar el día)
//            reintentar_push.php (reintento periódico de envíos fallidos)
//            editar.php / eliminar.php (sync de ediciones/eliminaciones post-cierre)
//
// Si cambia el hostname de oficina, solo hay que tocar este archivo.
//
// <!-- Desarrollado por Evandher Zarco (ZarcoMecatronics) - Junio 2026 -->

const PUSH_MAX_INTENTOS = 20;

/**
 * Push HTTP a oficina — usa hostname de Windows en vez de IP fija,
 * así no se rompe cuando el DHCP cambia la IP de oficina.
 *
 * $endpoint: nombre del archivo PHP destino en oficina (sin ruta).
 *            Default 'recibir_pagos.php' para no romper las llamadas existentes
 *            de cerrar_dia.php. Para ediciones/eliminaciones, pasar
 *            'actualizar_pago.php' o 'eliminar_pago.php'.
 *
 * Devuelve ['ok' => bool, 'respuesta' => string|null, 'error' => string|null]
 */
function _pushConfig(): array {
    $archivo = __DIR__ . '/push_config.json';
    if (file_exists($archivo)) {
        $c = json_decode(file_get_contents($archivo), true);
        if ($c && isset($c['ip1'])) return $c;
    }
    // Fallback: usar hostname de Windows de oficina en vez de IP fija
    return ['ip1' => '10.10.10.65', 'ip2' => '172.26.0.149', 'puerto' => '80'];
}

function enviarPushOficina(array $payload, string $endpoint = 'recibir_pagos.php'): array {
    $cfg    = _pushConfig();
    $puerto = $cfg['puerto'] ?? '80';
    // array_filter elimina valores null/vacíos para no intentar IPs inexistentes
    $ips    = array_filter([$cfg['ip1'] ?? null, $cfg['ip2'] ?? null]);
    // ⚠️ Ajustar /reporte/ si la carpeta de oficina tiene otro nombre

    $json = json_encode($payload);
    $ultimoError = '';

    foreach ($ips as $ip) {
        $url = "http://{$ip}:{$puerto}/reporte/{$endpoint}";
        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => $json,
            CURLOPT_HTTPHEADER => ['Content-Type: application/json'],
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_CONNECTTIMEOUT => 3,
            CURLOPT_TIMEOUT => 8,
        ]);
        $respuesta = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlError = curl_error($ch);
        curl_close($ch);

        if ($respuesta !== false && $httpCode === 200) {
            $decoded = json_decode($respuesta, true);
            if ($decoded && ($decoded['ok'] ?? false)) {
                return ['ok' => true, 'respuesta' => $respuesta, 'error' => null];
            }
            $ultimoError = "Oficina respondió pero con error: " . $respuesta;
        } else {
            $ultimoError = "Fallo en $url — HTTP $httpCode — $curlError";
        }
    }

    return ['ok' => false, 'respuesta' => null, 'error' => $ultimoError];
}

/**
 * Guarda (o reemplaza) un payload pendiente de envío en push_pendiente.
 * Usado por cerrar_dia.php cuando el push inmediato falla.
 * (Sin cambios — sigue siendo solo para inserts por día completo.)
 */
function guardarPushPendiente($conex, string $fechaOp, string $archivo, string $dia, array $payload, string $error) {
    $json = json_encode($payload);
    $stmt = mysqli_prepare($conex,
        "INSERT INTO push_pendiente (fecha_operativa, archivo, dia, datos_json, intentos, ultimo_intento, estado, ultimo_error)
         VALUES (?, ?, ?, ?, 0, NOW(), 'pendiente', ?)
         ON DUPLICATE KEY UPDATE
            archivo = VALUES(archivo),
            dia = VALUES(dia),
            datos_json = VALUES(datos_json),
            ultimo_intento = NOW(),
            estado = 'pendiente',
            ultimo_error = VALUES(ultimo_error)"
    );
    mysqli_stmt_bind_param($stmt, 'sssss', $fechaOp, $archivo, $dia, $json, $error);
    mysqli_stmt_execute($stmt);
    mysqli_stmt_close($stmt);
}

/**
 * Guarda (o reemplaza) un evento pendiente de sync individual (update/delete)
 * en push_evento. Usado por editar.php / eliminar.php cuando el push
 * inmediato a oficina falla para un pago ya cerrado.
 *
 * La llave (id_sala, tipo) hace que una segunda edición fallida del mismo
 * pago REEMPLACE el evento pendiente anterior, en vez de apilarse — mismo
 * principio que guardarPushPendiente(), aplicado a eventos individuales.
 */
function guardarPushEvento($conex, int $idSala, string $tipo, array $payload, string $error) {
    $json = json_encode($payload);
    $stmt = mysqli_prepare($conex,
        "INSERT INTO push_evento (id_sala, tipo, datos_json, intentos, ultimo_intento, estado, ultimo_error)
         VALUES (?, ?, ?, 0, NOW(), 'pendiente', ?)
         ON DUPLICATE KEY UPDATE
            datos_json = VALUES(datos_json),
            ultimo_intento = NOW(),
            estado = 'pendiente',
            ultimo_error = VALUES(ultimo_error)"
    );
    mysqli_stmt_bind_param($stmt, 'isss', $idSala, $tipo, $json, $error);
    mysqli_stmt_execute($stmt);
    mysqli_stmt_close($stmt);
}

/**
 * Consulta (solo lectura) los pagos capturados MANUALMENTE en oficina
 * (sin id_sala) para una fecha dada — temporal, mientras dure la
 * transición de captura manual en oficina.
 *
 * A diferencia de enviarPushOficina(), esto es un GET, no un POST, y
 * NUNCA se guarda en una cola de reintentos: si oficina no responde,
 * simplemente no se muestra esa sección extra en sala. No es un dato
 * crítico que deba garantizarse, es informativo.
 *
 * Devuelve un arreglo de filas (vacío si falla o si no hay nada).
 */
function consultarPagosManualesOficina(string $fecha): array {
    $cfg    = _pushConfig();
    $puerto = $cfg['puerto'] ?? '80';
    // array_filter elimina valores null/vacíos para no intentar IPs inexistentes
    $ips    = array_filter([$cfg['ip1'] ?? null, $cfg['ip2'] ?? null]);

    foreach ($ips as $ip) {
        $url = "http://{$ip}:{$puerto}/reporte/listar_manual.php?fecha=" . urlencode($fecha);
        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_CONNECTTIMEOUT => 3,
            CURLOPT_TIMEOUT => 8,
        ]);
        $respuesta = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($respuesta !== false && $httpCode === 200) {
            $decoded = json_decode($respuesta, true);
            if ($decoded && ($decoded['ok'] ?? false)) {
                return $decoded['filas'] ?? [];
            }
        }
    }

    return [];
}
