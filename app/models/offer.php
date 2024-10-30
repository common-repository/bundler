<?php

namespace Bundler\Models;

if (!defined('ABSPATH')) exit; // Exit if accessed directly

class Offer
{

    /**
     * Get all offers
     * 
     * @return array
     */
    public static function get_all_offers()
    {
        global $wpdb;

        $offer_table = $wpdb->prefix . DB::$offer_table_name;
        $offer_products_table = $wpdb->prefix . DB::$offer_products_table_name;

        $query = $wpdb->prepare("
            SELECT o.id, o.title, o.header, o.type, o.apply_on, o.priority, o.status, GROUP_CONCAT(op.product_id) as product_ids
            FROM $offer_table o 
            LEFT JOIN $offer_products_table op ON o.id = op.offer_id 
            GROUP BY o.id
        ", '');

        $offers = $wpdb->get_results($query);

        foreach ($offers as $offer) {
            if ($offer->apply_on === 'all_products') {
                // Fetch the first product as a default for preview
                $default_product = wc_get_products(array(
                    'limit' => 1,
                    'orderby' => 'date',
                    'order' => 'DESC',
                ));

                if (!empty($default_product)) {
                    $product = $default_product[0];
                    $offer->product_permalink = get_permalink($product->get_id());
                    $offer->product_name = $product->get_name();
                } else {
                    $offer->product_permalink = '';
                    $offer->product_name = '';
                }
            } else {
                $product_ids = explode(',', $offer->product_ids);
                $products = [];
                foreach ($product_ids as $product_id) {
                    $product = wc_get_product($product_id);
                    if ($product) {
                        $products[] = (object) [
                            'id' => $product_id,
                            'name' => $product->get_name(),
                            'permalink' => get_permalink($product_id),
                        ];
                    }
                }

                $offer->products = $products;
                $offer->product_permalink = isset($products[0]) ? $products[0]->permalink : '';
            }
        }

        return $offers;
    }

    /**
     * Get quantity discount offers
     * 
     * @return array
     */
    public static function get_qty_discount_offers()
    {
        global $wpdb;

        $offer_table = $wpdb->prefix . DB::$offer_table_name;
        $query = "SELECT * FROM $offer_table WHERE type = 'volume_discount'";
        return $wpdb->get_results($query);
    }

    /**
     * Get offer by id
     * 
     * @param mixed $offer_id
     * 
     * @return object
     */
    public static function get_offer_by_id($offer_id)
    {
        global $wpdb;

        $offer_table = $wpdb->prefix . DB::$offer_table_name;
        $offer_products_table = $wpdb->prefix . DB::$offer_products_table_name;

        $query = $wpdb->prepare("SELECT o.*, GROUP_CONCAT(op.product_id) as product_ids
                             FROM $offer_table o 
                             LEFT JOIN $offer_products_table op ON o.id = op.offer_id 
                             WHERE o.id = %d 
                             GROUP BY o.id", $offer_id);

        $offers = $wpdb->get_results($query);

        return !empty($offers) ? $offers[0] : null;
    }


    /**
     * Get the offer
     * 
     * @param mixed $product_id
     * 
     * @return Bundler\Models\Offer
     */
    public static function get_highest_priority_offer_by_product_id($product_id)
    {
        global $wpdb;

        $offer_table = $wpdb->prefix . DB::$offer_table_name;
        $offer_products_table = $wpdb->prefix . DB::$offer_products_table_name;

        // Query to check for the highest priority offer that applies to a specific product
        $query_specific = $wpdb->prepare(
            "SELECT o.*, GROUP_CONCAT(op.product_id) as product_ids
         FROM $offer_table o 
         LEFT JOIN $offer_products_table op ON o.id = op.offer_id 
         WHERE op.product_id = %d AND o.status = 'on'
         GROUP BY o.id 
         ORDER BY o.priority DESC 
         LIMIT 1",
            $product_id
        );

        // Query to check for the highest priority offer that applies to all products
        $query_all = "SELECT o.*, GROUP_CONCAT(op.product_id) as product_ids
                  FROM $offer_table o 
                  LEFT JOIN $offer_products_table op ON o.id = op.offer_id 
                  WHERE o.apply_on = 'all_products' AND o.status = 'on'
                  GROUP BY o.id 
                  ORDER BY o.priority DESC 
                  LIMIT 1";

        // Execute both queries
        $offer_specific = $wpdb->get_row($query_specific);
        $offer_all = $wpdb->get_row($query_all);

        // Compare the two offers by priority
        if ($offer_specific && $offer_all) {
            return ($offer_specific->priority >= $offer_all->priority) ? $offer_specific : $offer_all;
        } elseif ($offer_specific) {
            return $offer_specific;
        } elseif ($offer_all) {
            return $offer_all;
        }

        return null;
    }

    /**
     * @param mixed $title
     * @param mixed $type
     * @param mixed $header
     * @param mixed $status
     * @param mixed $priority
     * @param mixed $apply_on
     * @param mixed $wmc
     * 
     * @return mixed
     */
    public static function new_offer($title, $type, $header, $status, $priority, $apply_on, $wmc, $use_custom_variations)
    {
        global $wpdb;

        $offer_table = $wpdb->prefix . DB::$offer_table_name;

        $wpdb->insert(
            $offer_table,
            array(
                'title'                 => $title,
                'type'                  => $type,
                'header'                => $header,
                'status'                => $status,
                'priority'              => $priority,
                'apply_on'              => $apply_on,
                'wmc'                   => $wmc,
                'use_custom_variations' => $use_custom_variations
            ),
            array(
                '%s',      // title
                '%s',      // type
                '%s',      // header
                '%s',      // status
                '%s',      // priority
                '%s',      // apply on
                '%s',      // wmc
                '%s'       // use custom variations
            )
        );

        if (!$wpdb->last_error) {
            // Get the inserted offer id
            $offer_id = $wpdb->insert_id;
            // For now, the priority of the offer is its id
            $wpdb->update($offer_table, array('priority' => intval($offer_id)), array('id' => intval($offer_id)));
            return $offer_id;
        } else {
            return false;
        }
    }

    /**
     * @param mixed $offer_id
     * @param mixed $title
     * @param mixed $type
     * @param mixed $header
     * @param mixed $status
     * @param mixed $priority
     * @param mixed $apply_on
     * @param mixed $wmc
     * @param mixed $use_custom_variations
     * 
     * @return mixed
     */
    public static function update_offer($offer_id, $title, $type, $header, $status, $priority, $apply_on, $wmc, $use_custom_variations)
    {
        global $wpdb;

        $offer_table = $wpdb->prefix . DB::$offer_table_name;

        return $wpdb->update(
            $offer_table,
            array(
                'title'                 => $title,
                'type'                  => $type,
                'header'                => $header,
                'status'                => $status,
                'priority'              => $priority,
                'apply_on'              => $apply_on,
                'wmc'                   => $wmc,
                'use_custom_variations' => $use_custom_variations
            ),
            array('id' => intval($offer_id)),
            array(
                '%s',  // title
                '%s',  // type
                '%s',  // header
                '%s',  // status
                '%s',  // priority
                '%s',  // apply on
                '%s',  // wmc
                '%s'   // use custom variations
            ),
            array('%d') // offer_id
        );
    }

    /**
     * Remove offer
     * 
     * @param mixed $offer_id
     * 
     * @return void
     */
    public static function delete_offer($offer_id)
    {
        global $wpdb;

        $offer_table = $wpdb->prefix . DB::$offer_table_name;

        $wpdb->delete($offer_table, array('id' => $offer_id));
    }

    /**
     * enable/disable an offer
     * 
     * @param mixed $offer_id
     * @param mixed $status
     * 
     * @return void
     */
    public static function update_offer_status($offer_id, $status)
    {
        global $wpdb;

        $offer_table = $wpdb->prefix . DB::$offer_table_name;
        $wpdb->update($offer_table, array('status' => $status), array('id' => intval($offer_id)));
    }

    /**
     * @param mixed $offer_id
     * @param mixed $product_ids
     * 
     * @return void
     */
    public static function update_offer_products($offer_id, $product_ids)
    {
        global $wpdb;

        $offer_products_table = $wpdb->prefix . DB::$offer_products_table_name;

        $wpdb->delete($offer_products_table, array('offer_id' => $offer_id));

        foreach ($product_ids as $product_id) {
            $wpdb->insert($offer_products_table, array('offer_id' => $offer_id, 'product_id' => $product_id));
        }
    }

    /**
     * @param mixed $offer_id
     * 
     * @return array
     */
    public static function get_offer_products($offer_id)
    {
        global $wpdb;

        $offer_products_table = $wpdb->prefix . DB::$offer_products_table_name;
        $query = $wpdb->prepare("SELECT op.* FROM $offer_products_table WHERE op.offer_id = %s", $offer_id);

        $product_ids = $wpdb->get_results($query);

        return $product_ids;
    }
}
