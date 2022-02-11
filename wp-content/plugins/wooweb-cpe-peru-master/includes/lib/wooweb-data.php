<?php

class CpeDataFl
{

    /**
     * get data settings page to generate a invoince
     */
    static function _getDataSettings()
    {
        return array(
            'includeShipping' => CpeUtils::getOption('wooweb_cpe_peru_enabled_shipping'),
            'CURRENCY' => CpeUtils::getOption('wooweb_cpe_peru_currency'),
            'CAMBIO' => CpeUtils::getOption('wooweb_cpe_peru_tipo_cambio') == '' ? 4.1 : CpeUtils::getOption('wooweb_cpe_peru_tipo_cambio'),
            'UND' => CpeUtils::getOption('wooweb_cpe_peru_und'),
            'IGV' => CpeUtils::getOption('wooweb_cpe_peru_igv_actual') != '' ? CpeUtils::getOption('wooweb_cpe_peru_igv_actual') : 18,
            'useSKU' => CpeUtils::keyValue(CpeUtils::getOptions(), 'wooweb_cpe_peru_sku'),
        );
    }

    /**
     * get taxes woocommerce
     */
    public static function _getTaxes($orderTaxes, $settings)
    {
        $impuestos = CpeUtils::wooweb_get_taxes_by_item_taxes($settings['IGV'], $orderTaxes);

        return array(
            'IGV_PRODUCTOS' => $impuestos[0],
            'IGV_ENVIO' => $impuestos[1],
            'AFECTACION_IGV_ITEMS' => $impuestos[2],
            'AFECTACION_IGV_SHIPPING' => $impuestos[3],
            'IMPUESTOS_WOOCOMMERCE' => $impuestos[4],
        );
    }

    /**
     * get data entered by the client
     */
    public static function getDataFormClient($order)
    {

        $cust_tdoc = CpeUtils::getPostMeta($order, 'wooweb_cpe_peru_tipo_documento');

        if ($cust_tdoc == 1 || $cust_tdoc == 6) {
            $pais = 'PE';
        } else {
            $pais = $order->get_billing_country();
        }

        $doc_to_generate = '03'; //Boleta ó ticket
        $serie = CpeUtils::getOption('wooweb_cpe_peru_boleta_serie');
        if ($cust_tdoc == '6') {
            $doc_to_generate = '01'; //Invoice
            $serie = CpeUtils::getOption('wooweb_cpe_peru_invoice_serie');
        }

        $ubigeo = CpeUtils::getPostMeta($order, 'wooweb_cpe_peru_ubigeo');
        if ($pais == 'PE') {
            if ($ubigeo == '') {
                $ubigeo = "150101"; 
            }
        }

        return array(
            'cust_tdoc' => $cust_tdoc,
            'cust_ndoc' => CpeUtils::getPostMeta($order, 'wooweb_cpe_peru_registro'),
            'cust_rz'  => CpeUtils::getPostMeta($order, 'wooweb_cpe_peru_razonsocial'),
            'cust_df'  => CpeUtils::getPostMeta($order, 'wooweb_cpe_peru_domiciliofiscal'),
            'cust_pais' => $pais,
            'cust_ubi' => $ubigeo,
            'doc_to_generate' => $doc_to_generate,
            'doc_serie' => $serie,
            'nroCPE' => CpeUtils::getPostMeta($order, 'wooweb_cpe_peru_doc_numero'),
        );
    }

    /**
     * get shipping data order
     */
    public static function getShippingTotals($order, $setting, $taxes)
    {
        //datos del envío si esta activado ---------------------------------------------------------
        $includeShipping = $setting['includeShipping'];
        $order_shipping_total = $order->get_shipping_total() + $order->get_shipping_tax();
        if (!($order_shipping_total > 0)) {
            $includeShipping = 0; //desactivar la linea de pedido en la factura si este es monto cero
        }
        if ($includeShipping == 1) {
            $order_shipping_totalNeto = CpeUtils::calcularNeto($order_shipping_total, $taxes['IGV_ENVIO']);
            $order_shipping_totalIGV = $order_shipping_total - $order_shipping_totalNeto;
        } else {
            $order_shipping_totalNeto = '';
            $order_shipping_totalIGV = '';
        }

        return array(
            'shippingTotal' => $order_shipping_total,
            'shippingTotalNeto' => $order_shipping_totalNeto,
            'shippingTotalIGV' => $order_shipping_totalIGV
        );
    }

