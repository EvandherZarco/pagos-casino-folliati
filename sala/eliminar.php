<?php
require 'db.php';
require 'renumerar.php';      // incluye push_helpers.php internamente
require_once 'push_helpers.php';

$id = (int)($_GET['id'] ?? 0);
if ($id) {
    $r = mysqli_fetch_assoc(mysqli_query($conex, "SELECT fecha, cerrado FROM pagos WHERE id=$id"));
    mysqli_query($conex, "DELETE FROM pagos WHERE id=$id");

    if ($r) {
        // Si el pago ya estaba cerrado (ya viajó a oficina alguna vez),
        // avisarle que se eliminó. Oficina NO lo borra físico — lo marca
        // como anulado, conservando rastro para auditoría.
        if ((int)$r['cerrado'] === 1) {
            $payloadDelete = ['id_sala' => $id, 'motivo' => 'Eliminado desde sala'];
            $resultado = enviarPushOficina($payloadDelete, 'eliminar_pago.php');
            if (!$resultado['ok']) {
                guardarPushEvento($conex, $id, 'delete', $payloadDelete, $resultado['error']);
            }
        }

        // Renumera el resto del día Y resincroniza los pagos cerrados
        // restantes cuyo folio pudo haber cambiado por el borrado.
        renumerar_dia($conex, $r['fecha']);
    }
}
header('Location: lista.php');
exit;
