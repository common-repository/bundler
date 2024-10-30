<?php

namespace Bundler\Controllers;

use Bundler\Models\Settings;
use Bundler\Includes\Traits\Instance;

if (!defined('ABSPATH')) exit;

class SettingsController
{

    use Instance;

    /**
     * Get volume discount settings
     * 
     * @return object
     */
    public function get_vd_settings()
    {
        return Settings::get_vd_settings();
    }

    /**
     * Update template settings
     * 
     * @return void
     */
    public function update_vd_template_settings()
    {
        $options = array(
            'id'                => FILTER_SANITIZE_STRING,
            'design'            => FILTER_SANITIZE_STRING,
            'design_mobile'     => FILTER_SANITIZE_STRING,
            'bundles_title'     => FILTER_SANITIZE_STRING,
            'title_show'        => FILTER_SANITIZE_STRING,
            'qty_selector'      => FILTER_SANITIZE_STRING,
            'cart_redirect'     => FILTER_SANITIZE_STRING,
            'checkout_redirect' => FILTER_SANITIZE_STRING,
            'atc_price_text'    => FILTER_SANITIZE_STRING
        );

        $inputs = filter_input_array(INPUT_POST, $options);

        $design_desktop    = $inputs['design'];
        $design_mobile     = $inputs['design_mobile'];
        $bundles_title     = $inputs['bundles_title'];
        $title_show        = isset($inputs['title_show']) ? 'on' : 'off';
        $qty_selector      = isset($inputs['qty_selector']) ? 'on' : 'off';
        $cart_redirect     = isset($inputs['cart_redirect']) ? 'on' : 'off';
        $checkout_redirect = isset($inputs['checkout_redirect']) ? 'on' : 'off';
        $atc_price_text    = isset($inputs['atc_price_text']) ? 'on' : 'off';

        Settings::update_vd_template_settings(
            $design_desktop,
            $design_mobile,
            $bundles_title,
            $title_show,
            $qty_selector,
            $cart_redirect,
            $checkout_redirect,
            $atc_price_text
        );
    }

    public function update_vd_settings($settings)
    {

        $allowed_keys = array(
            'header_text',
            'header_show',
            'qty_selector',
            'cart_redirect',
            'checkout_redirect',
            'radio_show',
        );

        $filtered_settings = array_filter($settings, function ($key) use ($allowed_keys) {
            return in_array($key, $allowed_keys);
        }, ARRAY_FILTER_USE_KEY);

        $result = Settings::update_vd_settings($filtered_settings);

        return $result;
    }

    /**
     * Get the default settings
     * 
     * @return object
     */
    public function get_default_settings()
    {
        return Settings::get_default_settings();
    }
}

return SettingsController::get_instance();
