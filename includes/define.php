<?php

if (!defined('ABSPATH')) exit;

/* Lite vs PRO */
if (!defined('BDLR_PRO')) {
    define('BDLR_PRO', false);
}

/* Plugin text domain */
if (!defined('BDLR_TEXTDOMAIN')) {
    define('BDLR_TEXTDOMAIN', 'bundler');
}

/* Required PHP Version */
if (!defined('BDLR_REQUIRED_PHP_VERSION')) {
    define('BDLR_REQUIRED_PHP_VERSION', '5.6');
}

/* Required Woocommerce Version */
if (!defined('BDLR_WC_REQUIRED_VERSION')) {
    define('BDLR_WC_REQUIRED_VERSION', '3.3.0');
}

/* Plugin DIR */
if (!defined('BDLR_PLUGIN_DIR')) {
    define('BDLR_PLUGIN_DIR', plugin_dir_path(BDLR_PLUGIN_FILE));
}
if (!defined('BDLR_INCLUDES')) {
    define('BDLR_INCLUDES', BDLR_PLUGIN_DIR . "includes" . DIRECTORY_SEPARATOR);
}
if (!defined('BDLR_PLUGINS')) {
    define('BDLR_PLUGINS', BDLR_PLUGIN_DIR . "integrations" . DIRECTORY_SEPARATOR);
}

/* Plugin URL */
if (!defined('BDLR_PLUGIN_URL')) {
    $plugin_url = str_replace('/includes', '/', plugins_url('', __FILE__));
    define('BDLR_PLUGIN_URL', $plugin_url);
}

/* Plugin prefix */
if (!defined('BDLR_PLUGIN_PREFIX')) {
    define('BDLR_PLUGIN_PREFIX', 'wbdl_');
}

define('BDLR_REACT_PROD_URL', BDLR_PLUGIN_URL . 'admin/app/dist');
