<?php

/**
 * Cart Helper
 *
 * @since 2.0.0
 * 
 */

namespace Bundler\Helpers;

use Bundler\Controllers\OfferController;
use Bundler\Includes\Traits\Instance;
use Bundler\Helpers\CartHelper;

if (!defined('ABSPATH')) exit;

class Cart
{

    use Instance;

    protected $offer_controller;

    public function __construct()
    {
        $this->offer_controller = OfferController::get_instance();

        add_action('woocommerce_before_calculate_totals', array($this, 'before_calculate_totals'), 20);

        add_filter('woocommerce_cart_item_price', array($this, 'cart_item_price_html'), 10, 3);
    }

    /**
     * @param mixed $cart_object
     * 
     * @return void
     */
    public function before_calculate_totals($cart_object)
    {

        if (is_admin() && !defined('DOING_AJAX'))
            return;

        if (!$cart_object) return;

        $cart_contents = $cart_object->cart_contents;
        if (!$cart_contents || empty($cart_contents) || !(is_array($cart_contents) || is_object($cart_contents))) return;

        $product_ids = CartHelper::get_bundler_products_in_cart();
        if (!$product_ids || empty($product_ids)) return;

        foreach ($product_ids as $product_id) { // For each product (not item) in the cart

            $discount_options = $this->offer_controller->get_the_discounts_desc($product_id);

            if (!is_array($discount_options)) continue;

            $discount_type = $discount_options[0]->discount_type;

            $discounted_total = 0;
            $qty_in_cart = 0;
            $cart_item_count = 0;

            foreach ($cart_contents as $key => $cart_item) {
                if ($cart_item['product_id'] == $product_id) {
                    if (isset($cart_item["_wbdl_data"]['offer_type']) && $cart_item["_wbdl_data"]['offer_type'] === 'volume_discount') {
                        $qty_in_cart += $cart_item['quantity']; // Get the product's quantity in cart
                        $cart_item_count++; // Get the number of lines for this product in the cart (could be 1 for simple product or > 1 for variable product if different variations in the cart)
                    }
                }
            }

            $remaining_cart_qty = $qty_in_cart;

            if (!$discount_type || $discount_type === 'fixed_price' || $discount_type === '') {

                foreach ($discount_options as $discount_option) {
                    if ($remaining_cart_qty >= $discount_option->number_of_products) {
                        /* Note: it is possible to cumulate discount_option discounts. The idea here is to start with the bigger discount_option which is supposed to have the bigger discount, apply that discount and move to the next discount_option.
                                Example: if we have 5 items in cart, discount_option 1 = 3 items and discount_option 2 = 2 items, we calculate the discounted price for the first 3 items using the first discount_option because it has the biggest discount.*/
                        $max_discounted_items = intval($remaining_cart_qty / $discount_option->number_of_products);

                        $price = $discount_option->sale_price ? CartHelper::get_wmc_price($discount_option->sale_price, $discount_option->wmc_sale_price) : $this->get_wmc_price($discount_option->regular_price, $discount_option->wmc_regular_price);
                        $discounted_total += $price * $max_discounted_items;

                        $remaining_cart_qty -= $discount_option->number_of_products * $max_discounted_items;
                    }
                    if ($remaining_cart_qty == 0) break;
                }

                if (!$discounted_total || ($discounted_total == 0)) return;

                $price = ($discounted_total / $qty_in_cart);
                $decimals = function_exists('wc_get_price_decimals') ? (int) wc_get_price_decimals() : 2;

                if ((strpos(strrev($price), '.') > $decimals) || (strpos(strrev($price), ',') > $decimals)) { // Woocommerce rounding issue will occur.

                    // Round the unit price. This will be the price for all the items except the last one.
                    $rounded_price = round($price, $decimals);

                    foreach ($cart_contents as $key => $cart_item) {
                        if ($cart_item['product_id'] == $product_id && isset($cart_item['_wbdl_data'])) {
                            if (isset($cart_item["_wbdl_data"]['offer_type']) && $cart_item["_wbdl_data"]['offer_type'] === 'volume_discount') {
                                $cart_item['_wbdl_data']['rounding'] = true;
                                if ($cart_item_count > 1) { // Not the last item in the cart
                                    $cart_item['_wbdl_data']['sale_price'] = $rounded_price;
                                    $cart_item['_wbdl_data']['multiline_price'] = false;
                                    $cart_item['data']->set_price($rounded_price);
                                } else {
                                    // Last item in the cart has a different price to make the total price match
                                    $cart_item['_wbdl_data']['sale_price'] = $rounded_price;
                                    $last_item_price = ($discounted_total - ($rounded_price * ($qty_in_cart - $cart_item['quantity']))) / $cart_item['quantity'];
                                    $cart_item['_wbdl_data']['last_item_price'] = $last_item_price;
                                    $cart_item['_wbdl_data']['item_subtotal'] = $last_item_price * $cart_item['quantity'];
                                    $cart_item['_wbdl_data']['multiline_price'] = ($cart_item['quantity'] > 1) ? 'multiple' : 'single'; // show multiple lines only if the total quantity is > 1
                                    $cart_item['data']->set_price($last_item_price);
                                }
                                $cart_item_count--;
                                WC()->cart->cart_contents[$key] = $cart_item;
                            }
                        }
                    }
                } else { // No rounding issue
                    foreach ($cart_contents as $key => $cart_item) {
                        if ($cart_item['product_id'] == $product_id && $cart_item['_wbdl_data']) {
                            if (isset($cart_item["_wbdl_data"]['offer_type']) && $cart_item["_wbdl_data"]['offer_type'] === 'volume_discount') {
                                $cart_item['_wbdl_data']['rounding'] = false;
                                $cart_item['_wbdl_data']['sale_price'] = $price;
                                $cart_item['_wbdl_data']['item_subtotal'] = $price * $cart_item['quantity'];
                                $cart_item['_wbdl_data']['multiline_price'] = false;
                                $cart_item['data']->set_price($price);
                            }
                        }
                        WC()->cart->cart_contents[$key] = $cart_item;
                    }
                }
            } else if ($discount_type === 'discount') { // Dynamic discount offers

                uasort($cart_contents, ['Bundler\Helpers\CartHelper', 'sort_by_price_desc']);

                $cart_new = array();
                foreach ($cart_contents as $key => $cart_item) {
                    if ($cart_item['product_id'] === $product_id && isset($cart_item["_wbdl_data"]['offer_type']) && $cart_item["_wbdl_data"]['offer_type'] === 'volume_discount') {

                        $cart_item_quantity = $cart_item['quantity'];
                        while ($cart_item_quantity > 0) {

                            $product = wc_get_product($cart_item['product_id']);
                            $price = $product->get_price();

                            $meta_cart_item = new \stdClass();
                            $meta_cart_item->key = $key;
                            $meta_cart_item->price = $price;
                            array_push($cart_new, $meta_cart_item);
                            $cart_item_quantity--;
                        }
                    }
                }

                $item = reset($cart_new);

                foreach ($discount_options as $discount_option) {
                    if ($remaining_cart_qty >= $discount_option->number_of_products) {
                        $max_discounted_items = intval($remaining_cart_qty / $discount_option->number_of_products);

                        $discounted_items = 0;

                        while ($discounted_items < $discount_option->number_of_products || $discount_option->number_of_products == 1) { // Apply the discount to the number of products allowed by the discount option

                            if ($discount_option->discount_method === 'percent_off') {
                                $item->price = $item->price * (1 - $discount_option->discount_value / 100);
                            } else if ($discount_option->discount_method === 'value_off') {
                                $item->price = $item->price - $discount_option->discount_value / $discount_option->number_of_products;
                            }

                            $discounted_items++;
                            $item = next($cart_new);
                            if (!$item) {
                                break;
                            }
                        }
                        $remaining_cart_qty -= $discount_option->number_of_products * $max_discounted_items;
                    }
                    if ($remaining_cart_qty == 0) break;
                }

                // // applying the new prices
                foreach (WC()->cart->cart_contents as $key => $cart_item) {

                    if (isset($cart_item["_wbdl_data"]) && $cart_item['product_id'] === $product_id) {

                        if (isset($cart_item["_wbdl_data"]['offer_type']) && $cart_item["_wbdl_data"]['offer_type'] === 'volume_discount') {

                            $item_subtotal = 0;
                            foreach ($cart_new as $cart_item_new) {
                                if ($cart_item_new->key === $cart_item['key']) {
                                    $item_subtotal += $cart_item_new->price;
                                }
                            }
                            $sale_price = $item_subtotal / $cart_item['quantity'];
                            $cart_item['_wbdl_data']['sale_price'] = $sale_price;
                            // $cart_item['_wbdl_data']['item_subtotal'] = $item_subtotal;
                            $cart_item['_wbdl_data']['rounding'] = false;
                            $cart_item['_wbdl_data']['multiline_price'] = false;
                            $cart_item['data']->set_price($sale_price);
                            WC()->cart->cart_contents[$key] = $cart_item;
                        }
                    }
                }
            }
        }
    }

    /**
     * Edit the price html in the cart to show the price strikeout
     */
    public function cart_item_price_html($price, $cart_item, $cart_item_key)
    {
        if (is_null($cart_item['data'])) {
            return $price;
        }
        if (is_a($cart_item['data'], 'WC_Product')) {

            if (isset($cart_item['_wbdl_data'])) {
                if (isset($cart_item['_wbdl_data']['offer_type']) && $cart_item['_wbdl_data']['offer_type'] == 'volume_discount') {
                    if (CartHelper::get_strikeout_price_html($cart_item) != "") {
                        $price = CartHelper::get_strikeout_price_html($cart_item);
                    }
                }
            }
        }
        return $price;
    }
}

return Cart::get_instance();
