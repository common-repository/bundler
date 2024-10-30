<?php

namespace Bundler\Models;

if (!defined('ABSPATH')) exit;

class Discount
{

    /**
     * Add a discount to an existing offer
     * 
     * @param mixed $offer_id
     * @param mixed $title
     * @param mixed $image_url
     * @param mixed $number_of_products
     * @param mixed $sale_price
     * @param mixed $regular_price
     * @param mixed $use_custom_variations
     * @param mixed $option1_name
     * @param mixed $option1_value
     * @param mixed $option2_name
     * @param mixed $option2_value
     * @param mixed $add_messages
     * @param mixed $message
     * @param mixed $discount_rule
     * @param mixed $preselected_offer
     * @param mixed $message_effect
     * @param mixed $wmc_sale_price
     * @param mixed $wmc_regular_price
     * 
     * @return array
     */
    public static function add_discount_to_offer(
        $offer_id,
        $title,
        $image_url,
        $number_of_products,
        $discount_type,
        $discount_value,
        $discount_method,
        $sale_price,
        $regular_price,
        $wmc_sale_price,
        $wmc_regular_price,
        $use_custom_variations,
        $option1_name,
        $option1_value,
        $option2_name,
        $option2_value,
        $add_messages,
        $message,
        $discount_rule,
        $preselected_offer,
        $message_effect,
        $show_price_per_unit
    ) {
        global $wpdb;

        $volume_discount_table = $wpdb->prefix . DB::$volume_discount_table_name;

        $discount_data = array(
            'offer_id'              => $offer_id,
            'title'                 => $title,
            'image_url'             => $image_url,
            'number_of_products'    => $number_of_products,
            'discount_type'         => $discount_type,
            'discount_value'        => $discount_value,
            'discount_method'       => $discount_method,
            'sale_price'            => $sale_price,
            'regular_price'         => $regular_price,
            'wmc_sale_price'        => $wmc_sale_price,
            'wmc_regular_price'     => $wmc_regular_price,
            'use_custom_variations' => $use_custom_variations,
            'option1_name'          => $option1_name,
            'option1_value'         => $option1_value,
            'option2_name'          => $option2_name,
            'option2_value'         => $option2_value,
            'add_messages'          => $add_messages,
            'message'               => $message,
            'discount_rule'         => $discount_rule,
            'preselected_offer'     => $preselected_offer,
            'message_effect'        => $message_effect,
            'show_price_per_unit'   => $show_price_per_unit
        );

        $wpdb->insert($volume_discount_table, $discount_data);
    }

    /**
     * Get discounts for a given product id
     * 
     * @param mixed $product_id
     * 
     * @return array
     */
    public static function get_discounts_by_product_id($product_id)
    {
        global $wpdb;
        $volume_discount_table = $wpdb->prefix . DB::$volume_discount_table_name;
        $offer_table = $wpdb->prefix . DB::$offer_table_name;
        $query = $wpdb->prepare("SELECT * FROM $volume_discount_table INNER JOIN $offer_table 
            ON $volume_discount_table.offer_id = $offer_table.id
            WHERE product_id = %s", $product_id);

        return $wpdb->get_results($query);
    }

    /**
     * Get the discounts associated with an offer
     * 
     * @param mixed $offer_id
     * 
     * @return array
     */
    public static function get_discounts_by_offer_id($offer_id)
    {
        global $wpdb;
        $volume_discount_table = $wpdb->prefix . DB::$volume_discount_table_name;
        $query = $wpdb->prepare("SELECT * FROM $volume_discount_table WHERE offer_id = %s ORDER BY id ASC", $offer_id);

        return $wpdb->get_results($query);
    }

    /**
     * @param mixed $offer_id
     * 
     * @return array
     */
    public static function get_discounts_by_offer_id_sort_by_qty_desc($offer_id)
    {
        global $wpdb;

        $volume_discount_table = $wpdb->prefix . DB::$volume_discount_table_name;

        $query = $wpdb->prepare("SELECT * FROM $volume_discount_table
            WHERE offer_id = %s
            ORDER BY CAST(number_of_products AS UNSIGNED) DESC", $offer_id);

        return $wpdb->get_results($query);
    }

    /**
     * @param mixed $offer_id
     * @param mixed $title
     * @param mixed $sale_price
     * 
     * @return array
     */
    public static function get_custom_variant_discount($offer_id, $title)
    {
        global $wpdb;

        $volume_discount_table = $wpdb->prefix . DB::$volume_discount_table_name;

        $query = $wpdb->prepare(
            "SELECT * FROM $volume_discount_table
            WHERE offer_id = %s and title = %s",
            $offer_id,
            $title,
        );

        $result = $wpdb->get_results($query);
        if ($result) return $result[0];
    }

    /**
     * Remove all the volume discounts for a give offer
     * 
     * @param mixed $offer_id
     * 
     * @return void
     */
    public static function delete_discounts($offer_id)
    {
        global $wpdb;

        $volume_discount_table = $wpdb->prefix . DB::$volume_discount_table_name;

        $wpdb->delete($volume_discount_table, array('offer_id' => $offer_id));
    }
}
