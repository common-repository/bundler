<?php

declare(strict_types=1);

namespace Bundler\Admin;

use Bundler\Includes\Traits\Instance;

if (!defined('ABSPATH')) exit;

class Admin_App
{

    use Instance;

    public $admin_path;
    public $admin_url;

    public function __construct()
    {
        $this->admin_path = BDLR_PLUGIN_DIR . '/admin';
        $this->admin_url  = BDLR_PLUGIN_URL . '/admin';

        /** Add Bundler admin menu */
        add_action('admin_menu', [$this, 'register_admin_menu'], 902);

        /** Plugin action links */
        add_filter("plugin_action_links_" . BDLR_PLUGIN_BASENAME, [$this, 'add_plugin_action_links']);

        /** Admin enqueue scripts */
        add_action('admin_enqueue_scripts', array($this, 'admin_enqueue_assets'));
    }

    /**
     * Register the menu
     * 
     * @return void
     */
    public function register_admin_menu()
    {
        $capability = 'manage_options';
        $wpIcon = 'PHN2ZyBlbmFibGUtYmFja2dyb3VuZD0ibmV3IDAgMCAxMjggMTI4IiBoZWlnaHQ9IjEyOHB4IiBpZD0iTGF5ZXJfMSIgdmVyc2lvbj0iMS4xIiB2aWV3Qm94PSIwIDAgMTI4IDEyOCIgd2lkdGg9IjEyOHB4IiB4bWw6c3BhY2U9InByZXNlcnZlIiB4bWxucz0iaHR0cDovL3d3dy53My5vcmcvMjAwMC9zdmciIHhtbG5zOnhsaW5rPSJodHRwOi8vd3d3LnczLm9yZy8xOTk5L3hsaW5rIj48Zz48cGF0aCBmaWxsPSJibGFjayIgZD0iTTEyOCw2NGMwLTMuNDgtMi4zMDEtNi42Ni02LjMxNC04LjcyNWwtMTEuMzY5LTUuODQ3bDExLjM2OS01Ljg0OGM0LjAxNC0yLjA2NCw2LjMxNC01LjI0NCw2LjMxNC04LjcyNSAgIGMwLTMuNDgtMi4zMDEtNi42Ni02LjMxNC04LjcyNEw3NS44NzcsMi41NzVDNzIuNjQ4LDAuOTE1LDY4LjQzMiwwLDY0LDBjLTQuNDMxLDAtOC42NDgsMC45MTQtMTEuODc1LDIuNTc0TDYuMzE1LDI2LjEzMyAgIGMtNC4wMTMsMi4wNjQtNi4zMTQsNS4yNDQtNi4zMTQsOC43MjVjMCwzLjQ4LDIuMzAxLDYuNjYsNi4zMTQsOC43MjRsMTEuMzcsNS44NDdsLTExLjM3LDUuODQ3QzIuMzAyLDU3LjM0LDAuMDAxLDYwLjUyLDAuMDAxLDY0ICAgYzAsMy40OCwyLjMwMiw2LjY2LDYuMzE0LDguNzI0bDExLjM3LDUuODQ3bC0xMS4zNyw1Ljg0OGMtNC4wMTMsMi4wNjMtNi4zMTQsNS4yNDMtNi4zMTQsOC43MjRzMi4zMDIsNi42Niw2LjMxNCw4LjcyNSAgIGw0NS44MDksMjMuNTU4YzMuMjI4LDEuNjYsNy40NDUsMi41NzQsMTEuODc1LDIuNTc0YzQuNDMyLDAsOC42NDgtMC45MTQsMTEuODc1LTIuNTczbDQ1LjgxMS0yMy41NiAgIGM0LjAxNC0yLjA2Myw2LjMxNC01LjI0Myw2LjMxNC04LjcyNHMtMi4zMDEtNi42Ni02LjMxNC04LjcyNWwtMTEuMzcxLTUuODQ3bDExLjM3MS01Ljg0OEMxMjUuNjk5LDcwLjY2LDEyOCw2Ny40OCwxMjgsNjR6ICAgIE0xMTguMDI3LDY1LjYwOWwtNDUuODExLDIzLjU2Yy0yLjA4NiwxLjA3Mi01LjA4MiwxLjY4OC04LjIxNywxLjY4OGMtMy4xMzUsMC02LjEzLTAuNjE1LTguMjE2LTEuNjg4TDkuOTc1LDY1LjYxICAgYy0xLjUxNC0wLjc3OS0xLjk0My0xLjQ5LTEuOTc2LTEuNTgzYzAuMDMyLTAuMTQ3LDAuNDYyLTAuODU4LDEuOTc2LTEuNjM3bDE2LjQ1OC04LjQ2M2wyMy45NjEsMTIuMzIybDEuNzMyLDAuODkxICAgYzAuOTAzLDAuNDY1LDEuODkxLDAuODY0LDIuOTMzLDEuMjA4YzIuNjc5LDAuODg2LDUuNzUyLDEuMzY2LDguOTQyLDEuMzY2YzMuMTg5LDAsNi4yNjQtMC40OCw4Ljk0My0xLjM2NiAgIGMxLjA0MS0wLjM0NCwyLjAyOS0wLjc0MywyLjkzMi0xLjIwOGwxLjczMi0wLjg5MWwyMy45NjEtMTIuMzIzbDE2LjQ1Nyw4LjQ2M2MxLjUxNiwwLjc3OSwxLjk0NSwxLjQ5LDEuOTc3LDEuNTgzICAgQzExOS45NzEsNjQuMTIxLDExOS41NDEsNjQuODMxLDExOC4wMjcsNjUuNjA5eiBNMTIwLjAwMiw5My4xMTVjLTAuMDMxLDAuMTQ3LTAuNDYxLDAuODU4LTEuOTc1LDEuNjM3bC00NS44MTEsMjMuNTYgICBjLTIuMDg2LDEuMDcyLTUuMDgyLDEuNjg4LTguMjE3LDEuNjg4Yy0zLjEzNSwwLTYuMTMtMC42MTUtOC4yMTYtMS42ODhMOS45NzUsOTQuNzUzYy0xLjUxNC0wLjc3OS0xLjk0NC0xLjQ5LTEuOTc2LTEuNTgzICAgYzAuMDMyLTAuMTQ3LDAuNDYxLTAuODU4LDEuOTc1LTEuNjM3bDE2LjQ1OC04LjQ2NGwyNS42OTMsMTMuMjEzYzMuMjI4LDEuNjYsNy40NDUsMi41NzQsMTEuODc1LDIuNTc0ICAgYzQuNDMyLDAsOC42NDgtMC45MTQsMTEuODc1LTIuNTczbDI1LjY5NS0xMy4yMTRsMTYuNDU3LDguNDYzQzExOS41NDEsOTIuMzEyLDExOS45NzEsOTMuMDIyLDEyMC4wMDIsOTMuMTE1eiBNNy45OTksMzQuODg0ICAgYzAuMDMyLTAuMTQ3LDAuNDYyLTAuODU4LDEuOTc1LTEuNjM3bDQ1LjgxLTIzLjU1OEM1Ny44Nyw4LjYxNiw2MC44NjUsOCw2NCw4YzMuMTM2LDAsNi4xMywwLjYxNSw4LjIxOCwxLjY4OGw0NS44MDcsMjMuNTU4ICAgYzEuNTE2LDAuNzc5LDEuOTQ1LDEuNDksMS45NzcsMS41ODNjLTAuMDMxLDAuMTQ3LTAuNDYxLDAuODU4LTEuOTc1LDEuNjM3TDcyLjk0Myw1OS42NTJsLTAuNzI3LDAuMzc0ICAgYy0yLjA4NiwxLjA3My01LjA4LDEuNjg4LTguMjE3LDEuNjg4Yy0zLjEzNSwwLTYuMTMtMC42MTUtOC4yMTYtMS42ODhsLTAuNzI2LTAuMzc0TDkuOTc1LDM2LjQ2NyAgIEM4LjQ2MSwzNS42ODgsOC4wMzEsMzQuOTc4LDcuOTk5LDM0Ljg4NHoiLz48L2c+PC9zdmc+';

        add_menu_page(false, 'Bundler', $capability, 'bundler', [$this, 'bdlr_page'], 'data:image/svg+xml;base64,' . $wpIcon, 56);
        add_submenu_page('bundler', 'Quantity Breaks', 'Quantity Breaks', $capability, 'bundler', [$this, 'bdlr_page'], 100);
        // add_submenu_page('bundler', 'Bundles', 'Bundles', $capability, 'bundler&path=bundles', [$this, 'bdlr_page'], 15);
        add_submenu_page('bundler', 'Settings', 'Settings', $capability, 'bundler&path=settings', [$this, 'bdlr_page'], 15);
        add_submenu_page('bundler', 'Go Pro', 'Go Pro', $capability, 'bundler/upgrade', array($this, 'upgrade'));

        return;
    }

