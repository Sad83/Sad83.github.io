<?php

//-------------------------------------------------------------------------------------------------------
add_action('admin_menu', 'wooweb_cpe_plugin_settings_page');
function wooweb_cpe_plugin_settings_page()
{

  $page_title = 'Opciones del Plugin ' . CpeUtils::getProvider('name');
  $menu_title = CpeUtils::getProvider('name');

  $capability = 'edit_posts';
  $slug = 'wooweb_cpe_peru_fields';
  $callback = 'wooweb_cpe_settings_page_content';

  //Add menu to settings option on wordpress
  add_submenu_page('options-general.php', $page_title, $menu_title, $capability, $slug, $callback);
}
//-------------------------------------------------------------------------------------------------------

function wooweb_cpe_settings_page_content()
{ ?>
  <div class="wrap">
    <?php _e(CpeUtils::getProvider('logo')); ?>
    <h2><?php _e('Datos de su sistema de Facturación - '. CpeUtils::getProvider('name'), 'wooweb_cpe_peru'); ?></h2>
    <p><?php
        _e('Configura los datos de tu sistema de facturación - ' . CpeUtils::getProvider('help') . '</p>', 'wooweb_cpe_peru') ?>
    <form action='options.php' method='post'>
      <?php
      settings_fields('wooweb_cpe_peru_fields');
      do_settings_sections('wooweb_cpe_peru_fields');
      submit_button();
      ?>
    </form>
  </div>
<?php

}
//-------------------------------------------------------------------------------------------------------

add_action('admin_init', 'setup_sections');
function setup_sections()
{

  register_setting('wooweb_cpe_peru_fields', WOOWEB_CPE_PERU_SETTINGS, 'wooweb_cpe_peru_validate_fields');

  add_settings_section('section_configuracion', 'CONFIGURACIÓN', 'section_configuracion_callback', 'wooweb_cpe_peru_fields');
  add_settings_section('section_connection', 'CONEXIÓN', 'section_connection_callback', 'wooweb_cpe_peru_fields');
  add_settings_section('section_series', 'SERIES', 'section_series_callback', 'wooweb_cpe_peru_fields');
  add_settings_section('section_currency', 'OTROS DATOS', 'section_currency_callback', 'wooweb_cpe_peru_fields');
  if (CpeUtils::keyValue($_GET, 'marca')) {
    if ($_GET['brand'] = 'blanca') {
      add_settings_section('section_provider', 'Proveedor', 'section_provider_callback', 'wooweb_cpe_peru_fields');
    }
  }
}
//-------------------------------------------------------------------------------------------------------

function section_configuracion_callback()
{
  echo __('Configura el comportamiento del plugin', 'wooweb_cpe_peru');
}

function section_series_callback()
{
  echo 'Ingresa la series con las cuales trabajarás, previamente ingresalo en tu sistema de facturación electrónica';
}

function section_currency_callback()
{
  echo 'Ingresa otras opciones como, la moneda oficial de tu tienda virtual USD (Dólares americanos), PEN (Soles Peruanos), etc y la unidad de medida global de tus productos ZZ (Servicios) ó NIU (Productos), si desea alguna otra unidad revisar su sistema de facturación y colocar el que encuentre en su catálogo.';
}

function section_connection_callback()
{
  echo 'Ingresa los datos de conexión de tu sistema de facturación electrónica';
}

function section_provider_callback()
{
  echo 'Datos del proveedor';
}

//-------------------------------------------------------------------------------------------------------

