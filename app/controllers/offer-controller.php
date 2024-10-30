<?php

namespace Bundler\Controllers;

use Bundler\Models\Offer;
use Bundler\Models\Discount;
use Bundler\Helpers\ProductHelper;
use Bundler\Includes\Traits\Instance;

if (!defined('ABSPATH')) exit;

class OfferController
{

    use Instance;

    protected $currency_data;

    public function __construct()
    {
        $this->currency_data = array();

        if (has_filter('wbdl_get_currency_data')) {
            $this->currency_data = apply_filters('wbdl_get_currency_data', $this->currency_data);
        }
    }

    /**
     * Get all offers
     * 
     * @return array Bundler\Models|Offer
     */
    public function get_all_offers()
    {
        return Offer::get_all_offers();
    }

    /**
     * Get quantity discount offers
     * 
     * @return array Bundler\Models|Offer
     */
    public function get_qty_discount_offers()
    {
        return Offer::get_qty_discount_offers();
    }

    /**
     * Get offer data
     * 
     * @param mixed $offer_id
     * 
     * @return array
     */
    public function get_offer($offer_id)
    {
        $offer = Offer::get_offer_by_id($offer_id);
        if ($offer) {
            $discount_options = Discount::get_discounts_by_offer_id($offer_id);
            $products = $this->get_offer_products($offer);

            return [
                'status' => true,
                'offer' => [
                    'offerDetails'      => $offer,
                    'discountOptions'   => $discount_options,
                    'products'          => $products,
                    'productForPreview' => !empty($products) ? $products[0] : null,
                ],
                'msg' => __('fetched offer', 'bundler'),
            ];
        }
    }

    /**
     * Get the products linked to an offer
     * 
     * @param mixed $offer
     * 
     * @return array
     */
    private function get_offer_products($offer)
    {
        $products = [];
        if ($offer->product_ids) {
            $product_ids = explode(',', $offer->product_ids);

            foreach ($product_ids as $product_id) {
                $product = wc_get_product($product_id);
                $products[] = ProductHelper::format_product($product);
            }
        }
        return $products;
    }

    /**
     * @param mixed $offer_data
     * 
     * @return object
     */
    public function new_offer($offer_data)
    {

        if (!isset($offer_data)) return false;

        $offer_details = $offer_data['offerDetails'];
        $discount_options = $offer_data['discountOptions'];
        $product_ids = isset($offer_data['products']) ? array_column($offer_data['products'], 'id') : [];

        $title    = sanitize_text_field($offer_details['title']);
        $type     = $offer_details['type'] ? sanitize_text_field($offer_details['type']) : 'volume_discount';
        $header   = sanitize_text_field($offer_details['header']);
        $apply_on = sanitize_text_field($offer_details['apply_on']);
        // $status   = $offer_details['status']                              === 'on' ? 'on' : 'off';
        $status   = 'on';
        $priority = intval($offer_details['priority']);
        // $wmc      = isset($discount_options[0]) && isset($discount_options[0]['wmc']) && $offer_details['wmc'] === 'on' ? 'on' : 'off';
        $wmc                   = $offer_details['wmc']                   === 'on' ? 'on' : 'off';
        $use_custom_variations = $offer_details['use_custom_variations'] === 'on' ? 'on' : 'off';

        $offer_id = Offer::new_offer($title, $type, $header, $status, $priority, $apply_on, $wmc, $use_custom_variations);
        if (!$offer_id) {
            wp_send_json(array(
                'status'   => false,
                'msg'      => __('Error creating the offer.', 'bundler'),
            ));
        }

        if (!empty($product_ids)) Offer::update_offer_products($offer_id, $product_ids);

        foreach ($discount_options as $option) {
            Discount::add_discount_to_offer(
                $offer_id,
                $option['title'],
                $option['image_url'],
                $option['number_of_products'],
                $option['discount_type'],
                $option['discount_value'],
                $option['discount_method'],
                $option['sale_price'],
                $option['regular_price'],
                $option['wmc_sale_price'],
                $option['wmc_regular_price'],
                $use_custom_variations,
                $option['option1_name'],
                $option['option1_value'],
                $option['option2_name'],
                $option['option2_value'],
                $option['add_messages'],
                $option['message'],
                $option['discount_rule'],
                $option['preselected_offer'],
                $option['message_effect'],
                $option['show_price_per_unit'],
                $option['price_per_unit_text']
            );
        }

        return wp_send_json(array(
            'status'   => true,
            'offerId' => $offer_id,
            'msg'      => __('Offer created successfully.', 'bundler'),
        ));
    }

