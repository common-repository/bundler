<?php

namespace Bundler\Helpers;

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

class ProductHelper
{

    /**
     * Check if a product is in stock
     *
     * @param mixed $product_id
     * @param mixed $qty
     * @return bool
     */
    public static function is_product_in_stock($product_id, $qty)
    {
        $product          = wc_get_product($product_id);
        $quantity_in_cart = 0;

        if (WC()->cart) {
            $cart = WC()->cart->cart_contents;
        }
        if ($cart) {
            foreach ($cart as $cart_item) {
                if ($cart_item['product_id'] == $product_id) {
                    $quantity_in_cart++;
                }
            }
        }

        $total_quantity = $qty + $quantity_in_cart;

        return $product && $product->is_in_stock() && $product->has_enough_stock($total_quantity);
    }

    /**
     * Format a product object
     * 
     * @param mixed $product
     * 
     * @return array
     */
    public static function format_product($product)
    {
        $initial_price = 0;
        if ($product->is_type('simple') || $product->is_type('subscription')) {
            $initial_price = $product->get_price();
        }

        $attributes = [];
        foreach ($product->get_attributes() as $attribute) {
            $options = $attribute->is_taxonomy() ? $attribute->get_terms() : $attribute->get_options();
            $formatted_options = array_map(function ($val) use ($attribute) {
                return $attribute->is_taxonomy()
                    ? ['label' => esc_html($val->name), 'value' => $val->slug]
                    : ['label' => esc_html($val), 'value' => esc_attr($val)];
            }, $options);

            $attributes[] = [
                'name' => $attribute->get_name(),
                'slug' => wc_sanitize_taxonomy_name($attribute->get_name()),
                'wooName' => wc_attribute_label($attribute->get_name()),
                'options' => $formatted_options,
                'hasVariation' => $attribute->get_variation(),
                'isTaxonomy' => $attribute->is_taxonomy(),
            ];
        }

        return [
            'id' => $product->get_id(),
            'name' => $product->get_title(),
            'type' => $product->get_type(),
            'initialPrice' => $initial_price,
            'attributes' => $attributes,
        ];
    }

    /**
     * Search products
     * 
     * @param mixed $query
     * 
     * @return [type]
     */
    public static function search_products($query, $limit)
    {
        if ($query) {
            remove_all_filters('woocommerce_data_stores');

            $product_query = array(
                'limit'  => $limit,
                'status' => 'publish',
                'type'   => 'simple',
                's'      => $query,
            );
            $products = wc_get_products($product_query);

            $formatted_products = [];

            foreach ($products as $product) {
                $formatted_products[] = ProductHelper::format_product($product);
            }

            return $formatted_products;
        }

        return [];
    }

    public static function get_product_for_preview()
    {
        remove_all_filters('woocommerce_data_stores');

        $product_query = array(
            'limit'  => 1,
            'status' => 'publish',
            'type'   => 'simple',
        );
        $products = wc_get_products($product_query);

        if ($products && !empty($products)) {
            $product = ProductHelper::format_product($products[0]);
            return $product;
        }

        return null;
    }
}
