<?php

namespace Bundler\Models;

if (!defined('ABSPATH')) exit;

class Settings
{

    /**
     * Get the current settings
     * 
     * @return object
     */
    public static function get_vd_settings()
    {
        global $wpdb;

        $vd_settings_table = $wpdb->prefix . DB::$volume_discount_settings_table_name;

        $settings = $wpdb->get_results("SELECT * FROM $vd_settings_table ");

        if ($settings && is_array($settings)) {
            return $settings[0];
        } else return null;
    }

    /**
     * Update settings
     * 
     * @param array $settings
     * 
     * @return void
     */
    public static function update_vd_settings($settings)
    {
        global $wpdb;
        $settings_table = $wpdb->prefix . DB::$volume_discount_settings_table_name;

        $existing_settings = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM $settings_table WHERE id = %d",
            1
        ));

        if ($existing_settings > 0) {
            return $wpdb->update(
                $settings_table,
                $settings,
                array('id' => 1)
            );
        } else {
            $settings['id'] = 1; // Ensure the id is set to 1
            return $wpdb->insert(
                $settings_table,
                $settings
            );
        }
    }

    /**
     * Get the default settings
     * 
     * @return object
     */
    public static function get_default_settings()
    {
        $default_settings = (object) [
            'store_font'                => 'Cabin',
            'design'                    => 'classic',
            'design_mobile'             => 'classic',
            'header_show'               => 'off',
            'header_text'               => '',
            'radio_show'                => 'on',
            'qty_selector'              => 'off',
            'cart_redirect'             => 'off',
            'checkout_redirect'         => 'off',
            'atc_price_text'            => 'off',
            'background_color'          => '#ffffff',
            'background_color_active'   => '#f2f2fe',
            'border_color'              => '#dbdbdb',
            'border_color_active'       => '#565add',
            'title_color'               => '#000000',
            'sale_price_color'          => '#565add',
            'regular_price_color'       => '#afafaf',
            'message1_color'            => '#565add',
            'message2_color'            => '#ffffff',
            'message2_background_color' => '#565add',
            'attribute_name_color'      => '#000000',
            'dropdown_text_color'       => '#000000',
            'dropdown_background_color' => '#ffffff',
            'header_font'               => json_encode([
                'fontFamily' => 'store_font',
                'fontSize'   => '',
                'fontWeight' => '',
            ]),
            'title_font' => json_encode([
                'fontFamily' => 'store_font',
                'fontSize'   => '',
                'fontWeight' => '',
            ]),
            'price_font' => json_encode([
                'fontFamily' => 'store_font',
                'fontSize'   => '',
                'fontWeight' => '',
            ]),
            'message1_font' => json_encode([
                'fontFamily' => 'store_font',
                'fontSize'   => '',
                'fontWeight' => '',
            ]),
            'message2_font' => json_encode([
                'fontFamily' => 'store_font',
                'fontSize'   => '',
                'fontWeight' => '',
            ]),
            'attribute_name_font' => json_encode([
                'fontFamily' => 'store_font',
                'fontSize'   => '',
                'fontWeight' => '',
            ]),
            'dropdown_font' => json_encode([
                'fontFamily' => 'store_font',
                'fontSize'   => '',
                'fontWeight' => '',
            ]),
            'atc_button_font' => json_encode([
                'fontFamily' => 'store_font',
                'fontSize'   => '',
                'fontWeight' => '',
            ]),
            'atc_button_background_color' => '#565add',
            'atc_button_border_color'     => '#565add',
            'atc_button_text_color'       => '#ffffff',
            'atc_button_show_custom_text' => 'off',
            'atc_button_custom_text'      => '',
        ];


        return $default_settings;
    }
}