    /**
     * Update offer
     * 
     * @param mixed $offer_data
     * 
     * @return object
     */
    public function update_offer($offer_data)
    {
        if (!isset($offer_data)) return false;

        $offer_details = $offer_data['offerDetails'];
        $offer_id      = intval($offer_details['id']);

        if ($offer_id) {

            $discount_options = $offer_data['discountOptions'];

            $product_ids      = isset($offer_data['products']) ? array_column($offer_data['products'], 'id') : [];

            $title    = sanitize_text_field($offer_details['title']);
            $type     = sanitize_text_field($offer_details['type']);
            $header   = sanitize_text_field($offer_details['header']);
            $apply_on = sanitize_text_field($offer_details['apply_on']);
            $status   = $offer_details['status']                              === 'on' ? 'on' : 'off';
            $priority = intval($offer_details['priority']);
            // $wmc      = isset($discount_options[0]) && isset($discount_options[0]['wmc']) && $discount_options[0]['wmc'] === 'on' ? 'on' : 'off';
            $wmc                   = $offer_details['wmc']                   === 'on' ? 'on' : 'off';
            $use_custom_variations = $offer_details['use_custom_variations'] === 'on' ? 'on' : 'off';

            Offer::update_offer($offer_id, $title, $type, $header, $status, $priority, $apply_on, $wmc, $use_custom_variations);
            if (!empty($product_ids)) Offer::update_offer_products($offer_id, $product_ids);

            Discount::delete_discounts($offer_id);
            foreach ($discount_options as $option) {

                Discount::add_discount_to_offer(
                    $offer_id,
                    $option['title'],
                    $option['image_url'],
                    $option['number_of_products'],
                    $option['discount_type'],
                    $option['discount_value'],
                    $option['discount_method'],
                    $option['sale_price'],
                    $option['regular_price'],
                    $option['wmc_sale_price'],
                    $option['wmc_regular_price'],
                    $option['use_custom_variations'],
                    $option['option1_name'],
                    $option['option1_value'],
                    $option['option2_name'],
                    $option['option2_value'],
                    $option['add_messages'],
                    $option['message'],
                    $option['discount_rule'],
                    $option['preselected_offer'],
                    $option['message_effect'],
                    $option['show_price_per_unit'],
                    $option['price_per_unit_text']
                );
            }

            return wp_send_json(array(
                'status'   => true,
                'msg'      => __('Offer updated successfully.', 'bundler'),
            ));
        }

        return wp_send_json(array(
            'status'   => false,
            'msg'      => __('Error updating offer.', 'bundler'),
        ));
    }

    /**
     * Get discounts for a given offer
     * 
     * @param mixed $offer_id
     * 
     * @return Bundler\Models\Discount
     */
    public function get_discounts_by_offer_id($offer_id)
    {
        return Discount::get_discounts_by_offer_id($offer_id);
    }

    /**
     * @param mixed $offer_id
     * 
     * @return BdlrPck\Models\Bundle
     */
    public function get_bundle_by_offer_id($offer_id)
    {
        if (class_exists('\BdlrPck\Models\Bundle')) {
            $bundles = \BdlrPck\Models\Bundle::get_bundle_by_offer_id($offer_id);
            if ($bundles) return $bundles[0];
        }
        return false;
    }

    /**
     * Get the highest priority Volume Discount offer for the product id
     * 
     * @param mixed $product_id
     * 
     * @return Bundler\Models\Offer
     */
    public function get_the_offer($product_id)
    {
        $offer = Offer::get_highest_priority_offer_by_product_id($product_id);
        if ($offer) {
            $discount_options = Discount::get_discounts_by_offer_id($offer->id);
            // $products = $this->get_offer_products($offer);

            return [
                'status' => true,
                'offer' => [
                    'offerDetails'      => $offer,
                    'discountOptions'   => $discount_options,
                ],
                'msg' => __('Fetched offer', 'bundler'),
            ];
        }

        return [
            'status' => false,
            'msg' => __('No offer found', 'bundler'),
        ];
    }


    /**
     * Get the volume discounts of the active offer that has the higher priority for a certain product
     * 
     * @param mixed $product_id
     * 
     * @return array|null
     */
    public function get_the_discounts($product_id)
    {
        $offer =  Offer::get_highest_priority_offer_by_product_id($product_id);
        if ($offer) {
            $offer_id = $offer->id;
            return Discount::get_discounts_by_offer_id($offer_id);
        }

        return null;
    }

    /**
     * Get the volume discounts of the active offer that has the higher priority for a certain product
     * 
     * @param mixed $product_id
     * 
     * @return object|null
     */
    public function get_custom_variant_discount($product_id, $title)
    {

        if ($product_id)
            $offer =  Offer::get_highest_priority_offer_by_product_id($product_id);

        if ($offer && $title) {
            $offer_id = $offer->id;
            return Discount::get_custom_variant_discount($offer_id, $title);
        }

        return null;
    }

    /**
     * Get discounts by product ID, sorted by Qty Desc. This is used to calculate the discount in the cart.
     * 
     * @param mixed $product_id
     * 
     * @return array|null
     */
    public function get_the_discounts_desc($product_id)
    {
        $offer =  Offer::get_highest_priority_offer_by_product_id($product_id);
        if ($offer) {
            $offer_id = $offer->id;
            return Discount::get_discounts_by_offer_id_sort_by_qty_desc($offer_id);
        }

        return null;
    }

    /**
     * Delete multiple offers
     *
     * @param array $offer_ids
     * @return boolean
     */
    public function delete_offers($offer_ids)
    {
        try {
            foreach ($offer_ids as $offer_id) {
                if (!$this->delete_offer($offer_id)) {
                    throw new Exception('Failed to delete offer ID: ' . $offer_id);
                }
            }
            return true;
        } catch (Exception $e) {
            return false;
        }
    }

    /**
     * Delete a single offer by offer ID
     *
     * @param int $offer_id
     * @return boolean
     */
    public function delete_offer($offer_id)
    {
        global $wpdb;

        $wpdb->query('START TRANSACTION');

        try {
            Discount::delete_discounts($offer_id);
            Offer::delete_offer($offer_id);

            $wpdb->query('COMMIT');
            return true;
        } catch (Exception $e) {
            $wpdb->query('ROLLBACK');
            return false;
        }
    }

    /**
     * update an offer status (enable or disable)
     * 
     * @param mixed $offer_id
     * @param mixed $status
     * 
     * @return void
     */
    public function update_offer_status($offer_id, $status)
    {
        Offer::update_offer_status($offer_id, $status);
    }
}

return OfferController::get_instance();
