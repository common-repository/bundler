<?php

/**
 * Woocommerce Multi Currency by VillaTheme.
 *
 * @see https://villatheme.com/extensions/woo-multi-currency/
 * 
 * * @since 2.2.0
 * 
 */

namespace Bundler\Integrations;

use Bundler\Controllers\OfferController;

if (!defined('ABSPATH')) exit;

/**
 * WC_Multi_Currency class.
 */


class WC_Multi_Currency
{
    protected $wmc_settings;
    protected $offer_controller;

    public function __construct()
    {
        $this->offer_controller = new OfferController();

        if (is_plugin_active('woocommerce-multi-currency/woocommerce-multi-currency.php') || is_plugin_active("woo-multi-currency/woo-multi-currency.php")) {

            add_filter('wbdl_get_currency_data', array($this, 'wmc_get_currency_data'), 10);
            add_filter('wbdl_get_wmc_price', array($this, 'wmc_get_price'), 10, 3);

            // product price hooks
            add_filter('woocommerce_product_get_price', array($this, 'wmc_revert_product_price'), 100, 2);
        }
    }

    /**
     * Get the price in the current currency
     * 
     * @param mixed $price
     * 
     * @return mixed
     */
    public function wmc_get_price($price)
    {
        if (!$price) return;

        if (class_exists('WOOMULTI_CURRENCY_Data')) {
            $this->wmc_settings = \WOOMULTI_CURRENCY_Data::get_ins();
        } elseif (class_exists('WOOMULTI_CURRENCY_F_Data')) {
            $this->wmc_settings = \WOOMULTI_CURRENCY_F_Data::get_ins();
        }

        if ($this->wmc_settings) {

            $default_currency = $this->wmc_settings->get_default_currency();
            $current_currency = $this->wmc_settings->get_current_currency();

            if ($current_currency != $default_currency) {
                $price = wmc_get_price($price);
            }
        }
        return $price;
    }

    /**
     * Revert the villatheme convert price filter for products
     * 
     * @param mixed $price
     * @param mixed $product
     * 
     * @return mixed
     */
    public function wmc_revert_product_price($price, $product)
    {

        if (!is_shop() && !is_product_category()) {
            if (function_exists('wmc_revert_price')) {
                $product_id = $product->get_id();
                $bundles = $this->offer_controller->get_the_discounts_desc($product_id);
                if ($bundles && is_array($bundles)) {
                    return wmc_revert_price($price);
                }
            }
        }

        return $price;
    }

    /**
     * Get the current currency data
     * 
     * @return array
     */
    public function wmc_get_currency_data()
    {
        $currency_data = [];

        if (class_exists('WOOMULTI_CURRENCY_Data')) {
            $this->wmc_settings = \WOOMULTI_CURRENCY_Data::get_ins();
        } elseif (class_exists('WOOMULTI_CURRENCY_F_Data')) {
            $this->wmc_settings = \WOOMULTI_CURRENCY_F_Data::get_ins();
        }

        if ($this->wmc_settings) {
            $currencies    = $this->wmc_settings->get_list_currencies();
            $current_currency = $this->wmc_settings->get_current_currency();
            $default_currency = $this->wmc_settings->get_default_currency();

            foreach ($currencies as $currency => $value) {

                $currency_symbol = isset($currencies[$currency]['custom']) && '' != $currencies[$currency]['custom']
                    ? $currencies[$currency]['custom']
                    : get_woocommerce_currency_symbol($currency);


                $currencies[$currency]['symbol'] = $currency_symbol;
            }


            $currency_data = array(
                'currencies'       => $currencies,
                'current_currency' => $current_currency,
                'default_currency' => $default_currency,
            );
        }

        return $currency_data;
    }
}
