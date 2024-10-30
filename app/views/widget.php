<?php

/**
 * Widget that will be displayed in the frontend
 *
 * @since 1.2.7
 */

namespace Bundler\Views;

use Bundler\Controllers\OfferController;
use Bundler\Controllers\SettingsController;
use Bundler\Includes\Traits\Instance;

if (!defined('ABSPATH')) exit; // Exit if accessed directly

class Widget
{
    use Instance;

    public function __construct()
    {
        add_action('woocommerce_before_add_to_cart_button', array(__CLASS__, 'display_widget'));
    }

    public static function display_widget()
    {
        global $post;

        if (get_post_type($post) === 'product' && !is_a($post, 'WC_Product')) {

            $settings = SettingsController::get_instance()->get_vd_settings();
            if (!$settings || empty($settings)) $settings = SettingsController::get_instance()->get_default_settings();

            $product_id = get_the_ID();
            $product    = wc_get_product($product_id);

            if ($product->is_type('simple') || $product->is_type('subscription')) {

                $offer_controller = OfferController::get_instance();
                $offer_data       = $offer_controller->get_the_offer($product_id);

                if (isset($offer_data)) {
                    if ($offer_data['status']) {
                        $offer            = $offer_data['offer']['offerDetails'];
                        $discount_options = $offer_data['offer']['discountOptions'];

                        if ($discount_options && is_array($discount_options)) {

?>

                            <div class="wbdl_widget" data-product_id="<?php esc_attr_e($product_id); ?>">
                                <style>
                                    table.variations {
                                        display: none !important;
                                    }

                                    <?php
                                    if ($settings->qty_selector == 'off') {
                                    ?>.woocommerce .product button.single_add_to_cart_button {
                                        height: 70px !important;
                                        width: 100% !important;
                                        margin-left: 0 !important;
                                    }

                                    @media (max-width: 1024px) {
                                        .summary-inner .single_add_to_cart_button.button.alt {
                                            height: 70px !important;
                                            width: 100% !important;
                                            margin-left: 0 !important;
                                        }
                                    }

                                    .woocommerce .product .quantity {
                                        display: none !important;
                                    }

                                    <?php
                                    }
                                    ?>.quantity-break {
                                        border-color: <?php esc_html_e($settings->border_color); ?> !important;
                                        background: <?php esc_html_e($settings->background_color); ?> !important
                                    }

                                    .quantity-break.active {
                                        border-color: <?php esc_html_e($settings->border_color_active); ?> !important;
                                        background: <?php esc_html_e($settings->background_color_active); ?> !important;
                                    }

                                    .quantity-break .quantity-break__title,
                                    .quantity-break.active .quantity-break__title {
                                        color: <?php esc_html_e($settings->title_color); ?> !important;
                                    }

                                    .quantity-break.active .bundle-message-blink,
                                    .quantity-break.active .bundle-message-not-blink {
                                        color: <?php esc_html_e($settings->message1_color); ?> !important
                                    }

                                    .quantity-break .bundle-price,
                                    .quantity-break.active .bundle-price {
                                        color: <?php esc_html_e($settings->sale_price_color); ?> !important
                                    }

                                    .quantity-break .bundle-cprice,
                                    .quantity-break.active .bundle-cprice {
                                        color: <?php esc_html_e($settings->regular_price_color); ?> !important
                                    }

                                    .quantity-break .quantity-break__radio input[type=radio]:checked {
                                        background: <?php esc_html_e($settings->border_color_active); ?> !important;
                                    }

                                    <?php if ($settings->radio_show === 'off') { ?>.quantity-break .quantity-break__radio input[type=radio] {
                                        display: none;
                                    }

                                    .quantity-break .quantity-break__radio {
                                        min-width: 25px;
                                    }

                                    <?php } ?>
                                </style>

                                <tbody>
                                    <input type="hidden" name="wbdl_nonce" value="<?php echo wp_create_nonce('bundler'); ?>">
                                    <input type="hidden" class="productType" name="wbdl_product_type" value="<?php esc_attr_e($product->get_type()); ?>">
                                    <input type="hidden" class="productId" name="product_id" value="<?php esc_attr_e($product_id); ?>">

                                    <p class="offer-header">
                                        <?php if ($offer->header && $offer->header !== '') {
                                            esc_html_e($offer->header);
                                        } else {
                                            if ($settings->header_show === 'on') {
                                                esc_html_e($settings->header_text);
                                            }
                                        } ?>
                                    </p>

                                    <?php
                                    $initial_price = $product->get_price();
                                    include BDLR_PLUGIN_DIR . 'app/views/templates/classic.php'; ?>
                                </tbody>
                            </div>
<?php

                        }
                    }
                }
            }
        }
    }
}

return Widget::get_instance();
