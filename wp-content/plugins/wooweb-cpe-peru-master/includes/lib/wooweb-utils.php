<?php

class CpeUtils
{
    static function  cpe_curl_post($data, $rutaApi)
    {

        $curl = curl_init();

        $URL = self::getOption('wooweb_cpe_peru_url');
        $TOKEM = self::getOption('wooweb_cpe_peru_tokem');

        curl_setopt_array($curl, array(
            CURLOPT_URL => $URL .'/'. $rutaApi,
            CURLOPT_SSL_VERIFYPEER => 0, //añades esta línea
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => "",
            CURLOPT_CONNECTTIMEOUT => 120,
            CURLOPT_TIMEOUT => 120,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => "POST",
            CURLOPT_POSTFIELDS => $data, //$data
            CURLOPT_HTTPHEADER => array(
                "Authorization: Bearer " . $TOKEM,
                "Content-Type: application/json",
                "cache-control: no-cache"
            ),
        ));

        $response = curl_exec($curl);
        $err = curl_error($curl);

        curl_close($curl);


        if ($err) {
            error_log('"WOOWEB-DEBUG-INVOICE: "'.$err);
        } else {
            $SUNAT_respuesta = json_decode($response, true);
            error_log('"WOOWEB-DEBUG-INVOICE: "'.json_encode($SUNAT_respuesta));
            return $SUNAT_respuesta;
        }
    }

    static function cpe_curl_get($rutaApi)
    {

        $URL = CpeUtils::getOption('wooweb_cpe_peru_url');

        $TOKEM = self::getOption('wooweb_cpe_peru_tokem');

        $curl = curl_init();

        curl_setopt_array($curl, array(
            //'http://url/api/services/dni/45897656'
            CURLOPT_URL => $URL .'/'. $rutaApi,
            CURLOPT_SSL_VERIFYPEER => 0, //añades esta línea
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => "",
            CURLOPT_CONNECTTIMEOUT => 20,
            CURLOPT_TIMEOUT => 20,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => "GET",
            CURLOPT_POSTFIELDS => array(), //$data
            CURLOPT_HTTPHEADER => array(
                "Authorization: Bearer " . $TOKEM,
                "Content-Type: application/json",
                "cache-control: no-cache"
            ),
        ));


        $response = curl_exec($curl);
        $err = curl_error($curl);

        curl_close($curl);

        if ($err) {
            error_log('"WOOWEB-DEBUG-GETDOC: "'.$err);
        } else {
            $SUNAT_respuesta = json_decode($response, true);
            error_log("WOOWEB-DEBUG-GETDOC: ".json_encode($SUNAT_respuesta));
            wp_send_json($SUNAT_respuesta);
        }
        die();
    }

    static function getOptions()
    {
        $options = get_option(WOOWEB_CPE_PERU_SETTINGS);
        return $options;
    }

    static function getOption($option)
    {
        $opciones = self::getOptions();
        $valor = '';
        if (!is_array($opciones)) {
            $opciones[$option] = $valor;
        } else {
            if (!array_key_exists($option, $opciones)) {
                $opciones[$option] = $valor;
            } else {
                $valor = $opciones[$option];
            }
        }
        return $valor;
    }

    static function getPostMeta($order, $parameter)
    {
        return get_post_meta($order->get_id(), $parameter, true);
    }

    static function getApiUrl()
    {
        $URL = self::getOption('wooweb_cpe_peru_url');

        return $URL;
    }

    static function keyValue($array, $key)
    {
        if (is_array($array)) {
            $return = (array_key_exists($key, $array)) ? $array[$key] : '';
        } else {
            $return = '';
        }

        return $return;
    }

    static function getCPEUrls($orderId, $cdr, $xml)
    {

        $CPExternalID = self::getCPExternalId($orderId);
        $URL = self::getApiUrl();

        if ($CPExternalID != '') {
            echo '<a href="' . $URL . '/downloads/document/pdf/' . $CPExternalID . '" target="_blank" id="descargarPDF" data-orderid="' . $orderId . '" class="add_note button"><i class="dashicons-before dashicons-download"></i> PDF</a> ';

            if ($xml) {
                echo '<a href="' . $URL . '/downloads/document/xml/' . $CPExternalID . '" target="_blank" id="descargarXML" data-orderid="' . $orderId . '" class="add_note button"><i class="dashicons-before dashicons-download"></i> XML</a> ';
            }
            
            if ($cdr) {
                echo '<a href="' . $URL . '/downloads/document/cdr/' . $CPExternalID . '" target="_blank" id="descargarCDR" data-orderid="' . $orderId . '" class="add_note button"><i class="dashicons-before dashicons-download"></i> CDR</a>';
            }
        } else {
            echo "<b>Sin CPE</b>";
        }
    }

    static function getCPExternalId($orderId)
    {
        $var = get_post_meta($orderId, 'wooweb_cpe_peru_doc_externalid', true);
        return $var;
    }

