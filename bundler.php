<?php
/* 
 * Plugin Name:  Bundler Lite
 * Requires Plugins: woocommerce
 * Plugin URI:   https://wcbundler.com/
 * Description:  Create bundle offers that convert. Bundler Core package.
 * Version:      3.0.6
 * Author:       WooBundles
 * Author URI:   https://wcbundler.com
 * License:      GPLv3
 * License URI:  http://www.gnu.org/licenses/gpl-3.0.html
 * Text Domain:  bundler
 * Domain Path:  /languages
 * 
 * Bundler is a free software.
 * You can redistribute it and/or modify it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * any later version.
 *
 * WooBundles is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with WooBundles. If not, see <http://www.gnu.org/licenses/>.
*/


if (!defined('ABSPATH'))  exit; // Exit if accessed directly

if (!function_exists('is_plugin_active')) {
    require_once ABSPATH . 'wp-admin/includes/plugin.php';
}

if (!defined('BDLR_VERSION')) {
    define('BDLR_VERSION', '3.0.6');
}

if (!defined('BDLR_PLUGIN_FILE')) {
    define('BDLR_PLUGIN_FILE', __FILE__);
}

if (!defined('BDLR_PLUGIN_BASENAME')) {
    define('BDLR_PLUGIN_BASENAME', plugin_basename(__FILE__));
}

require_once plugin_dir_path(__FILE__) . '/includes/define.php';

/**
 *  Don't allow multiple versions to be active.
 * */
if (function_exists('bdlr')) {

    if (!function_exists('bdlr_pro_just_activated')) {
        /**
         * When we activate a Pro version, we need to do additional operations:
         * 1) deactivate a Lite version;
         * 2) register option which help to run all activation process for Pro version (custom tables creation, etc.).
         *
         * @since 1.0.0
         */
        function bdlr_pro_just_activated()
        {
            set_transient('bdlr_pro_just_activated', true);
            bdlr_lite_deactivate();
            add_option('bdlr_install', 1);
        }
    }
    add_action('activate_bundler-pro/bundler.php', 'bdlr_pro_just_activated');

    if (!function_exists('bdlr_lite_deactivate')) {
        /**
         * Deactivate Lite if Bundler already activated.
         *
         * @since 1.0.0
         */
        function bdlr_lite_deactivate()
        {

            $plugin = 'bundler/bundler.php';

            deactivate_plugins($plugin);

            //do_action( 'bdlr_plugin_deactivated', $plugin );
        }
    }
    add_action('admin_init', 'bdlr_lite_deactivate');

    if (!function_exists('bdlr_lite_just_activated')) {
        /**
         * Store temporarily that the Lite version of the plugin was activated.
         * This is needed because WP does a redirect after activation and
         * we need to preserve this state to know whether user activated Lite or not.
         *
         * @since 1.0.0
         */
        function bdlr_lite_just_activated()
        {

            if (get_transient('bdlr_pro_just_activated')) {
                bdlr_lite_deactivate();
                set_transient('bdlr_lite_just_deactivated', true);
            } else {
                set_transient('bdlr_lite_just_activated', true);
            }
        }
    }
    add_action('activate_bundler/bundler.php', 'bdlr_lite_just_activated');

    // Do not process the plugin code further.
    return;
}


/**
 * Store temporarily that Bundler has been activated for the first time.
 * 
 * @since 1.2.8
 * */
if (!function_exists('bdlr_activation')) {
    function bdlr_activation()
    {
        set_transient('bdlr_first_time_activated', true);
    }
    register_activation_hook(__FILE__, 'bdlr_activation');
}


// Redirect to the offers page after plugin's activation.
// if (!function_exists('bdlr_plugin_activated')) {
//     function bdlr_plugin_activated($plugin)
//     {
//         if ($plugin == plugin_basename(__FILE__)) {
//             exit(wp_redirect(admin_url('admin.php?page=bundler')));
//         }
//     }
//     add_action('activated_plugin', 'bdlr_plugin_activated');
// }

/**
 * Display admin notices
 * */