    public static function bdlr_page()
    {
?>

        <div id="bdlr-page" class="bdlr-page"></div>

        <script type="text/javascript">
            function highlightSubmenu() {
                var path = new URLSearchParams(window.location.search).get('path');
                var menuItems = document.querySelectorAll('#toplevel_page_bundler ul.wp-submenu li');
                menuItems.forEach(function(menuItem) {
                    menuItem.classList.remove('current');
                });

                if (!path || path === 'edit-offer' || path === 'add-offer') {
                    document.querySelector('#toplevel_page_bundler ul.wp-submenu .wp-first-item').classList.add('current');
                } else {
                    document.querySelector('#toplevel_page_bundler a[href$="admin.php?page=bundler&path=' + path + '"]').parentNode.classList.add('current');
                }

            }

            document.addEventListener('DOMContentLoaded', highlightSubmenu);
            window.addEventListener('popstate', highlightSubmenu);
        </script>

        <script>
            document.addEventListener('DOMContentLoaded', function() {
                var goProLink = document.querySelector('#toplevel_page_bundler ul.wp-submenu a[href="admin.php?page=bundler/upgrade"]');
                if (goProLink) {
                    var parentLi = goProLink.parentElement;
                    parentLi.innerHTML = '<a target="_blank" href="https://wcbundler.com/pricing"><span class="button button-primary bundler-upgrade-button">Go Pro <span class="dashicons dashicons-external"></span></span></a>';
                }
            });
        </script>

        <?php
    }