    /**
     * get discount data order
     */
    public static function getGlobalDiscount($order, $taxes, $items)
    {
        $discountTax = $order->get_discount_tax();
        if ($discountTax != 0) {
            $order_discount_total = $order->get_discount_tax() + $order->get_total_discount();
            $order_discount_totalNeto = CpeUtils::calcularNeto($order_discount_total, $taxes['IGV_ENVIO']);
        } else {
            $order_discount_total = $order->get_total_discount();
            $order_discount_totalNeto = $order_discount_total; 
        }

        //$order_total = floatval($items['total_gravada']);
        $order_total = $order->get_total()+$order_discount_total;

        $factor = ((100 * $order_discount_totalNeto) / $order_total) / 100;

        if ($order_discount_totalNeto != 0 && ($items['total_exonerada']!=0 || $items['total_inafecta']!=0)) {
            $SUNAT_descuentos[] = array(
                "codigo"        => '03',
                "descripcion"   => "Descuento",
                "factor"        => round($factor, 4),
                "monto"         => round($order_discount_totalNeto, 2),
                "base"          => $order_total
            );
        } else if ($order_discount_totalNeto != 0) {
            $SUNAT_descuentos[] = array(
                "codigo"        => '02',
                "descripcion"   => "Descuento",
                "factor"        => round($factor, 4),
                "monto"         => round($order_discount_totalNeto, 2),
                "base"          => $order_total
            );
        } else {
            $SUNAT_descuentos = [];
        }

        return array(
            'descuentos' => $SUNAT_descuentos,
            'descuentos_total' => $order_discount_total,
            'descuentos_totalNeto' => $order_discount_totalNeto
        );
    }

