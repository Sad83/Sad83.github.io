<?php
//-------------------------------------------------------------------------------------------------------
/**ADD FRONTEND SCRIPT AND CSS **/
add_action('wp_enqueue_scripts', 'wooweb_cpe_peru_styles');
function wooweb_cpe_peru_styles()
{
  if (is_checkout()) {

    wp_enqueue_style(
      'wooweb-cpe-peru',
      WOOWEB_CPE_PERU_PLUGIN_URL . '/assets/css/wooweb-cpe-peru.css',
      array(),
      '1.1'
    );

    wp_register_script('wooweb-cpe-peru-js', WOOWEB_CPE_PERU_PLUGIN_URL . '/assets/js/wooweb-cpe-checkout.js', array('jquery'), '1.0', true);

    wp_enqueue_script('wooweb-cpe-peru-js');

    wp_localize_script('wooweb-cpe-peru-js', 'ajax_cpe_peru', array(
      'ajax_url' => admin_url('admin-ajax.php')
    ));
  }
}
//--- START IF -------------------------------------------------------------------------------------------------------
if (CpeUtils::keyValue($options, 'wooweb_cpe_peru_enabled_checkout') == 1) {

  add_action('woocommerce_checkout_process', 'wooweb_checkout_field_validation');
  function wooweb_checkout_field_validation()
  {
    // Check if set, if its not set add an error.

    if (!$_POST['wooweb_cpe_tipo_documento'] || $_POST['wooweb_cpe_tipo_documento'] == 'blank') {
      wc_add_notice(__('Selecciona el <b>tipo de documento</b>', 'wooweb_cpe_peru'), 'error');
    }

    $tipoDocumento = $_POST['wooweb_cpe_tipo_documento'];

    if (!$_POST['wooweb_cpe_registro']) {
      wc_add_notice(__('Ingrese en <b>nro</b> de registro', 'wooweb_cpe_peru'), 'error');
    }

    if ($tipoDocumento == 6) {
      if (!$_POST['wooweb_cpe_razonsocial']) {
        wc_add_notice(__('Ingrese la <b>razón social</b>', 'wooweb_cpe_peru'), 'error');
      }

      if (!$_POST['wooweb_cpe_domiciliofiscal']) {
        wc_add_notice(__('Ingrese el <b>domicilio fiscal</b>', 'wooweb_cpe_peru'), 'error');
      }
    } else if ($tipoDocumento != 6) {
      if (!$_POST['wooweb_cpe_razonsocial']) {
        wc_add_notice(__('Ingrese la <b>el Nombre</b>', 'wooweb_cpe_peru'), 'error');
      }
    }
  }
  //-------------------------------------------------------------------------------------------------------
  //Agregar campos personalizados en el checkout de WooCommerce
  add_action('woocommerce_before_order_notes', 'wooweb_add_fields_to_checkout');
  function wooweb_add_fields_to_checkout($checkout)
  {
    echo '<h3>' . __('Comprobante Electrónico', 'wooweb_cpe_peru') . '</h3>';

    $options = get_option(WOOWEB_CPE_PERU_SETTINGS);
    if (CpeUtils::keyValue($options, 'wooweb_cpe_peru_enabled_boletas') == 1) {
      woocommerce_form_field('wooweb_cpe_tipo_documento', array(
        'type'          => 'select',
        'class'         => array('wps-drop form-row form-row-first'),
        'label'         => __('Documento', 'wooweb_cpe_peru'),
        'options'       => array(
          'blank'    => __('Seleccione su documento', 'wooweb_cpe_peru'),
          '1'  => __('DNI', 'wooweb_cpe_peru'),
          '4'   => __('Carnet de Extranjería', 'wooweb_cpe_peru'),
          '7'   => __('Pasaporte', 'wooweb_cpe_peru')
        )
      ), $checkout->get_value('wooweb_cpe_tipo_documento'));
    } else {
      woocommerce_form_field('wooweb_cpe_tipo_documento', array(
        'type'          => 'select',
        'class'         => array('wps-drop form-row form-row-first'),
        'label'         => __('Documento', 'wooweb_cpe_peru'),
        'options'       => array(
          'blank'    => __('Seleccione su documento', 'wooweb_cpe_peru'),
          '1'  => __('DNI', 'wooweb_cpe_peru'),
          '6'  => __('RUC', 'wooweb_cpe_peru'),
          '4'   => __('Carnet de Extranjería', 'wooweb_cpe_peru'),
          '7'   => __('Pasaporte', 'wooweb_cpe_peru')
        )
      ), $checkout->get_value('wooweb_cpe_tipo_documento'));
    }


    woocommerce_form_field('wooweb_cpe_registro', array(
      'type' => 'text',
      'class' => array(
        'form-row form-row-last'
      ),
      'label' => __('N°'),
      'placeholder' => __('Ingrese el N°', 'wooweb_cpe_peru'),
      'required' => true,
    ), $checkout->get_value('wooweb_cpe_registro'));

    woocommerce_form_field('wooweb_cpe_razonsocial', array(
      'type' => 'text',
      'class' => array(
        'form-row form-row-wide'
      ),
      'label' => __('Nombre', 'wooweb_cpe_peru'),
      'placeholder' => __('Nombre o Razon social', 'wooweb_cpe_peru'),
      'required' => true,
    ), $checkout->get_value('wooweb_cpe_razonsocial'));

    woocommerce_form_field('wooweb_cpe_domiciliofiscal', array(
      'type' => 'text',
      'class' => array(
        'form-row form-row wooweb-company'
      ),
      'label' => __('Domicilio fiscal', 'wooweb_cpe_peru'),
      'placeholder' => __('Domicilio fiscal', 'wooweb_cpe_peru'),
      'required' => true,
    ), $checkout->get_value('wooweb_cpe_domiciliofiscal'));

    woocommerce_form_field('wooweb_cpe_ubigeo', array(
      'type' => 'text',
      'class' => array(
        'form-row form-row ubigeo'
      ),
      'label' => __('Ubigeo', 'wooweb_cpe_peru'),
      'placeholder' => __('Ingrese el ubigeo', 'wooweb_cpe_peru'),
      'required' => true,
    ), $checkout->get_value('wooweb_cpe_ubigeo'));
  }
  //-------------------------------------------------------------------------------------------------------
  //agregar campos a pedido
  add_action('woocommerce_checkout_update_order_meta', 'wooweb_checkout_field_update_order_meta');
  function wooweb_checkout_field_update_order_meta($order_id)
  {

    if (!empty($_POST['wooweb_cpe_tipo_documento'])) {
      update_post_meta($order_id, 'wooweb_cpe_peru_tipo_documento', sanitize_text_field($_POST['wooweb_cpe_tipo_documento']));
    }

    if (!empty($_POST['wooweb_cpe_registro'])) {
      update_post_meta($order_id, 'wooweb_cpe_peru_registro', sanitize_text_field($_POST['wooweb_cpe_registro']));
    }

    if (!empty($_POST['wooweb_cpe_razonsocial'])) {
      update_post_meta($order_id, 'wooweb_cpe_peru_razonsocial', sanitize_text_field($_POST['wooweb_cpe_razonsocial']));
    }

    //if ($tipoDocumento == 6)  {
    if (!empty($_POST['wooweb_cpe_domiciliofiscal'])) {
      update_post_meta($order_id, 'wooweb_cpe_peru_domiciliofiscal', sanitize_text_field($_POST['wooweb_cpe_domiciliofiscal']));
    }

    if (!empty($_POST['wooweb_cpe_ubigeo'])) {
      update_post_meta($order_id, 'wooweb_cpe_peru_ubigeo', sanitize_text_field($_POST['wooweb_cpe_ubigeo']));
    }
    //}
  }
  //-------------------------------------------------------------------------------------------------------
} //--- END IF