    /**
     * Link to bundler pro pricing page
     */
    public static function upgrade()
    {
        header('Location: https://wcbundler.com/pricing');
        exit;
    }

    /**
     * Register plugin action links
     *
     * @param $links
     *
     * @return array|string[]
     */
    public function add_plugin_action_links($links)
    {
        $plugin_links = [];

        $upgrade_link = add_query_arg([
            'utm_source'   => 'WordPress',
            'utm_medium'   => 'Plugin+Action+Links',
            'utm_campaign' => 'WP+Bundler+Repo',
            'utm_content'  => 'Upgrade'
        ], "https://wcbundler.com/pricing/");

        $docs_link = add_query_arg([
            'utm_source'   => 'WordPress',
            'utm_medium'   => 'Plugin+Action+Links',
            'utm_campaign' => 'WP+Bundler+Repo',
            'utm_content'  => 'Docs'
        ], "https://wcbundler.com/docs/");

        $support_link = add_query_arg([
            'utm_source'   => 'WordPress',
            'utm_medium'   => 'Plugin+Action+Links',
            'utm_campaign' => 'WP+Bundler+Repo',
            'utm_content'  => 'Support'
        ], "https://wcbundler.com/support/");

        $plugin_links['bdlr_settings_link'] = '<a href="' . admin_url('admin.php?page=bundler&path=settings') . '">' . __('Settings', 'woocommerce') . '</a>';
        $plugin_links['bdlr_docs_link']     = '<a href="' . $docs_link . '" target="_blank">' . __('Docs', 'bundler') . '</a>';
        $plugin_links['bdlr_support_link']  = '<a href="' . $support_link . '" target="_blank">' . __('Support', 'bundler') . '</a>';
        $plugin_links['bdlr_upgrade_link']  = '<a href="' . $upgrade_link . '" target="_blank" style="color:#93003c;font-weight:700;">' . __('Get Bundler Pro', 'bundler') . '</a>';

        return array_merge($plugin_links, $links);
    }

