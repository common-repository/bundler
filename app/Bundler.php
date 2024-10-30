<?php

namespace Bundler {

	// use stdClass;
	use Bundler\Controllers\SettingsController;
	use Bundler\Models\Settings;
	use Bundler\Models\DB;
	use Bundler\Views\Widget;

	/**
	 * Main WBundler class.
	 *
	 * @since 1.0.0
	 */
	final class Bundler
	{

		/**
		 * Bundler Instance
		 *
		 * @since 1.0.0
		 *
		 */
		private static $instance;

		/**
		 * Plugin version for enqueueing, etc.
		 * The value is got from BDLR_VERSION constant.
		 *
		 * @since 1.0.0
		 *
		 * @var string
		 */
		public $version = '';

		/**
		 * Plugin settings.
		 *
		 * @since 1.0.0
		 *
		 * @var Settings
		 */
		private $settings;

		/**
		 * Pro returns true, free (Lite) returns false.
		 *
		 * @since 1.0.0
		 *
		 * @var bool
		 */
		private $pro = false;


		/**
		 * Main WBundler Instance.
		 *
		 * Only one instance of Bundler exists in memory at any one time.
		 * Also prevent the need to define globals all over the place.
		 *
		 * @since 1.0.0
		 *
		 * @return Bundler
		 */
		public static function instance()
		{

			if (
				self::$instance === null ||
				!self::$instance instanceof self
			) {

				self::$instance = new self();
				self::$instance->constants();
				self::$instance->load_db();

				add_action('init', [self::$instance, 'includes'], 10);
				add_action('init', [self::$instance, 'objects'], 10);

				self::$instance->load_assets();

				add_action('plugins_loaded', [self::$instance, 'load_textdomain'], 10);
				add_action('plugins_loaded', [self::$instance, 'init_tracker']);
			}

			return self::$instance;
		}

		/**
		 * Include files.
		 *
		 * @since 1.0.0
		 */
		private function load_db()
		{
			require_once BDLR_PLUGIN_DIR . 'app/models/db.php';

			add_action('plugins_loaded', function () {
				DB::create_tables();
			});
		}

		/**
		 * Register hooks.
		 *
		 * @since 1.0.0
		 */
		public function load_assets()
		{

			add_action('wp_enqueue_scripts', [$this, 'enqueue_script']);
			add_action('wp_enqueue_scripts', [$this, 'enqueue_style']);
		}

		/**
		 * Enqueue Scripts.
		 *
		 * @since 1.0.0
		 */
		public function enqueue_script()
		{
			wp_register_script('bdlr-script', BDLR_PLUGIN_URL . 'assets/js/app.js', array('jquery', 'wc-add-to-cart'), BDLR_VERSION, true);
			wp_enqueue_script('bdlr-script');

			$wc_store_object = array(
				'html'         => true,
				'currency'     => get_woocommerce_currency(),
				'symbol'       => get_woocommerce_currency_symbol(get_woocommerce_currency()),
				'position'     => get_option('woocommerce_currency_pos', true),
				'decimal_sep'  => wc_get_price_decimal_separator(),
				'thousand_sep' => wc_get_price_thousand_separator(),
				'decimals'     => wc_get_price_decimals(),
				'price_format' => get_woocommerce_price_format(),
			);

			$currency_data = [];
			if (has_filter('wbdl_get_currency_data')) {
				$currency_data = apply_filters('wbdl_get_currency_data', $currency_data);
			}

			$script_vars = array(
				'woo_cart_url'      => wc_get_cart_url(),
				'woo_checkout_url'  => wc_get_checkout_url(),
				'cart_redirect'     => $this->settings ? $this->settings->cart_redirect : '',
				'checkout_redirect' => $this->settings ? $this->settings->checkout_redirect : '',
				'ajax_url'          => admin_url('admin-ajax.php'),
				'is_wmc_active'     => is_plugin_active('woocommerce-multi-currency/woocommerce-multi-currency.php') || is_plugin_active("woo-multi-currency/woo-multi-currency.php"),
				'currency_data'     => $currency_data,
				'wc_store_object'   => $wc_store_object,
				'i18n'              => array(
					'select_offer_message' => esc_html__('Please select an offer before adding this product to your cart.', 'bundler')
				),
			);

			wp_localize_script(
				'bdlr-script',
				'bdlrData',
				$script_vars,
			);
		}