if (!function_exists('bdlr_lite_notice')) {
    /**
     * Display the notice after deactivation when Pro is still active
     * and user wanted to activate the Lite version of the plugin.
     *
     * @since 1.0.0
     */
    function bdlr_lite_notice()
    {

        if (get_transient('bdlr_lite_just_deactivated')) {
            // Currently tried to activate Lite with Pro still active, so display the message.
            printf(
                '<div class="notice notice-warning is-dismissible">
                    <p>%1$s</p>
                    <p>%2$s</p>
                </div>',
                esc_html_e('Heads up!', 'bundler'),
                esc_html_e('Your site already has Bundler Pro activated. If you want to switch to Bundler Lite, please first go to Plugins → Installed Plugins and deactivate Bundler Pro. Then, you can activate Bundler Lite.', 'bundler')
            );

            if (isset($_GET['activate'])) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
                unset($_GET['activate']); // phpcs:ignore WordPress.Security.NonceVerification.Recommended
            }

            delete_transient('bdlr_lite_just_deactivated');
        }

        if (get_transient('bdlr_first_time_activated')) { ?>

            <div class="notice notice-success is-dismissible">
                <p><?php esc_html_e('Bundler is active now! Click on the button below to start creating offers and discounts.', 'bundler'); ?></p>
                <p>
                    <a href="<?php echo esc_url(admin_url('admin.php?page=bundler')); ?>" class="button button-primary"><?php esc_html_e('Get started', 'bundler'); ?></a>
                    <a href="https://wcbundler.com/docs" target="_blank" class="button"><?php esc_html_e('View Documentation', 'bundler'); ?></a>
                </p>
            </div>
        <?php
            delete_transient('bdlr_first_time_activated');
        }
    }
    add_action('admin_notices', 'bdlr_lite_notice');
}



/**
 *  We require PHP version 5.6+ for the whole plugin to work.
 */

if (version_compare(phpversion(), BDLR_REQUIRED_PHP_VERSION, '<')) {

    if (!function_exists('bdlr_php56_notice')) {

        /**
         * Display the notice about incompatible PHP version after deactivation.
         *
         * @since 1.0.0
         */
        function bdlr_php56_notice()
        {

        ?>
            <div class="notice notice-error">
                <p>
                    <?php
                    esc_html_e('Bundler is disabled on your site until you fix the issue: Your site is running an <strong>insecure version</strong> of PHP that is no longer supported. Please contact your web hosting provider to update your PHP version.', 'bundler');
                    ?>
                </p>
            </div>

            <?php
            // In case this is on plugin activation.
            // phpcs:disable WordPress.Security.NonceVerification.Recommended
            if (isset($_GET['activate'])) {
                unset($_GET['activate']);
            }
            // phpcs:enable WordPress.Security.NonceVerification.Recommended
        }
    }

    add_action('admin_notices', 'bdlr_php56_notice');

    // Do not process the plugin code further.
    return;
}


/**
 * We require WC version 3.3+ for the whole plugin to work.
 */

add_action('plugins_loaded', function () {
    if (class_exists('woocommerce')) {

        if (version_compare(WC_VERSION, BDLR_WC_REQUIRED_VERSION, '<')) {

            if (!function_exists('bdlr_woocommerce_version_admin_notice')) {

                /**
                 * Message if WooCommerce version is < 3.3.
                 */

                function bdlr_woocommerce_version_admin_notice()
                {
            ?>
                    <div class="error">
                        <p><?php esc_html_e('Bundler requires WooCommerce version above 3.3 to work.', 'bundler'); ?></p>
                    </div>
                <?php
                    // In case this is on plugin activation.
                    // phpcs:disable WordPress.Security.NonceVerification.Recommended
                    if (isset($_GET['activate'])) {
                        unset($_GET['activate']);
                    }
                }
            }
            add_action('admin_notices', 'bdlr_woocommerce_version_admin_notice');
            // Do not process the plugin code further.
            return;
        }
    } else {

        if (!function_exists('bdlr_install_woocommerce_admin_notice')) {

            /**
             * Message if WooCommerce plugin is not installed.
             */

            function bdlr_install_woocommerce_admin_notice()
            {
                ?>
                <div class="error">
                    <p><?php esc_html_e('Bundler cannot run without WooCommerce active. Please install and activate WooCommerce plugin.', 'bundler'); ?></p>
                </div>
<?php
                // In case this is on plugin activation.
                // phpcs:disable WordPress.Security.NonceVerification.Recommended
                if (isset($_GET['activate'])) {
                    unset($_GET['activate']);
                }
            }
        }
        add_action('admin_notices', 'bdlr_install_woocommerce_admin_notice');
        // Do not process the plugin code further.
        return;
    }
}, 1);

require_once dirname(__FILE__) . '/app/Bundler.php';

bdlr();
