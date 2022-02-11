<?php
//-------------------------------------------------------------------------------------------------------
/**ADD FRONTEND SCRIPT AND CSS **/
add_action('admin_enqueue_scripts', 'wooweb_cpe_peru_styles_admin');
function wooweb_cpe_peru_styles_admin()
{

  wp_register_style(
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
//-------------------------------------------------------------------------------------------------------
//View cpe from order list
add_filter('manage_edit-shop_order_columns', 'wooweb_cpe_peru_new_order_column');
function wooweb_cpe_peru_new_order_column($columns)
{
  $columns['cpe'] = 'CPE';
  return $columns;
}
// Adding custom fields meta data for each new column
add_action('manage_shop_order_posts_custom_column', 'wooweb_cpe_peru_custom_orders_list_column_content', 20, 2);
function wooweb_cpe_peru_custom_orders_list_column_content($column, $post_id)
{
  switch ($column) {
    case 'cpe':
      // Get custom post meta data
      CpeUtils::getCPEUrls($post_id, false, false);
      break;
  }
}
//-------------------------------------------------------------------------------------------------------
//Permite emitir/ver comprobante desde el detalle de pedido
/**
 * Display field sunat values on the order edit page
 */
add_action('woocommerce_admin_order_data_after_billing_address', 'wooweb_cpe_peru_checkout_field_display_admin_order_meta', 10, 1);

function wooweb_cpe_peru_checkout_field_display_admin_order_meta($order)
{

  $cpeTypeId = CpeUtils::getCPETypeID($order->get_id());

  if ($cpeTypeId != '') {
    $cpeName = CpeUtils::tipoDocumento($cpeTypeId);

    echo '<div class="address">';
    echo '<h3>Datos Sunat</h3>';
    echo '<p><strong>' . __('Tipo de Documento', 'wooweb_cpe_peru') . ':</strong> ' . $cpeName . '</p>';

    echo '<p><strong>' . __('N. ' . $cpeName, 'wooweb_cpe_peru') . ':</strong> ' . get_post_meta($order->get_id(), 'wooweb_cpe_peru_registro', true) . '</p>';
    echo '<p><strong>' . __('Nombre', 'wooweb_cpe_peru') . ':</strong> ' . get_post_meta($order->get_id(), 'wooweb_cpe_peru_razonsocial', true) . '</p>';
    echo '<p><strong>' . __('Domicilio Fiscal', 'wooweb_cpe_peru') . ':</strong> ' . get_post_meta($order->get_id(), 'wooweb_cpe_peru_domiciliofiscal', true) . '</p>';

    if ($cpeTypeId == 6) {
      echo '<p><strong>' . __('Ubigeo', 'wooweb_cpe_peru') . ':</strong> ' . get_post_meta($order->get_id(), 'wooweb_cpe_peru_ubigeo', true) . '</p>';
    }

    echo '</div>';
  }

  echo '<div class="edit_address">';

  $options = get_option(WOOWEB_CPE_PERU_SETTINGS);
  if (CpeUtils::keyValue($options, 'wooweb_cpe_peru_enabled_boletas') == 1) {
    woocommerce_wp_select(array(
      'id' => 'wooweb_cpe_tipo_documento',
      'label' => __('Tipo de Documento', 'wooweb_cpe_peru'),
      'wrapper_class' => 'form-field form-field-wide',
      'class' => '_billing_address_1_field  sunat_field',
      'options' => array(
        'blank'    => __('Seleccione su documento', 'wooweb_cpe_peru'),
        '1'  => __('DNI', 'wooweb_cpe_peru'),
        'A'   => __('Documento no domiciliado', 'wooweb_cpe_peru'),
        '4'   => __('Carnet de Extranjería', 'wooweb_cpe_peru'),
        '7'   => __('Pasaporte', 'wooweb_cpe_peru')
      ),
      'value' => get_post_meta($order->get_id(), 'wooweb_cpe_peru_tipo_documento', true)
    ));
  } else {
    woocommerce_wp_select(array(
      'id' => 'wooweb_cpe_tipo_documento',
      'label' => __('Tipo de Documento', 'wooweb_cpe_peru'),
      'wrapper_class' => 'form-field form-field-wide',
      'class' => '_billing_address_1_field  sunat_field',
      'options' => array(
        'blank'    => __('Seleccione su documento', 'wooweb_cpe_peru'),
        '1'  => __('DNI', 'wooweb_cpe_peru'),
        '6'  => __('RUC', 'wooweb_cpe_peru'),
        'A'   => __('Documento no domiciliado', 'wooweb_cpe_peru'),
        '4'   => __('Carnet de Extranjería', 'wooweb_cpe_peru'),
        '7'   => __('Pasaporte', 'wooweb_cpe_peru')
      ),
      'value' => get_post_meta($order->get_id(), 'wooweb_cpe_peru_tipo_documento', true)
    ));
  }


  woocommerce_wp_text_input(array(
    'id' => 'wooweb_cpe_registro',
    'label' => __('N. ', 'wooweb_cpe_peru'),
    'wrapper_class' => 'form-field form-field-wide',
    'class' => '_billing_address_2_field  sunat_field',
    'value' => get_post_meta($order->get_id(), 'wooweb_cpe_peru_registro', true)
  ));

  woocommerce_wp_text_input(array(
    'id' => 'wooweb_cpe_razonsocial',
    'label' => __('Nombre ', 'wooweb_cpe_peru'),
    'wrapper_class' => 'form-field form-field-wide',
    'class' => '_billing_address_1_field  sunat_field',
    'value' => get_post_meta($order->get_id(), 'wooweb_cpe_peru_razonsocial', true)
  ));

  woocommerce_wp_text_input(array(
    'id' => 'wooweb_cpe_domiciliofiscal',
    'label' => __('Domicilio Fiscal', 'wooweb_cpe_peru'),
    'wrapper_class' => 'form-field form-field-wide',
    'class' => '_billing_address_2_field  sunat_field',
    'value' => get_post_meta($order->get_id(), 'wooweb_cpe_peru_domiciliofiscal', true)
  ));

  woocommerce_wp_text_input(array(
    'id' => 'wooweb_cpe_ubigeo',
    'label' => __('Ubigeo', 'wooweb_cpe_peru'),
    'wrapper_class' => 'form-field form-field-wide',
    'class' => '_billing_address_2_field  sunat_field',
    'value' => get_post_meta($order->get_id(), 'wooweb_cpe_peru_ubigeo', true)
  ));

  echo '</div>';
}
//-------------------------------------------------------------------------------------------------------
add_action('woocommerce_process_shop_order_meta', 'wooweb_cpe_peru_save_shipping_details');
function wooweb_cpe_peru_save_shipping_details($order_id)
{

  if (!empty($_POST['wooweb_cpe_tipo_documento'])) {
    update_post_meta($order_id, 'wooweb_cpe_peru_tipo_documento', wc_clean($_POST['wooweb_cpe_tipo_documento']));
  }

  if (!empty($_POST['wooweb_cpe_registro'])) {
    update_post_meta($order_id, 'wooweb_cpe_peru_registro', wc_clean($_POST['wooweb_cpe_registro']));
  }

  if (!empty($_POST['wooweb_cpe_razonsocial'])) {
    update_post_meta($order_id, 'wooweb_cpe_peru_razonsocial', wc_clean($_POST['wooweb_cpe_razonsocial']));
  }

  if (!empty($_POST['wooweb_cpe_domiciliofiscal'])) {
    update_post_meta($order_id, 'wooweb_cpe_peru_domiciliofiscal', wc_clean($_POST['wooweb_cpe_domiciliofiscal']));
  }

  if (!empty($_POST['wooweb_cpe_ubigeo'])) {
    update_post_meta($order_id, 'wooweb_cpe_peru_ubigeo', wc_clean($_POST['wooweb_cpe_ubigeo']));
  }

}
//-------------------------------------------------------------------------------------------------------
add_action('add_meta_boxes', 'wooweb_add_meta_boxes_woocommerce');
if (!function_exists('wooweb_add_meta_boxes_woocommerce')) {
  function wooweb_add_meta_boxes_woocommerce()
  {
    add_meta_box('wooweb_box_cpe_peru', __('Comprobante '.CpeUtils::getProvider('name'), 'wooweb_cpe_peru'), 'wooweb_box_cpe_peru_markup', 'shop_order', 'side', 'core');
  }
}
//-------------------------------------------------------------------------------------------------------
if (!function_exists('wooweb_box_cpe_peru_markup')) {
  function wooweb_box_cpe_peru_markup($order)
  {
    $order_id = $order->ID;
    $nroCPE = CpeUtils::getNroCPE($order_id);

    if ($order->post_status != 'wc-processing' && $order->post_status != 'wc-completed') {
      if (!$nroCPE) {
        echo '<center><strong style="color:red">'.__('NO PUEDES GENERAR UN COMPROBANTE <br/> EL ESTADO DEL PEDIDO DEBE SER PROCESANDO O COMPLETADO', 'wooweb_cpe_peru').'</strong></center>';
      } else {
        echo '<center><strong style="color:red">'.__('TIENES UN COMPROBANTE DE UNA ORDEN - ','wooweb_cpe_peru') . $order->post_status . __(', VE A TU PANEL DE FACTURACIÓN Y ANULA EL COMPROBANTE.','wooweb_cpe_peru').'</strong></center>';
      }
    }

    if (!$nroCPE) {
      echo __('<center>No existe comprobante actualmente</center>', 'wooweb_cpe_peru');
    } else {
      $CPExternalID = CpeUtils::getCPExternalId($order_id);
      $URL = CpeUtils::getApiUrl();
      echo "<b style='font-size: 18px;'>".__("N° CPE: ", 'wooweb_cpe_peru') . $nroCPE . '</b><br/><br/>';
      CpeUtils::getCPEUrls($order_id, false, true);
    }
  }
}
//-------------------------------------------------------------------------------------------------------
//Verified order payment complete
if (CpeUtils::keyValue($options, 'wooweb_cpe_peru_enabled_status_payment') == 1) {
  add_action('woocommerce_payment_complete', 'wooweb_cpe_peru_payment_complete');
  function wooweb_cpe_peru_payment_complete($order_id)
  {

    $nroCPE = CpeUtils::getNroCPE($order_id);

    if (!$nroCPE || $nroCPE == '') {
      wooweb_cpe_peru_crearComprobanteSUNAT($order_id);
    }
  }
}
//-------------------------------------------------------------------------------------------------------
//Verified order completed
if (CpeUtils::keyValue($options, 'wooweb_cpe_peru_enabled_status_completed') == 1) {
  add_action('woocommerce_order_status_completed', 'wooweb_cpe_peru_order_status_completed', 10, 1);
  function wooweb_cpe_peru_order_status_completed($order_id)
  {

    $nroCPE = CpeUtils::getNroCPE($order_id);

    if (!$nroCPE || $nroCPE == '') {
      wooweb_cpe_peru_crearComprobanteSUNAT($order_id);
    }
  }
}
//-------------------------------------------------------------------------------------------------------
//verified order is processing
if (CpeUtils::keyValue($options, 'wooweb_cpe_peru_enabled_status_processing') == 1) {
  add_action('woocommerce_order_status_processing', 'wooweb_cpe_peru_order_status_processing', 10, 1);
  function wooweb_cpe_peru_order_status_processing($order_id)
  {

    $nroCPE = CpeUtils::getNroCPE($order_id);

    if (!$nroCPE || $nroCPE == '') {
      wooweb_cpe_peru_crearComprobanteSUNAT($order_id);
    }
  }
}
//------------------------------------------------------------------------------------------------------
add_action('woocommerce_order_actions', 'wooweb_cpe_peru_add_generatecpe_actions');
function wooweb_cpe_peru_add_generatecpe_actions($actions)
{
  $actions['wooweb_cpe_peru_generatecpe'] = __('Generar Comprobante', 'wooweb_cpe_peru');
  return $actions;
}


add_action('woocommerce_order_action_wooweb_cpe_peru_generatecpe', 'wooweb_cpe_peru_process_generatecpe', 1);
function wooweb_cpe_peru_process_generatecpe($order)
{

  $order_id = $order->get_id();
  $nroCPE = CpeUtils::getNroCPE($order_id);

  if ($order->has_status('processing') || $order->has_status('completed')) {
    if (!$nroCPE || $nroCPE == '') {
      wooweb_cpe_peru_crearComprobanteSUNAT($order_id);
    }
  }
}

//------------------------------------------------------------------------------------------------------
add_filter('bulk_actions-edit-shop_order', 'wooweb_bulk_generatecpe', 20, 1);
function wooweb_bulk_generatecpe($actions)
{
  $actions['wooweb_bulk_cpe'] = __('Generar comprobantes', 'wooweb_cpe_peru');
  return $actions;
}

add_action('admin_action_wooweb_bulk_cpe', 'wooweb_bulk_generatecpe_process');
function wooweb_bulk_generatecpe_process()
{

  if (!isset($_REQUEST['post']) && !is_array($_REQUEST['post']))
    return;

  foreach ($_REQUEST['post'] as $order_id) {

    $order = new WC_Order($order_id);
    $nroCPE = CpeUtils::getNroCPE($order_id);

    if (!$nroCPE || $nroCPE == '') {
      if ($order->has_status('processing') || $order->has_status('completed')) {
        wooweb_cpe_peru_crearComprobanteSUNAT($order_id);
      }
    }
  }
}