add_action('admin_init', 'wooweb_cpe_peru_setup_fields');
function wooweb_cpe_peru_setup_fields()
{
  //configuración fields
  add_settings_field('wooweb_cpe_peru_enabled_checkout', 'Habilitar', 'wooweb_cpe_peru_enabled_checkout_render', 'wooweb_cpe_peru_fields', 'section_configuracion');
  add_settings_field('wooweb_cpe_peru_enabled_status_processing', 'Estado del pedido', 'wooweb_cpe_peru_enabled_status_processing_render', 'wooweb_cpe_peru_fields', 'section_configuracion');
  //series fields
  add_settings_field('wooweb_cpe_peru_invoice_serie', 'Facturas', 'wooweb_cpe_peru_invoice_serie_render', 'wooweb_cpe_peru_fields', 'section_series');
  add_settings_field('wooweb_cpe_peru_boleta_serie', 'Boletas', 'wooweb_cpe_peru_boleta_serie_render', 'wooweb_cpe_peru_fields', 'section_series');
  add_settings_field('wooweb_cpe_peru_igv_actual', 'IGV Actual', 'wooweb_cpe_peru_igv_actual_render', 'wooweb_cpe_peru_fields', 'section_series');
  //moneda - seccion 2
  add_settings_field('wooweb_cpe_peru_currency', 'Moneda', 'wooweb_cpe_peru_currency_render', 'wooweb_cpe_peru_fields', 'section_currency');
  add_settings_field('wooweb_cpe_peru_cambio', 'Tipo de Cambio', 'wooweb_cpe_peru_cambio_render', 'wooweb_cpe_peru_fields', 'section_currency');
  add_settings_field('wooweb_cpe_peru_und', 'Unidad de Medida', 'wooweb_cpe_peru_und_render', 'wooweb_cpe_peru_fields', 'section_currency');
  add_settings_field('wooweb_cpe_peru_date', 'Fecha de Pedido', 'wooweb_cpe_peru_date_render', 'wooweb_cpe_peru_fields', 'section_currency');
  add_settings_field('wooweb_cpe_peru_sku', 'Código Interno', 'wooweb_cpe_peru_sku_render', 'wooweb_cpe_peru_fields', 'section_currency');

  //Conexión
  add_settings_field('wooweb_cpe_peru_url', 'URL', 'wooweb_cpe_peru_url_render', 'wooweb_cpe_peru_fields', 'section_connection');
  add_settings_field('wooweb_cpe_peru_tokem', 'TOKEM', 'wooweb_cpe_peru_tokem_render', 'wooweb_cpe_peru_fields', 'section_connection');

  //Proveedor

  add_settings_field('wooweb_cpe_peru_pname', 'Nombre', 'wooweb_cpe_peru_pname_render', 'wooweb_cpe_peru_fields', 'section_provider');
  add_settings_field('wooweb_cpe_peru_plogo', 'Logo', 'wooweb_cpe_peru_plogo_render', 'wooweb_cpe_peru_fields', 'section_provider');
  add_settings_field('wooweb_cpe_peru_payuda', 'Soporte', 'wooweb_cpe_peru_payuda_render', 'wooweb_cpe_peru_fields', 'section_provider');
}
//-------------------------------------------------------------------------------------------------------
//validated when the options is saving
function wooweb_cpe_peru_validate_fields($input)
{

  $camposValidar = array(
    'wooweb_cpe_peru_url' => 'SUBDOMINIO',
    'wooweb_cpe_peru_tokem' => 'TOKEM',
    'wooweb_cpe_peru_invoice_serie' => 'Serie Facturas',
    'wooweb_cpe_peru_boleta_serie' => 'Serie Boletas',
    'wooweb_cpe_peru_igv_actual' => 'IGV',
    'wooweb_cpe_peru_currency' => 'Moneda',
    'wooweb_cpe_peru_und' => 'Unidad'
  );

  $camposFaltantes = '';
  foreach ($camposValidar as $key => $value) {
    if ($input[$key] == '') {
      $camposFaltantes .= $value . ', ';
    }
  }

  if ($camposFaltantes != '') {
    add_settings_error(
      'notified',
      esc_attr('settings_updated'),
      "Datos actualizados, pero faltan los siguientes datos obligatorios: <b>" . $camposFaltantes . '</b>',
      'error'
    );
  } else {
    add_settings_error(
      'notified',
      esc_attr('settings_updated'),
      "Cambios actualizados",
      'success'
    );
  }

  return $input;
}

//-------------------------------------------------------------------------------------------------------

