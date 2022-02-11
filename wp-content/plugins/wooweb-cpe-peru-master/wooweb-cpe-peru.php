<?php
/*
Plugin Name: WooWeb CPE Perú
Plugin URI: https://wooweb.site
Description: Conexión con tu sistema de facturación electrónica para la emisión de comprobantes electrónicos
Author: Xuxan Vigo
Author URI: https://xuxanvigo.com
Version: 1.0
Text Domain: wooweb_cpe_peru
Copyright: © WooWeb

*/

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

final class WooWeb_CPE_Peru {
  /**
	 * Plugin Version
	 *
	 * @since 1.0
	 * @var string The plugin version.
	 */
	const VERSION = '1.0';

  /**
	 * Minimum PHP Version
	 *
	 * @since 1.2.0
	 * @var string Minimum PHP version required to run the plugin.
	 */
	const MINIMUM_PHP_VERSION = '7.0';

  public function __construct() {
    // Load translation
    add_action( 'init', array( $this, 'i18n' ) );
    // Init Plugin
    add_action( 'plugins_loaded', array( $this, 'init' ) );
  }

  /**
   * Load Textdomain
   *
   * Load plugin localization files.
   * Fired by `init` action hook.
   *
   * @since 1.2.0
   * @access public
   */
  public function i18n() {
    load_plugin_textdomain( 'wooweb-cpe-peru' );
  }

  public function init() {
    //validar que este instalado WooCommerce
    if(!in_array('woocommerce/woocommerce.php', apply_filters('active_plugins', get_option('active_plugins')))) {
		//if ( ! function_exists( 'WC' ) ) {
      add_action( 'admin_notices', array( $this, 'admin_notice_missing_woocommerce_plugin' ) );
      return;
    }
    // Check for required PHP version
    if ( version_compare( PHP_VERSION, self::MINIMUM_PHP_VERSION, '<' ) ) {
      add_action( 'admin_notices', array( $this, 'admin_notice_minimum_php_version' ) );
      return;
    }
    //validar que este instalado Table rates

    //Incluir el plugin
    require_once( 'modules.php' );
  }

  public function admin_notice_missing_woocommerce_plugin() {
    $message = sprintf(
      /* translators: 1: Plugin name 2: WooCommerce */
      esc_html__( '"%1$s" require que "%2$s" este instalado y activado.', 'wooweb_cpe_peru' ),
      '<strong>' . esc_html__( 'CPE PERU', 'wooweb_cpe_peru' ) . '</strong>',
      '<strong>' . esc_html__( 'WooCommerce', 'wooweb_cpe_peru' ) . '</strong>'
    );
    printf( '<div class="notice notice-warning is-dismissible"><p>%1$s</p></div>', $message );
  }

  /**
   * Admin notice
   *
   * Warning when the site doesn't have a minimum required PHP version.
   *
   * @since 1.0.0
   * @access public
   */
  public function admin_notice_minimum_php_version() {
    if ( isset( $_GET['activate'] ) ) {
      unset( $_GET['activate'] );
    }
    $message = sprintf(
      /* translators: 1: Plugin name 2: PHP 3: Required PHP version */
      esc_html__( '"%1$s" requiere "%2$s" version %3$s o mayor.', 'wooweb_cpe_peru' ),
      '<strong>' . esc_html__( 'CPE PERU', 'wooweb_cpe_peru' ) . '</strong>',
      '<strong>' . esc_html__( 'PHP', 'wooweb_cpe_peru' ) . '</strong>',
      self::MINIMUM_PHP_VERSION
    );
    printf( '<div class="notice notice-warning is-dismissible"><p>%1$s</p></div>', $message );
  }
}

new WooWeb_CPE_Peru();
