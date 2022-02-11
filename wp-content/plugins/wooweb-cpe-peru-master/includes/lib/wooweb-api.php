<?php

class CpeFlApi
{

  public static function cpe_register_document($order)
  {
    $settings = CpeDataFl::_getDataSettings();
    $taxes = CpeDataFl::_getTaxes($order->get_items('tax'), $settings);
    $frmClient = CpeDataFl::getDataFormClient($order);
    $shippingTotals = CpeDataFl::getShippingTotals($order, $settings, $taxes);
    $items = CpeDataFl::createItemsArrayDocument($order, $taxes, $settings, $shippingTotals);
    $discounts = CpeDataFl::getGlobalDiscount($order, $taxes, $items);

    $jsonDocument = CpeDataFl::createInvoiceJSON($order, $items, $taxes, $settings, $shippingTotals, $frmClient, $discounts);

    error_log('"WOOWEB-DEBUG-INVOICE: "'.$jsonDocument);

    $response = '';

    if (!$frmClient['nroCPE']) {
      $response = CpeUtils::cpe_curl_post($jsonDocument, '/api/documents');
    }

    //error_log(json_encode($response));
    //error_log($jsonDocument);
    //error_log(json_encode($response));

    return $response;
  }

  public static function cpe_get_identification($documento, $nroDocumento)
  {

    $rutaApi =  'api/service/' . $documento . '/' . $nroDocumento;

    CpeUtils::cpe_curl_get($rutaApi);
  }
}