    /**
     * Create array items from order
     */
    public static function createItemsArrayDocument($order, $taxes, $setting, $shipping)
    {

        $orderItems = $order->get_items();

        $TOTAL_CPE_GRAVADA = 0;
        $TOTAL_CPE_INAFECTA = 0;
        $TOTAL_CPE_EXONERADA = 0;
        $SunatItems = [];

        foreach ($orderItems as $product_key => $orderItem) {

            $orderQuantity = $orderItem->get_quantity();
            //get Product values
            $product = $orderItem->get_product();
            $IDProducto = $product->get_id();

            $IDVariacion = $orderItem->get_variation_id();
            if ($IDVariacion) {
                $IDProducto = $IDVariacion;
                $product = new WC_Product_Variation($IDProducto);
            }

            if (self::_getDataSettings()['useSKU'] == 1) {
                $IDProducto = $product->get_sku();
            }

            $itemUnitPrice = ($orderItem->get_subtotal() + $orderItem->get_subtotal_tax()) / $orderQuantity;
            $itemUnitPriceNeto = CpeUtils::calcularNeto($itemUnitPrice, $taxes['IGV_PRODUCTOS']);

            $itemTotal = $itemUnitPrice * $orderQuantity;
            $itemTotalNeto = CpeUtils::calcularNeto($itemTotal, $taxes['IGV_PRODUCTOS']);
            $itemTotalIGV = $itemTotal - $itemTotalNeto;

            if ($itemTotal > 0) {
                $SunatItems[] = array(
                    "codigo_interno"            => $IDProducto, //OK
                    "descripcion"               => $orderItem->get_name(), //OK
                    "codigo_producto_sunat"     => "",

                    "unidad_de_medida"          => $setting['UND'], //OK
                    "cantidad"                  => $orderQuantity, //OK
                    "valor_unitario"            => $itemUnitPriceNeto,
                    "codigo_tipo_precio"        => "01",
                    "precio_unitario"           => $itemUnitPrice,
                    "codigo_tipo_afectacion_igv" => $taxes['AFECTACION_IGV_ITEMS'],

                    "total_base_igv"            => $itemTotalNeto, //VALOR
                    "porcentaje_igv"            => $taxes['IGV_PRODUCTOS'], //IGV 18% ó del año
                    "total_igv"                 => $itemTotalIGV, // IMPUESTOS = 72
                    "total_impuestos"           => $itemTotalIGV,
                    "total_valor_item"          => $itemTotalNeto, //VALOR
                    "total_item"                => $itemTotal //TOTAL CON IGV = 472
                );

                if ($taxes['AFECTACION_IGV_ITEMS'] == 10) {
                    $TOTAL_CPE_GRAVADA = $TOTAL_CPE_GRAVADA + $itemTotalNeto;
                } else if ($taxes['AFECTACION_IGV_ITEMS'] == 20) {
                    $TOTAL_CPE_EXONERADA = $TOTAL_CPE_EXONERADA + $itemTotalNeto;
                } else if ($taxes['AFECTACION_IGV_ITEMS'] == 30) {
                    $TOTAL_CPE_INAFECTA = $TOTAL_CPE_INAFECTA + $itemTotalNeto;
                }
            }
        }

        //datos del envío si esta activado ---------------------------------------------------------
        $totalShipping = floatval($shipping['shippingTotal']);
        if ($setting['includeShipping'] == 1 && $totalShipping > 0) {
            $SunatItems[] = array(
                "codigo_interno"              => 'ENVIO', //OK
                "descripcion"                 => 'ENVIO - ' . $order->get_shipping_method(),
                "codigo_producto_sunat"       => "",

                "unidad_de_medida"            => 'ZZ', //OK
                "cantidad"                    => 1, //OK
                "valor_unitario"              => round($shipping['shippingTotalNeto'], 2),
                "codigo_tipo_precio"          => "01", //Indagar que es
                "precio_unitario"             => round($shipping['shippingTotal'], 2),
                "codigo_tipo_afectacion_igv"  => $taxes['AFECTACION_IGV_SHIPPING'],

                "total_base_igv"              => round($shipping['shippingTotalNeto'], 2), //VALOR : 400
                "porcentaje_igv"              => round($taxes['IGV_ENVIO'], 2), //IGV 18% ó del año
                "total_igv"                   => round($shipping['shippingTotalIGV'], 2), // IMPUESTOS = 72
                "total_impuestos"             => round($shipping['shippingTotalIGV'], 2),
                "total_valor_item"            => round($shipping['shippingTotalNeto'], 2), //VALOR : 400
                "total_item"                  => round($shipping['shippingTotal'], 2), //TOTAL CON IGV = 472
            );

            if ($taxes['AFECTACION_IGV_SHIPPING'] == 10) {
                $TOTAL_CPE_GRAVADA = $TOTAL_CPE_GRAVADA + $shipping['shippingTotalNeto'];
            } else if ($taxes['AFECTACION_IGV_SHIPPING'] == 20) {
                $TOTAL_CPE_EXONERADA = $TOTAL_CPE_EXONERADA + $shipping['shippingTotalNeto'];
            } else if ($taxes['AFECTACION_IGV_SHIPPING'] == 30) {
                $TOTAL_CPE_INAFECTA = $TOTAL_CPE_INAFECTA + $shipping['shippingTotalNeto'];
            }
        }

        return array(
            'sunatItems' => $SunatItems,
            'total_gravada' => $TOTAL_CPE_GRAVADA,
            'total_inafecta' => $TOTAL_CPE_INAFECTA,
            'total_exonerada' => $TOTAL_CPE_EXONERADA
        );
    }

