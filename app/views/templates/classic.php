<?php

use Bundler\Helpers\CartHelper;

?>
<div class="quantity-breaks__classic">

	<?php

	foreach ($discount_options as $discount_option) {

		$discount_option_price = $initial_price * $discount_option->number_of_products;

		$sale_price = $regular_price = $discount_option_price;

		if ($discount_option->discount_type === 'discount') { // dynamic discount
			if ($discount_option->discount_method === 'percent_off') {
				$sale_price = $discount_option_price * (1 - $discount_option->discount_value / 100);
			} else if ($discount_option->discount_method === 'value_off') {
				$sale_price = $discount_option_price - $discount_option->discount_value;
			}
		} else if (!$discount_option->discount_type || $discount_option->discount_type === 'fixed_price') { // fixed price
			if ($discount_option->sale_price) {
				$sale_price = $discount_option->sale_price;
			}
			if ($discount_option->regular_price && $discount_option->regular_price != '') {
				$regular_price = $discount_option->regular_price;
			}
		}

		$sale_price    = CartHelper::get_wmc_price($sale_price);
		$regular_price = CartHelper::get_wmc_price($regular_price);
	?>
		<div class="quantity-break" data-products_number="<?php echo esc_attr_e($discount_option->number_of_products); ?>" data-bundle_id="<?php echo esc_attr($product_id) . '-' . esc_attr($discount_option->id); ?>" data-discount_type="<?php echo esc_attr($discount_option->discount_type); ?>" <?php if ($discount_option->discount_type == 'discount') : ?> data-discount_method="<?php echo esc_attr($discount_option->discount_method); ?>" data-discount_value="<?php echo esc_attr($discount_option->discount_value); ?>" <?php endif; ?> data-sale_price="<?php echo esc_attr($sale_price); ?>" data-regular_price="<?php echo esc_attr($regular_price); ?>">


			<div class="quantity-break__radio <?php if ($discount_option->preselected_offer == "on") echo 'active'; ?>">
				<input type="radio" class="radio_select" name="bundle_select" value="1" <?php if ($discount_option->preselected_offer == "on") echo "checked"; ?>>
			</div>

			<div class="quantity-break__content">

				<?php
				if ($discount_option->message) {
					if ($discount_option->message_effect == 'blinking') { ?>
						<p class="bundle-message-blink"><?php if ($discount_option->add_messages == 'on' && $discount_option->message) esc_html_e($discount_option->message); ?></p>
					<?php } else { ?>
						<p class="bundle-message-not-blink"><?php if ($discount_option->add_messages == 'on' && $discount_option->message) esc_html_e($discount_option->message); ?></p>
				<?php }
				}
				?>

				<h5 class="quantity-break__title" data-number="<?php esc_html_e($discount_option->number_of_products); ?>" data-title="<?php esc_attr_e($discount_option->title); ?>"><?php esc_html_e($discount_option->title); ?></h5>

				<div class="quantity-break__price">
					<?php
					if ($regular_price && ($regular_price > $sale_price)) {
						if ($regular_price != 0) {
					?>
							<span class="bundle-cprice" name="bundle_cprice" value="<?php echo esc_attr(Strip_tags($regular_price)); ?>"><?php esc_html_e(Strip_tags(wc_price($regular_price))); ?></span>
					<?php
						}
					} ?>
					<span class="bundle-price" name="bundle_price" value="<?php esc_attr_e(Strip_tags($sale_price)); ?>"><?php esc_html_e(Strip_tags(wc_price($sale_price))); ?></span>
				</div>

				<?php
				if ($discount_option->add_messages == 'on' && $discount_option->discount_rule) {
					$str = $discount_option->discount_rule;
					if (str_contains($str, '($)') || str_contains($str, '(%)')) {
						if ($regular_price && $sale_price) {
							$discount = $regular_price - $sale_price;
							$percentChange = (1 - $sale_price / $regular_price) * 100;
							$percent = number_format($percentChange);
							$str = str_replace("($)", wc_price($discount), $str);
							$str = str_replace("(%)", $percent . '%', $str);
						}
					}
				?>
					<span class="quantity-break__discount-rule">
						<?php echo $str; ?>
					</span>
				<?php
				}
				?>
			</div>
		</div>
	<?php
	} ?>
</div>