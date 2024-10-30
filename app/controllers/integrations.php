<?php

/**
 * Integrations
 *
 * Handle integrations between BUNDLER and 3rd-Party plugins
 *
 * @since 2.1.5
 */

namespace Bundler\Controllers;

if (!defined('ABSPATH')) exit;

class Integrations
{

    /**
     * Add built-in integrations with 3rd party plugins
     */
    public static function init()
    {

        $namespace = "Bundler\Integrations\\";
        $integrations = array(
            'WC_Multi_Currency' => BDLR_PLUGIN_DIR . 'integrations/woocommerce-multi-currency.php',
            'WCPBC' => BDLR_PLUGIN_DIR . 'integrations/woocommerce-product-price-based-on-countries.php',
        );

        foreach ($integrations as $class => $integration_file) {
            $class = $namespace . $class;
            if (!class_exists($class)) {
                require_once $integration_file;
                if (class_exists($class)) {
                    new $class;
                }
            }
        }
    }
}

Integrations::init();