    /**
     * create invoice with all data
     */
    public static function createInvoiceJSON($order, $items, $taxes, $settings, $shipping, $clientData, $discounts)
    {
        $includeShipping = $settings['includeShipping'];
        //datos de la orden WooCommerce --------------------------------------------------------------
        $order_total = $order->get_total();

        $order_total_tax = 0;
        if ($taxes['IGV_ENVIO'] != 0) {
            $order_total_tax = $order->get_shipping_tax();
        }

        if ($taxes['IGV_PRODUCTOS'] != 0) {
            $order_total_tax = ($order->get_total_tax() - $order->get_shipping_tax()) + $order_total_tax;
        }

        if ($includeShipping != 1) {
            $order_total = $order_total - $shipping['shippingTotal']; //descuento el envio del total si este no se debe incluir
            $order_total_tax = $order_total_tax - $order->get_shipping_tax();
        }
        if ($items['total_inafecta'] > 0 || $items['total_exonerada'] > 0) {
            $order_total = $order_total + $discounts['descuentos_total'];
        }
        if ($taxes['IMPUESTOS_WOOCOMMERCE'] == 0) {
            $order_totalNeto = CpeUtils::calcularNeto($order_total, $taxes['IGV_PRODUCTOS']);
            $order_total_tax = $order_total - $order_totalNeto;
        }

        $fecha_de_emision = date('Y-m-d', current_time('timestamp', 0));
        $hora_de_emision  = date('H:i:s', current_time('timestamp', 0));
        if (CpeUtils::keyValue(CpeUtils::getOptions(), 'wooweb_cpe_peru_date') == 1) {
            $fecha_de_emision = date("Y-m-d", strtotime($order->order_date));
            $hora_de_emision  = date("h:i:s", strtotime($order->order_date));
        }

        if ($items['total_gravada'] > $discounts['descuentos_totalNeto']) {
            $items['total_gravada'] = $items['total_gravada'] - $discounts['descuentos_totalNeto'];
        }

        $totalGlobal = $order_total;
        $subTotal = $order_total;
        if ($items['total_inafecta'] > 0 || $items['total_exonerada'] > 0) {
            $totalGlobal = $totalGlobal - $discounts['descuentos_totalNeto'];
            if ($items['total_gravada']==0) {
                $subTotal = $order_total - $order_total_tax;
            }
        } 

        //INVOICE ARRAY --------------------------------------------------------------------------------------------
        $SUNAT_array = array(
            "serie_documento"               => $clientData['doc_serie'],
            "numero_documento"              => "#",
            "fecha_de_emision"              => $fecha_de_emision,
            "hora_de_emision"               => $hora_de_emision,

            "codigo_tipo_operacion"         => "0101",
            "codigo_tipo_documento"         => $clientData['doc_to_generate'],
            "codigo_tipo_moneda"            => $settings['CURRENCY'],
            "factor_tipo_de_cambio"         => round(floatval($settings['CAMBIO']), 2),
            "fecha_de_vencimiento"          => $fecha_de_emision,
            "numero_orden_de_compra"        => $order->get_id(),

            "datos_del_emisor" => array(
                "codigo_del_domicilio_fiscal" => "0000"
            ),

            "datos_del_cliente_o_receptor"            => array(
                "codigo_tipo_documento_identidad"     => $clientData['cust_tdoc'],
                "numero_documento"                    => $clientData['cust_ndoc'],
                "apellidos_y_nombres_o_razon_social"  => $clientData['cust_rz'],
                "codigo_pais"                         => 'PE',
                "ubigeo"                              => $clientData['cust_ubi'], //CAMBIAR
                "direccion"                           => $clientData['cust_df'],
                "correo_electronico"                  => $order->get_billing_email(),
                "telefono"                            => $order->get_billing_phone()
            ),

            "codigo_condicion_de_pago" => "01", //Pago al contado

            "descuentos"   => $discounts['descuentos'],

            "totales" => array(
                "total_descuentos"                => round($discounts['descuentos_totalNeto'], 2),
                "total_exportacion"               => "0.00",
                "total_operaciones_gravadas"      => round($items['total_gravada'], 2),
                "total_operaciones_inafectas"     => round($items['total_inafecta'], 2),
                "total_operaciones_exoneradas"    => round($items['total_exonerada'], 2),
                "total_operaciones_gratuitas"     => "0.00",
                "total_igv"                       => round($order_total_tax, 2),
                "total_impuestos"                 => round($order_total_tax, 2),
                "total_valor"                     => round($order_total - $order_total_tax, 2), //base imponible
                "subtotal_venta"                  => round($subTotal, 2), //base imponible
                "total_venta"                     => round($totalGlobal, 2)
            ),

            "items" => $items['sunatItems'],

        );
        $SUNAT_data_json = json_encode($SUNAT_array);

        return $SUNAT_data_json;
    }
}