//-------------------------------------------------------------------------------------------------------
//Display custom fields about the Sunat invoice in the order thank you page
add_filter('woocommerce_order_details_after_customer_details', 'wooweb_display_document_data_thankyoupage', 10, 1);
function wooweb_display_document_data_thankyoupage($order)
{

  if (version_compare(get_option('woocommerce_version'), '3.0.0', ">=")) {
    $order_id = $order->get_id();
    $order_status = $order->get_status();
  } else {
    $order_id = $order->id;
    $order_status = $order->status;
  }

  $cpeTypeId = CpeUtils::getCPETypeID($order_id);

  if ($cpeTypeId != '') {

    $cpeName = CpeUtils::tipoDocumento($cpeTypeId);

    $nroCPE = CpeUtils::getNroCPE($order_id);

    echo '<h2 class="woocommerce-column__title">' . __('Comprobante SUNAT', 'wooweb_cpe_peru') . '</h2>';
    echo '<address><strong>' . __('Tipo de Documento', 'wooweb_cpe_peru') . ':</strong> ' . $cpeName . '<br/>';
    echo '<strong>' . __('N. ', 'wooweb_cpe_peru') . $cpeName . ':</strong> ' . get_post_meta($order_id, 'wooweb_cpe_peru_registro', true) . '<br/>';
    echo '<strong>' . __('Nombre', 'wooweb_cpe_peru') . ':</strong> ' . get_post_meta($order_id, 'wooweb_cpe_peru_razonsocial', true) . '<br/>';

    if ($cpeTypeId == 6) { //Invoice
      echo '<strong>' . __('Domicilio Fiscal', 'wooweb_cpe_peru') . ':</strong> ' . get_post_meta($order_id, 'wooweb_cpe_peru_domiciliofiscal', true) . '<br/>';
      echo '<strong>' . __('UBIGEO', 'wooweb_cpe_peru') . ':</strong> ' . get_post_meta($order_id, 'wooweb_cpe_peru_ubigeo', true) . '<br/><br/>';
    }

    if (!$nroCPE || $nroCPE == '') {
      if (!$order->is_paid() || $order_status != 'completed' || $order_status != 'processing') {
        echo __("Su orden aún esta pendiente, se emitirá el comprobante una vez se pague o complete el pedido", 'wooweb_cpe_peru');
      }
    } else {
      //imprime los botones para la descarga de comprobantes
      CpeUtils::getCPEUrls($order_id, false, true);
    }

    echo "</address>";
  }
}
//-------------------------------------------------------------------------------------------------------
add_filter('woocommerce_email_after_order_table', 'wooweb_cpe_peru_email_order_custom_fields');
function wooweb_cpe_peru_email_order_custom_fields($order)
{

  if (version_compare(get_option('woocommerce_version'), '3.0.0', ">=")) {
    $order_id = $order->get_id();
    $order_status = $order->get_status();
  } else {
    $order_id = $order->id;
    $order_status = $order->status;
  }

  $cpeTypeId = CpeUtils::getCPETypeID($order_id);

  if ($cpeTypeId != '') {

    $cpeName = CpeUtils::tipoDocumento($cpeTypeId);

    //echo $order->is_paid();
    $nroCPE = CpeUtils::getNroCPE($order_id);

    //echo '<section class="woocommerce-customer-details">';
    echo '<h2 class="woocommerce-column__title">' . __('Comprobante SUNAT', 'wooweb_cpe_peru') . '</h2>';
    echo '<address style="padding:12px;color:#636363;border:1px solid #e5e5e5"><strong>' . __('Tipo de Documento', 'wooweb_cpe_peru') . ':</strong> ' . $cpeName . '<br/>';
    echo '<strong>' . __('N. ', 'wooweb_cpe_peru') . $cpeName . ':</strong> ' . get_post_meta($order_id, 'wooweb_cpe_peru_registro', true) . '<br/>';
    echo '<strong>' . __('Nombre', 'wooweb_cpe_peru') . ':</strong> ' . get_post_meta($order_id, 'wooweb_cpe_peru_razonsocial', true) . '<br/>';

    if ($cpeTypeId == 6) {
      echo '<strong>' . __('Domicilio Fiscal', 'wooweb_cpe_peru') . ':</strong> ' . get_post_meta($order_id, 'wooweb_cpe_peru_domiciliofiscal', true) . '<br/>';
      echo '<strong>' . __('UBIGEO', 'wooweb_cpe_peru') . ':</strong> ' . get_post_meta($order_id, 'wooweb_cpe_peru_ubigeo', true) . '<br/><br/>';
    }

    if (!$nroCPE || $nroCPE == '') {
      if (!$order->is_paid() || $order_status != 'completed' || $order_status != 'processing') {
        echo __("Su orden aún esta pendiente, se emitirá el comprobante una vez se pague o complete el pedido", 'wooweb_cpe_peru');
      }
    } else {
      //imprime los botones para la descarga de comprobantes
      CpeUtils::getCPEUrls($order_id, false, true);
    }

    echo "</address><br/>";
  }
}
//-------------------------------------------------------------------------------------------------------