    /**
     * Load admin script
     *
     * @return void
     */
    public function admin_enqueue_assets()
    {
        $page = filter_input(INPUT_GET, 'page');
        if (empty($page) || 'bundler' !== strval($page)) {
            return;
        }

        $build_dir  = $this->admin_path . '/app/dist';
        $app_name   = 'main';
        $script_dir = BDLR_REACT_PROD_URL;

        if (!is_dir($build_dir) || !file_exists($build_dir . "/$app_name.js") || !file_exists($build_dir . "/$app_name.css")) {
        ?>
            <script>
                document.addEventListener("DOMContentLoaded", function() {
                    var appLoader = document.getElementById('bdlr-page');
                    if (appLoader) {
                        appLoader.innerHTML = "<div class='notice notice-error'>" +
                            "<p><strong>Warning! Build files are missing.</strong></p>" +
                            "</div>";
                    }
                });
            </script>
<?php
            return;
        }

        do_action('bdlr_before_app_script_loaded');

        /** Enqueue wp media */
        wp_enqueue_media();
        wp_enqueue_style('selectWoo', BDLR_PLUGIN_URL . 'admin/assets/css/selectWoo.css');
        wp_enqueue_script('selectWoo', BDLR_PLUGIN_URL . 'admin/assets/js/selectWoo.full.js', array('jquery'), '1.0.0', true);

        /** Common */
        if (class_exists('\WooCommerce')) {
            wp_dequeue_style('woocommerce_admin_styles');
            wp_dequeue_style('wc-components');
        }

        wp_enqueue_style('wp-components');

        $deps    = $this->get_deps($app_name);
        $version = (isset($deps['version']) ? $deps['version'] : time());

        wp_register_script("bdlr_$app_name", $script_dir . "/$app_name.js", $deps['dependencies'], $version, true);
        wp_enqueue_style("bdlr_{$app_name}_css", $script_dir . "/$app_name.css", array(), $version);

        $wc_store_object = array(
            'html'                         => true,
            'currency_symbol'              => get_woocommerce_currency_symbol(get_woocommerce_currency()),
            'currency_position'            => get_option('woocommerce_currency_pos', true),
            'decimal_separator'            => wc_get_price_decimal_separator(),
            'currency_format_trim_zeros'   => wc_get_price_thousand_separator(),
            'currency_format_num_decimals' => wc_get_price_decimals(),
            'price_format'                 => get_woocommerce_price_format(),
        );

        $is_bundler_pack_active = is_plugin_active('bundler-pack-pro/bundler.php') || is_plugin_active('bundler-pack/bundler.php');

        wp_localize_script('bdlr_main', 'bdlr_data', [
            'ajax_url'            => admin_url('admin-ajax.php'),
            'nonce'               => wp_create_nonce('bdlr_nonce'),
            'license_nonce'       => wp_create_nonce('Bundler Pro'),
            'pluginUrl'           => BDLR_PLUGIN_URL,
            'bundler_pack_active' => $is_bundler_pack_active,
            'wc_store_object'     => $wc_store_object,
        ]);
        wp_enqueue_script("bdlr_$app_name");

        do_action('bdlr_after_app_script_loaded');
    }

    /**
     * Load dependencies
     *
     * @param $app_name
     *
     * @return array
     */
    public function get_deps($app_name)
    {
        $assets_path = $this->admin_path . "/app/dist/$app_name.asset.php";
        $assets      = require_once $assets_path;
        $deps        = (isset($assets['dependencies']) ? array_merge($assets['dependencies'], array('jquery')) : array('jquery'));
        $version     = (isset($assets['version']) ? $assets['version'] : BDLR_VERSION);

        $script_deps = array_filter($deps, function ($dep) use (&$style_deps) {
            return false === strpos($dep, 'css');
        });

        return array(
            'dependencies' => $script_deps,
            'version'      => $version,
        );
    }

    /**
     * Clear cache
     *
     * @return void
     */
    public static function maybe_clear_cache()
    {

        /**
         * Clear wordpress cache
         */
        if (function_exists('wp_cache_flush')) {
            wp_cache_flush();
        }

        /**
         * Checking if wp fastest cache installed
         * Clear cache of wp fastest cache
         */
        if (class_exists('\WpFastestCache')) {
            global $wp_fastest_cache;
            if (method_exists($wp_fastest_cache, 'deleteCache')) {
                $wp_fastest_cache->deleteCache();
            }

            // clear all cache
            if (function_exists('wpfc_clear_all_cache')) {
                wpfc_clear_all_cache(true);
            }
        }

        /**
         * Checking if wp Autoptimize installed
         * Clear cache of Autoptimize
         */

        if (class_exists('\autoptimizeCache') && method_exists('\autoptimizeCache', 'clearall')) {
            \autoptimizeCache::clearall();
        }

        /**
         * Checking if W3Total Cache plugin activated.
         * Clear cache of W3Total Cache plugin
         */
        if (function_exists('w3tc_flush_all')) {
            w3tc_flush_all();
        }

        /**
         * Checking if wp rocket caching add on installed
         * Cleaning the url for current opened URL
         */
        if (function_exists('rocket_clean_home')) {
            $referer = wp_get_referer();


            if (0 !== strpos($referer, 'http')) {
                $rocket_pass_url = get_rocket_parse_url(untrailingslashit(home_url()));

                if (is_array($rocket_pass_url) && 0 < count($rocket_pass_url)) {
                    list($host, $path, $scheme, $query) = $rocket_pass_url;
                    $referer = $scheme . '://' . $host . $referer;
                }
            }

            if (home_url('/') === $referer) {
                rocket_clean_home();
            } else {
                rocket_clean_files($referer);
            }
        }

        /**
         * LiteSpeed cache plugin
         */
        if (class_exists('\LiteSpeed\Purge')) {
            \LiteSpeed\Purge::purge_all();
        }

        /**
         * Checking if Wp Super Cache plugin activated.
         * Clear cache of Wp Super Cache plugin
         */
        if (function_exists('wp_cache_clear_cache')) {
            wp_cache_clear_cache();
        }
    }
}

if (is_admin()) {
    return Admin_App::get_instance();
}
