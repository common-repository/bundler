<?php

/**
 * AJAX
 *
 * @since 1.0.0
 * 
 */

namespace Bundler\Controllers;

use Bundler\Helpers\ProductHelper;
use Bundler\Includes\Traits\Instance;

if (!defined('ABSPATH')) exit;

class Ajax
{

    use Instance;

    public function __construct()
    {
        $this->handle_public_ajax();
    }

    /**
     * Add public ajax endpoints
     *
     * @return void
     */
    public function handle_public_ajax()
    {
        $endpoints = self::get_available_public_endpoints();
        foreach ($endpoints as $action => $function) {
            add_action('wc_ajax_' . $action, array($this, $function));
        }
    }

    /**
     * Get public endpoints     
     *
     * @return array
     */
    public function get_available_public_endpoints()
    {
        return [
            'wbdl_add_to_cart' => 'add_to_cart',
        ];
    }

    /**
     * Get WC public endpoints
     *
     * @param $query
     *
     * @return array
     */
    public function get_public_endpoints($query = [])
    {
        $public_endpoints = $this->get_available_public_endpoints();
        if (empty($public_endpoints) || !is_array($public_endpoints)) {
            return [];
        }

        $endpoints = [];
        foreach ($public_endpoints as $key => $function) {
            $url = \WC_AJAX::get_endpoint($key);
            $url = is_array($query) && count($query) > 0 ? add_query_arg($query, $url) : $url;

            $endpoints[$key] = $url;
        }

        return $endpoints;
    }

    /**
     * Add to cart function for simple and variable products
     * 
     * @return void
     */
    public function add_to_cart()
    {

        $this->verify_nonce();

        $product_id        = apply_filters('woocommerce_add_to_cart_product_id', absint(sanitize_text_field($_POST['product_id'])));
        $products_number   = empty($_POST['products_num']) ? 1 : wc_stock_amount(sanitize_text_field($_POST['products_num']));
        $qty               = isset($_POST['product_quantity']) ? sanitize_text_field($_POST['product_quantity']) : 1;
        $product_status    = get_post_status($product_id);
        $passed_validation = true;

        $product = wc_get_product($product_id);

        $cart_item_meta = array();
        $cart_item_meta['_wbdl_data'] = ['use_custom_variations' => 'off'];
        $cart_item_meta['_wbdl_data']['offer_type'] = 'volume_discount';

        if ($product->is_type('simple') || $product->is_type('subscription')) {

            $qty = $qty * $products_number;

            if ($product) $stock_quantity = $product->get_stock_quantity();
            if (!ProductHelper::is_product_in_stock($product_id, $qty)) {
                $passed_validation = false;
            }

            if ($passed_validation && 'publish' === $product_status && WC()->cart->add_to_cart($product_id, $qty, 0, null, $cart_item_meta)) {
                do_action('woocommerce_ajax_added_to_cart', $product_id);
                if ('yes' === get_option('woocommerce_cart_redirect_after_add')) {
                    wc_add_to_cart_message(array($product_id => $qty), true);
                }
                \WC_AJAX::get_refreshed_fragments();
            } else {
                $response = $stock_quantity > 0 ? array(
                    'error'       => true,
                    'message'     => sprintf(
                        /* translators: %d: stock quantity */
                        __('This product has only %d items in stock.', 'bundler'),
                        $stock_quantity
                    ),
                    'product_url' => apply_filters('woocommerce_cart_redirect_after_error', get_permalink($product_id), $product_id)
                ) : array(
                    'error'       => true,
                    'message'     => __('This product is out of stock.', 'bundler'),
                    'product_url' => apply_filters('woocommerce_cart_redirect_after_error', get_permalink($product_id), $product_id)
                );
                wp_send_json($response);
            }
        }
        wp_die();
    }

    /**
     * Verify nonce
     *
     * @return void
     */
    public function verify_nonce($form_nonce = null)
    {
        $nonce = $form_nonce ? $form_nonce : filter_input(INPUT_POST, 'nonce', FILTER_UNSAFE_RAW);
        if (is_null($nonce) || !wp_verify_nonce($nonce, 'bundler')) {
            wp_send_json(array(
                'msg'  => __('Security check failed', 'bundler'),
                'code' => 401
            ));
        }
    }
}

return Ajax::get_instance();
