<?php


if (!class_exists('Plugin_WooWeb_CPE_Peru')) {
  /**
   * Class Plugin
   *
   * Main Plugin class
   * @since 1.2.0
   */
  class Plugin_WooWeb_CPE_Peru
  {
    /**
     * @var Plugin_WooWeb_CPE_Peru unique instance
     */
    private static $_instance = null;
    protected static $PLUGIN_ID;
    protected static $PLUGIN_INSTANCIA;
    protected static $PLUGIN_ST_PROCESS;

    /**
     * Instance
     *
     * Ensures only one instance of the class is loaded or can be loaded.
     *
     * @since 1.2.0
     * @access public
     *
     * @return Plugin_WooWeb_CPE_Peru  An instance of the class.
     */
    public static function instance()
    {
      if (is_null(self::$_instance)) {
        self::$_instance = new self();
      }
      return self::$_instance;
    }

    public function __construct()
    {

      self::iniciar_constantes();

      require_once('includes/lib/wooweb-utils.php');
      require_once('includes/lib/wooweb-data.php');
      require_once('includes/lib/wooweb-api.php');
      require_once('includes/wooweb-process.php');
      require_once('includes/wooweb-cpe-peru-settings.php');
      require_once('includes/wooweb-cpe-peru-woocommerce-admin-front.php');
      require_once('includes/wooweb-cpe-peru-woocommerce-frontend.php');
    }

    public static function iniciar_constantes()
    {

      if (!defined('WOOWEB_CPE_PERU_PLUGIN_URL')) {
        define('WOOWEB_CPE_PERU_PLUGIN_URL', plugins_url('', __FILE__));
      }

      if (!defined('WOOWEB_CPE_PERU_SETTINGS')) {
        define('WOOWEB_CPE_PERU_SETTINGS', 'wooweb_cpe_peru_settings');
      }

      $options = get_option(WOOWEB_CPE_PERU_SETTINGS);
      if (!is_array($options)) {
        $options['wooweb_cpe_peru_sunat_connection'] = '';
      }

      if (!function_exists('get_plugin_data')) {
        require_once(ABSPATH . 'wp-admin/includes/plugin.php');
      }

      if (!defined('WOOWEB_CPE_PERU_SUNAT_CONNECTION')) {
        $sunat_data = get_plugin_data(plugin_dir_path(__FILE__) . 'wooweb-cpe-peru.php');
        define('WOOWEB_CPE_PERU_SUNAT_CONNECTION', $sunat_data['PluginURI']);
      }

      if (!defined('WOOWEB_CPE_PERU_VERSION')) {
        $sunat_data = get_plugin_data(plugin_dir_path(__FILE__) . 'wooweb-cpe-peru.php');
        define('WOOWEB_CPE_PERU_VERSION', $sunat_data['Version']);
      }

    }

  }
  // Instantiate Plugin_WooWeb_CPE_Peru Class
  Plugin_WooWeb_CPE_Peru::instance();
}