    static function getNroCPE($orderId)
    {
        $var = get_post_meta($orderId, 'wooweb_cpe_peru_doc_numero', true);
        return $var;
    }

    static function getCPETypeID($orderId)
    {
        $var = get_post_meta($orderId, 'wooweb_cpe_peru_tipo_documento', true);
        return $var;
    }

    /**
     * Obtener impuestos de woocommerce by items
     */
    static function wooweb_get_taxes_by_item_taxes($igv, $item_taxes)
    {

        $tax_productos  = $igv;
        $tax_envio      = $igv;
        $type_tax_producto = 10; //gravada
        $type_tax_envio = 10; //gravada


        $count = 0;
        foreach ($item_taxes as $item) {

            $rate_label   = $item->get_label(); // Get label
            $rate_percent = $item->get_rate_percent(); // Get rate Id
            $tax_total   = $item->get_tax_total(); // Get tax total amount (for this rate)
            $ship_total  = $item->get_shipping_tax_total(); // Get shipping tax total amount (for this rate)

            if ($rate_label == 'IGV' || $rate_label == 'IGV-EXO'  ||  $rate_label == 'IGV-INA') {
                if ($count == 0) {
                    $tax_productos = $rate_percent;
                    $tax_envio = $rate_percent;

                    if ($rate_label == 'IGV-EXO') {
                        $type_tax_producto = '20';
                        $type_tax_envio = '20';
                    }

                    if ($rate_label == 'IGV-INA') {
                        $type_tax_producto = '30';
                        $type_tax_envio = '30';
                    }
                }

                if ($count <= 1) {

                    if ($tax_total > 0) {
                        $tax_productos = $rate_percent;

                        if ($rate_label == 'IGV-EXO') {
                            $type_tax_producto = '20';
                            $tax_productos = 0;
                        }

                        if ($rate_label == 'IGV-INA') {
                            $type_tax_producto = '30';
                            $tax_productos = 0;
                        }

                        if ($rate_label == 'IGV') {
                            $type_tax_producto = '10';
                        }
                    }

                    if ($ship_total > 0) {
                        $tax_envio = $rate_percent;

                        if ($rate_label == 'IGV-EXO') {
                            $type_tax_envio = '20';
                            $tax_envio = 0;
                        }

                        if ($rate_label == 'IGV-INA') {
                            $type_tax_envio = '30';
                            $tax_envio = 0;
                        }

                        if ($rate_label == 'IGV') {
                            $type_tax_envio = '10';
                        }
                    }
                }
            }


            $count = $count + 1;
        }

        return [$tax_productos, $tax_envio, $type_tax_producto, $type_tax_envio, $count];
    }

    /**
     * Obtener tipo de documento de identidad
     */
    static function tipoDocumento($idDoc)
    {
        $documento = "";
        switch ($idDoc) {
            case 'DNI':
                $documento = 'DNI';
                break;
            case 'Doc.trib.no.dom.sin.ruc':
                $documento = 'Doc.trib.no.dom.sin.ruc';
                break;
            case 'CE':
                $documento = 'CE';
                break;
            case 'RUC': //antes 4
                $documento = 'RUC';
                break;
            case 'Pasaporte':
                $documento = 'Pasaporte';
                break;

            case '1':
                $documento = 'DNI';
                break;
            case 'A':
                $documento = 'Doc.trib.no.dom.sin.ruc';
                break;
            case '4':
                $documento = 'CE';
                break;
            case '6': //antes 4
                $documento = 'RUC';
                break;
            case '7':
                $documento = 'Pasaporte';
                break;

            default:
                $documento = 'RUC';
                break;
        }
        return $documento;
    }

    /**
     * Calcular monto neto a partir del monto con IGV
     */
    static function calcularNeto($mtotal, $igv)
    {
        $total_neto = $mtotal / (1 + ('0.' . $igv));
        return $total_neto;
    }

    static function getProvider ($opt) {

        $return = '';

        $name = self::getOption('wooweb_cpe_peru_pname');
        $logo = self::getOption('wooweb_cpe_peru_plogo');
        $ayuda = self::getOption('wooweb_cpe_peru_payuda');

        if ($name == '') {
            $name = 'Facturación electrónica';
        }

        if ($logo == '') {
            $logo = '<img src="https://static.wooweb.site/media/2019/07/wooWeb-logotipo-200x64.png" style="max-width: 250px;" />';
        } else {
            $logo = '<img src="'.$logo.'" style="max-width: 250px;" />';
        }

        if ($ayuda == '') {
            $ayuda = '';
        } else {
            $ayuda = __('<a href="'.$ayuda.'"
            target="_blank">Solicitar ayuda</a>');
        }

        switch ($opt) {
            case 'name':
                $return = $name;
                break;
            case 'logo':
                $return = $logo;
                break;
            case 'help':
                $return = $ayuda;
                break;
        }

        return $return;

    }

}

$options = get_option(WOOWEB_CPE_PERU_SETTINGS);