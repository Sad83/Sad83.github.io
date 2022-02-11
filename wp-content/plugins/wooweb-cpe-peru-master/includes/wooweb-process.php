<?php

add_action('wp_ajax_nopriv_cpe_peru_getCliente', 'wooweb_cpe_peru_getCliente');
add_action('wp_ajax_cpe_peru_getCliente', 'wooweb_cpe_peru_getCliente');
function wooweb_cpe_peru_getCliente()
{
    $tipoDocumento =  intval($_POST['tipoDocumento']);
    $nroregistro =  $_POST['nroDocumento'];

    $documento = 'dni';
    if ($tipoDocumento == 6 || $tipoDocumento == 'RUC') {
        $documento = 'ruc';
    } else if ($tipoDocumento == 1 || $tipoDocumento == 'DNI') {
        $documento = 'dni';
    }

    CpeFlApi::cpe_get_identification($documento, $nroregistro);
}
//-------------------------------------------------------------------------------------------------------
function wooweb_cpe_peru_crearComprobanteSUNAT($porder)
{
    global $woocommerce;
    $order = wc_get_order($porder);

    $responseValidation = array('success' => 'false', 'message' => 'La factura tiene un monto cero');
    if ($order->get_total() <= 0) {
        return $responseValidation;
    }

    $SUNAT_respuesta = CpeFlApi::cpe_register_document($order);

    if (CpeUtils::keyValue($SUNAT_respuesta, 'success') == true) {
        if ($SUNAT_respuesta['data']['state_type_description'] == 'Aceptado') {
            update_post_meta($order->get_id(), 'wooweb_cpe_peru_doc_numero', sanitize_text_field($SUNAT_respuesta['data']['number']));
            update_post_meta($order->get_id(), 'wooweb_cpe_peru_doc_externalid', sanitize_text_field($SUNAT_respuesta['data']['external_id']));
            $order->add_order_note("Comprobante Generado correctamente");
        } else if ($SUNAT_respuesta['data']['state_type_description'] == 'Rechazado') {
            $order->add_order_note("¡Su comprobante ha sido rechazado!");
        }
    } else if (CpeUtils::keyValue($SUNAT_respuesta, 'success') == false) {
        $order->add_order_note("Hubo un problema, porfavor revisa lo siguiente, " . $SUNAT_respuesta['message'] . '.<br/>Si desconoces el error, consulta al soporte de tu sistema de facturación electronica<br/>');
    } else {
        $order->add_order_note("Hubo un problema, le recomendamos revisar los datos de configuración del plugin de " . CpeUtils::getProvider('name'));
    }
}
