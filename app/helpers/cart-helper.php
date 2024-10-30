<?php

namespace Bundler\Helpers;

if (!defined('ABSPATH')) exit;

class CartHelper
{
    /**
     * Get IDs of bundle products in cart (doesn't include products with custom variations)
     *
     * @return array
     */
    public static function get_bundler_products_in_cart()
    {
        $product_ids = array();

        if (WC()->cart->cart_contents_count != 0) {
            foreach (WC()->cart->cart_contents as $cart_item) {
                $product_id = $cart_item['product_id'];
                if (isset($cart_item['_wbdl_data']) && wc_get_product($product_id)->is_type('simple') && !in_array($product_id, $product_ids)) {
                    if (isset($cart_item["_wbdl_data"]['offer_type']) && $cart_item["_wbdl_data"]['offer_type'] === 'volume_discount') {
                        array_push($product_ids, $cart_item['product_id']);
                    }
                }
            }
        }
        return $product_ids;
    }

    /**
     * Get multicurrency price in case a multicurrency plugin is enabled
     * 
     * @param mixed $price
     * @param mixed $wmc_price
     * @param string $is_wmc_custom_price
     * 
     * @return float $price
     */
    public static function get_wmc_price($price)
    {
        if (has_filter("wbdl_get_wmc_price")) {
            $price = apply_filters("wbdl_get_wmc_price", $price);
        }

        return $price;
    }

    /**
     * Build the Strikout Price HTML (to show on cart page) based on the product discount
     * 
     * @param mixed $cart_item
     * 
     * @return string
     */
    public static function get_strikeout_price_html($cart_item)
    {
        $price_html = "";

        // Check if use custom variations is selected. This will have an impact on how to display prices (bundle price VS item price)
        $ucv = isset($cart_item['_wbdl_data']['use_custom_variations']) ? $cart_item['_wbdl_data']['use_custom_variations'] : 'off';

        if (isset($ucv) && $ucv == 'off') {

            // Get the regular & sale prices
            $regular_price = $cart_item['data']->get_regular_price();
            $sale_price = $cart_item['_wbdl_data']['sale_price'];

            // Check if the item shows multiline price
            $is_multiline = $cart_item['_wbdl_data']['multiline_price'];
            $is_rounding = $cart_item['_wbdl_data']['rounding'];
            $qty = $cart_item['quantity'] - 1;

            $price_html = '<div class="wbdl_cart_strikeout_line">';

            if ($regular_price && $sale_price && ($regular_price > $sale_price)) {
                $price_html .= '<del><span class="woocommerce-Price-amount amount" val="' . $regular_price . '"><bdi>' . wc_price($regular_price) . '</bdi></span></del>&nbsp;<ins><span class="woocommerce-Price-amount amount"><bdi>';
            } else {
                $price_html .= '<ins><span class="woocommerce-Price-amount amount"><bdi>';
            }

            // If multiline, the price has to be displayed in multiple lines to match the bundle price
            if ($is_rounding) {
                // Calculate the last item displayed price
                $last_item_subtotal = $cart_item['_wbdl_data']['item_subtotal'];
                $sale_price = number_format(round($sale_price, 2), 2);
                $last_item_adjusted_price = ($cart_item['quantity'] == 1) ? $last_item_subtotal : $last_item_subtotal - ($sale_price * ($cart_item['quantity'] - 1));

                if ($is_multiline) {
                    if ($is_multiline == 'multiple') {
                        $price_html .= wc_price($sale_price);
                        $price_html .= '</bdi></span></ins>&nbsp;x&nbsp;' . $qty . '</div>';
                        $price_html .= '<div class="wbdl_cart_strikeout_last_line"><del><span class="woocommerce-Price-amount amount"><bdi>';
                        $price_html .= wc_price($regular_price) . '</bdi></span></del>&nbsp;<ins><span class="woocommerce-Price-amount amount"><bdi>';
                        $price_html .= wc_price($last_item_adjusted_price);
                        $price_html .= '</bdi></span></ins>&nbsp;x&nbsp;1';
                    } else if ($is_multiline == 'single') {
                        $price_html .= wc_price($last_item_adjusted_price) . '</bdi></span></ins>';
                    }
                } else {
                    $price_html .= wc_price($sale_price) . '</bdi></span></ins>';
                }
            }
            // Else, the price has to be displayed in a single line because the price is the same for all items
            else {
                $price_html .= wc_price($sale_price) . '</bdi></span></ins>';
            }
            $price_html .= '</div>';
        }

        return $price_html;
    }

    public static function sort_by_price($cart_item_a, $cart_item_b)
    {
        $price_a = $cart_item_a['data']->get_price();
        $price_b = $cart_item_b['data']->get_price();

        if ($price_a == $price_b) {
            return 0;
        }

        return ($price_a < $price_b) ? -1 : 1;
    }

    public static function sort_by_price_desc($cart_item_a, $cart_item_b)
    {
        $price_a = $cart_item_a['data']->get_price();
        $price_b = $cart_item_b['data']->get_price();

        if ($price_a == $price_b) {
            return 0;
        }

        return ($price_a > $price_b) ? -1 : 1;
    }
}