		/**
		 * Enqueue Styles.
		 *
		 * @since 1.3.5
		 */
		public function enqueue_style()
		{
			wp_register_style('bdlr-style', BDLR_PLUGIN_URL . 'assets/css/style.css', false, BDLR_VERSION, 'all');
			wp_enqueue_style('bdlr-style');
		}

		/**
		 * Setup plugin constants.
		 * All the path/URL related constants are defined in main plugin file.
		 *
		 * @since 1.0.0
		 */
		private function constants()
		{

			$this->version = BDLR_VERSION;

			if (BDLR_PRO == true) {
				$this->pro = true;
				define('BDLR_PLUGIN_SLUG', 'bundler-pro');
			} else {
				$this->pro = false;
				define('BDLR_PLUGIN_SLUG', 'bundler');
			}
		}

		/**
		 * Load the plugin language files.
		 *
		 * @since 1.0.0
		 * 
		 */
		public function load_textdomain()
		{
			// If the user is logged in, unset the current text-domains before loading our text domain.
			// This feels hacky, but this way a user's set language in their profile will be used,
			// rather than the site-specific language.
			if (is_user_logged_in()) {
				unload_textdomain('bundler');
			}

			load_plugin_textdomain('bundler', false, BDLR_PLUGIN_BASENAME . '/languages');
		}

		/**
		 * Include files.
		 *
		 * @since 1.0.0
		 */
		public function includes()
		{

			// Include traits
			require_once BDLR_PLUGIN_DIR . 'includes/traits/instance.php';

			// Include models
			require_once BDLR_PLUGIN_DIR . 'app/models/offer.php';
			require_once BDLR_PLUGIN_DIR . 'app/models/discount.php';
			require_once BDLR_PLUGIN_DIR . 'app/models/settings.php';

			// Include helpers
			require_once BDLR_PLUGIN_DIR . "app/helpers/product-helper.php";
			require_once BDLR_PLUGIN_DIR . "app/helpers/cart-helper.php";

			// Include controllers
			require_once BDLR_PLUGIN_DIR . 'app/controllers/offer-controller.php';
			require_once BDLR_PLUGIN_DIR . 'app/controllers/settings-controller.php';
			require_once BDLR_PLUGIN_DIR . 'app/controllers/integrations.php';
			require_once BDLR_PLUGIN_DIR . "app/controllers/cart.php";
			require_once BDLR_PLUGIN_DIR . 'app/controllers/ajax.php';

			// Include widget
			require_once BDLR_PLUGIN_DIR . 'app/views/widget.php';

			// Load admin
			if (is_admin()) {
				require_once BDLR_PLUGIN_DIR . 'admin/admin-app.php';
				require_once BDLR_PLUGIN_DIR . 'admin/admin-ajax.php';
			}
		}

		/**
		 * Setup objects.
		 *
		 * @since 1.0.0
		 */
		public function objects()
		{
			// Global objects.
			$settings_controller = SettingsController::get_instance();

			$this->settings = $settings_controller->get_vd_settings() ? $settings_controller->get_vd_settings() : $settings_controller->get_default_settings();

			// Hook now that all of the Bundler stuff is loaded.
			do_action('bdlr_loaded');
		}

		/**
		 * Initialize the plugin tracker
		 *
		 * @return void
		 */
		public function init_tracker()
		{
			if (!class_exists('Appsero\Client')) {
				require_once BDLR_PLUGIN_DIR . '/includes/appsero/src/Client.php';
			}
			$client = new \Appsero\Client('86d8a14f-02c3-4b05-9c4f-5839e6130e96', 'Bundler Lite', BDLR_PLUGIN_FILE);
			$client->insights()->init();
		}

		/**
		 * Whether the current instance of the plugin is a paid version, or free.
		 *
		 * @since 1.0.0
		 *
		 * @return bool
		 */
		public function is_pro()
		{
			/**
			 * Filters whether the current plugin version is pro.
			 *
			 * @since 1.0.0
			 *
			 * @param bool $pro Whether the current plugin version is pro.
			 */
			return (bool) $this->pro;
		}
	}
}

namespace {

	/**
	 * The function which returns the one WBundler instance.
	 *
	 * @since 1.0.0
	 *
	 * @return Bundler\Bundler
	 */
	function bdlr()
	{
		return Bundler\Bundler::instance();
	}

	/**
	 * Adding an alias for backward-compatibility with plugins
	 * that still use class_exists( 'WBundler' )
	 * instead of function_exists( 'wbdl' ), which is preferred.
	 *
	 * @since 1.0.0
	 */
	class_alias('Bundler\Bundler', 'Bundler');
}
