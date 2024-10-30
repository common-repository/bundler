<?php

/**
 * Woocommerce Product Price Based On Countries
 *
 * @see https://www.pricebasedcountry.com/
 * 
 * @since 2.2.0
 * 
 */

namespace Bundler\Integrations;

if (!defined('ABSPATH')) exit;

/**
 * WCPBC class
 */

class WCPBC
{
    protected $wcbpc_zones = [];
    protected $wcpbc_currencies = [];

    public function __construct()
    {
        if (is_plugin_active('woocommerce-product-price-based-on-countries/woocommerce-product-price-based-on-countries.php')) {
            add_filter('wbdl_get_currency_data', array($this, 'wcbpc_get_currency_data'), 10);
            add_filter('wbdl_get_wmc_price', array($this, 'wcbpc_get_price'), 10, 3);
        }
    }

    /**
     * Get the price in the current currency
     * 
     * @param mixed $price
     * @param string $wmc_price
     * @param string $is_wmc_custom_price
     * 
     * @return mixed
     */
    public function wcbpc_get_price($price)
    {
        if (!$price) return;

        if (class_exists('WCPBC_Pricing_Zones')) {

            $this->wcbpc_zones = \WCPBC_Pricing_Zones::get_zones();

            if (function_exists('wcpbc_the_zone') && wcpbc_the_zone()) {

                $current_zone = wcpbc_the_zone();
                $exchange_rate = $current_zone->get_exchange_rate();

                // Calculate the price
                $price = $price * $exchange_rate;
            }
        }

        return $price;
    }

    /**
     * Get the current currency data
     * 
     * @return array
     */
    public function wcbpc_get_currency_data()
    {
        $currencies = array();
        $current_currency = $default_currency = $exchange_rate = false;

        if (class_exists('WCPBC_Pricing_Zones')) {

            $wcbpc_zones = \WCPBC_Pricing_Zones::get_zones();
            if ($wcbpc_zones && is_array($wcbpc_zones)) {
                foreach ($wcbpc_zones as $zone) {
                    $currencies[$zone->get_currency()] = $zone->get_exchange_rate();
                }
            }

            if (function_exists('wcpbc_the_zone') && wcpbc_the_zone()) {
                $current_zone     = wcpbc_the_zone();
                $exchange_rate    = $current_zone->get_exchange_rate();
                $current_currency = $current_zone->get_currency();
            }

            if (function_exists("get_woocommerce_currency")) $default_currency = get_woocommerce_currency();
        }

        $currency_data = array(
            'currencies'       => $currencies,
            'current_currency' => $current_currency,
            'default_currency' => $default_currency,
            'exchange_rate'    => $exchange_rate
        );

        return $currency_data;
    }
}