function wooweb_cpe_peru_enabled_checkout_render()
{
  $options = get_option(WOOWEB_CPE_PERU_SETTINGS);
  echo '<input type="checkbox" name="wooweb_cpe_peru_settings[wooweb_cpe_peru_enabled_checkout]" value="1"' . checked(1, CpeUtils::keyValue($options, 'wooweb_cpe_peru_enabled_checkout'), false) . '/> Formulario en el Checkout<br/>';
  echo '<input type="checkbox" name="wooweb_cpe_peru_settings[wooweb_cpe_peru_enabled_shipping]" value="1"' . checked(1, CpeUtils::keyValue($options, 'wooweb_cpe_peru_enabled_shipping'), false) . '/> Costo de envío como una linea de pedido<br/>';
  echo '<input type="checkbox" name="wooweb_cpe_peru_settings[wooweb_cpe_peru_enabled_boletas]" value="1"' . checked(1, CpeUtils::keyValue($options, 'wooweb_cpe_peru_enabled_boletas'), false) . '/> Emitir solo boletas. Especial para el régimen NRUS';
}

function wooweb_cpe_peru_enabled_status_processing_render()
{
  $options = get_option(WOOWEB_CPE_PERU_SETTINGS);
  echo "<b>Generar comprobante según el estado del pedido:</b><br/>";
  echo '<input type="checkbox" name="wooweb_cpe_peru_settings[wooweb_cpe_peru_enabled_status_processing]" value="1"' . checked(1, CpeUtils::keyValue($options, 'wooweb_cpe_peru_enabled_status_processing'), false) . '/> Procesando<br/>';
  echo '<input type="checkbox" name="wooweb_cpe_peru_settings[wooweb_cpe_peru_enabled_status_completed]" value="1"' . checked(1, CpeUtils::keyValue($options, 'wooweb_cpe_peru_enabled_status_completed'), false) . '/> Completado<br/>';
  echo '<input type="checkbox" name="wooweb_cpe_peru_settings[wooweb_cpe_peru_enabled_status_payment]" value="1"' . checked(1, CpeUtils::keyValue($options, 'wooweb_cpe_peru_enabled_status_payment'), false) . '/> Cuando el pago es confirmado';
}

function wooweb_cpe_peru_invoice_serie_render()
{
  $options = get_option(WOOWEB_CPE_PERU_SETTINGS);
  echo '<input name="wooweb_cpe_peru_settings[wooweb_cpe_peru_invoice_serie]" placeholder="Ejemplo: F001" type="text" value="' . CpeUtils::keyValue($options, 'wooweb_cpe_peru_invoice_serie') . '" />';
}

function wooweb_cpe_peru_boleta_serie_render()
{
  $options = get_option(WOOWEB_CPE_PERU_SETTINGS);
  echo '<input name="wooweb_cpe_peru_settings[wooweb_cpe_peru_boleta_serie]" placeholder="Ejemplo: B001" type="text" value="' . CpeUtils::keyValue($options, 'wooweb_cpe_peru_boleta_serie') . '" />';
}

function wooweb_cpe_peru_igv_actual_render()
{
  $options = get_option(WOOWEB_CPE_PERU_SETTINGS);
  echo '<input name="wooweb_cpe_peru_settings[wooweb_cpe_peru_igv_actual]" placeholder="18" type="text" value="' . CpeUtils::keyValue($options, 'wooweb_cpe_peru_igv_actual') . '" />';
}

function wooweb_cpe_peru_currency_render()
{
  $options = get_option(WOOWEB_CPE_PERU_SETTINGS);
  echo '<input name="wooweb_cpe_peru_settings[wooweb_cpe_peru_currency]" type="text" placeholder="Ejemplo: PEN" value="' . CpeUtils::keyValue($options, 'wooweb_cpe_peru_currency') . '" />';
}

function wooweb_cpe_peru_cambio_render()
{
  $options = get_option(WOOWEB_CPE_PERU_SETTINGS);
  echo '<input name="wooweb_cpe_peru_settings[wooweb_cpe_peru_tipo_cambio]" type="text" placeholder="3.35" value="' . CpeUtils::keyValue($options, 'wooweb_cpe_peru_tipo_cambio') . '" />';
}

function wooweb_cpe_peru_und_render()
{
  $options = get_option(WOOWEB_CPE_PERU_SETTINGS);
  echo '<input name="wooweb_cpe_peru_settings[wooweb_cpe_peru_und]" type="text" placeholder="Ejemplo: NIU o ZZ" value="' . CpeUtils::keyValue($options, 'wooweb_cpe_peru_und') . '" />';
}

function wooweb_cpe_peru_date_render()
{
  $options = get_option(WOOWEB_CPE_PERU_SETTINGS);
  echo '<input type="checkbox" name="wooweb_cpe_peru_settings[wooweb_cpe_peru_date]" value="1"' . checked(1, CpeUtils::keyValue($options, 'wooweb_cpe_peru_date'), false) . '/> Usar fecha de pedido<br/>';
  echo "El sistema usa la fecha actual para la emisión del comprobante, si deseas usar la fecha de pedido de WooCommerce marca el check. Recuerda emitir tus comprobantes dentro del periodo establecido por SUNAT.";
}

function wooweb_cpe_peru_sku_render()
{
  $options = get_option(WOOWEB_CPE_PERU_SETTINGS);
  echo '<input type="checkbox" name="wooweb_cpe_peru_settings[wooweb_cpe_peru_sku]" value="1"' . checked(1, CpeUtils::keyValue($options, 'wooweb_cpe_peru_sku'), false) . '/> Usar SKU como código interno<br/>';
  echo "El sistema usará el ID del producto como codigo interno para identificarlo en el sistema de " . CpeUtils::getProvider('name') . ", marca el check si deseas usar el SKU como código interno en tu sistema de " . CpeUtils::getProvider('name') . ". El SKU será tomado del producto principal o sus variantes.";
}

function wooweb_cpe_peru_url_render()
{
  $options = get_option(WOOWEB_CPE_PERU_SETTINGS);
  echo '<input name="wooweb_cpe_peru_settings[wooweb_cpe_peru_url]" type="text" value="' . CpeUtils::keyValue($options, 'wooweb_cpe_peru_url') . '" style="min-width:200px" /><br/>';
  echo 'Ingresa la url de tu cuenta de ' . CpeUtils::getProvider('name');
}

function wooweb_cpe_peru_tokem_render()
{
  $options = get_option(WOOWEB_CPE_PERU_SETTINGS);
  echo '<input name="wooweb_cpe_peru_settings[wooweb_cpe_peru_tokem]" type="text" value="' . CpeUtils::keyValue($options, 'wooweb_cpe_peru_tokem') . '" style="min-width:250px" />';
}

function wooweb_cpe_peru_nf_sunatcliente_render()
{
  $options = get_option(WOOWEB_CPE_PERU_SETTINGS);
}

function wooweb_cpe_peru_pname_render()
{
  $options = get_option(WOOWEB_CPE_PERU_SETTINGS);
  echo '<input name="wooweb_cpe_peru_settings[wooweb_cpe_peru_pname]" type="text" value="' .
    CpeUtils::keyValue($options, 'wooweb_cpe_peru_pname') . '" style="min-width:250px" />';
}

function wooweb_cpe_peru_plogo_render()
{
  $options = get_option(WOOWEB_CPE_PERU_SETTINGS);
  echo '<input name="wooweb_cpe_peru_settings[wooweb_cpe_peru_plogo]" type="text" value="' .
    CpeUtils::keyValue($options, 'wooweb_cpe_peru_plogo') . '" style="min-width:250px" /><br/>' .
    'Ingresa una url';
}

function wooweb_cpe_peru_payuda_render()
{
  $options = get_option(WOOWEB_CPE_PERU_SETTINGS);
  echo '<input name="wooweb_cpe_peru_settings[wooweb_cpe_peru_payuda]" type="text" value="' .
    CpeUtils::keyValue($options, 'wooweb_cpe_peru_payuda') . '" style="min-width:250px" /><br/>' .
    'Ingresa una url o un mailto:tucorre@proveedor.com';
}

add_filter('plugin_action_links_wooweb-cpe-peru/wooweb-cpe-peru.php', 'wooweb_settings_cpe_link');
function wooweb_settings_cpe_link($links)
{
  // Build and escape the URL.
  $url = esc_url(add_query_arg(
    'page',
    'wooweb_cpe_peru_fields',
    get_admin_url() . 'options-general.php'
  ));
  // Create the link.
  $settings_link = "<a href='$url'>" . __('Ajustes') . '</a>';
  // Adds the link to the end of the array.
  array_push(
    $links,
    $settings_link
  );
  return $links;
}