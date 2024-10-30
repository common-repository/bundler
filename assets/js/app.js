jQuery(document).ready(function ($) {
  var $form = $("form.cart");

  /**
   * Format the price with Woo structure
   *
   * @param mixed price
   *
   * @return mixed
   */
  function formatPrice(price) {
    const currencyData =
      bdlrData.currency_data && bdlrData.currency_data.currencies
        ? bdlrData.currency_data.currencies[
            bdlrData.currency_data.current_currency
          ]
        : null;

    const formatParams = {
      decimal_sep:
        currencyData?.decimal_sep && currencyData.decimal_sep !== ""
          ? currencyData.decimal_sep
          : bdlrData.wc_store_object.decimal_sep,
      position:
        currencyData?.pos && currencyData.pos !== ""
          ? currencyData.pos
          : bdlrData.wc_store_object.position,
      symbol:
        currencyData?.symbol && currencyData.symbol !== ""
          ? currencyData.symbol
          : bdlrData.wc_store_object.symbol,
      thousand_sep:
        currencyData?.thousand_sep && currencyData.thousand_sep !== ""
          ? currencyData.thousand_sep
          : bdlrData.wc_store_object.thousand_sep,
      decimals:
        currencyData?.decimals && currencyData.decimals !== ""
          ? currencyData.decimals
          : bdlrData.wc_store_object.decimals,
      html: true,
    };

    // Convert price to a number and round it to the specified number of decimal places
    var roundedPrice = parseFloat(price).toFixed(formatParams.decimals);

    // Replace the decimal separator with the appropriate separator
    var formattedPrice = roundedPrice.replace(".", formatParams.decimal_sep);

    // Format the currency symbol
    var formattedSymbol = formatParams.html
      ? '<span class="woocommerce-Price-currencySymbol">' +
        formatParams.symbol +
        "</span>"
      : formatParams.symbol;

    // Add the currency symbol to the formatted price based on the currency position

    switch (formatParams.position) {
      case "left":
        formattedPrice = `${formattedSymbol}${formattedPrice}`;
        break;
      case "right":
        formattedPrice = `${formattedPrice}${formattedSymbol}`;
        break;
      case "left_space":
        formattedPrice = `${formattedSymbol} ${formattedPrice}`;
        break;
      case "right_space":
        formattedPrice = `${formattedPrice} ${formattedSymbol}`;
        break;
      default:
        formattedPrice = `${formattedSymbol}${formattedPrice}`; // Default format
    }

    // Add HTML wrappers if needed
    formattedPrice = formatParams.html
      ? '<span class="woocommerce-Price-amount amount">' +
        formattedPrice +
        "</span>"
      : formattedPrice;

    return formattedPrice;
  }

  /**
   * Update the displayed product price
   *
   * @param mixed salePrice
   * @param mixed regularPrice
   *
   * @return void
   */
  function updateDisplayedProductPrice(salePrice, regularPrice) {
    // var price = $(".summary").find(".price").first();

    var price = $form.parent().find(".price");
    price.empty();

    if (regularPrice && regularPrice != 0 && regularPrice != salePrice) {
      // If compared price + sale price
      salePrice = formatPrice(salePrice);
      regularPrice = formatPrice(regularPrice);

      price.append("<del></del><ins></ins>");
      price.find("del").html(regularPrice);
      price.find("ins").html(salePrice);
    } else {
      // If sale price only (no regular price)
      salePrice = formatPrice(salePrice);
      price.append(salePrice);
    }
  }

  /**
   * select an offer and unselect the others
   *
   * @param mixed bundle
   * @return void
   */
  function selectOffer(bundle) {
    var bundleId = $(bundle).data("bundle_id");
    // get all the bundle widgets with the same id
    var bundles = $(document)
      .find(".wbdl_widget")
      .find("div.quantity-break[data-bundle_id='" + bundleId + "']");

    bundles.each(function () {
      // Hide non-active bundles details and show active bundle details.
      var parent = $(this).closest(".wbdl_widget");
      parent
        .find("div[class^='quantity-break'][class$='active']")
        .each(function (i, obj) {
          $(obj).removeClass("active");
        });

      // Activate the clicked bundle
      $(this).addClass("active");

      // Hide the bundle details
      parent
        .find("div[class^='quantity-break']:not(.active)")
        .find(".bundle-variation")
        .css("display", "none");
      parent
        .find("div[class^='quantity-break']:not(.active)")
        .find(".custom-vari .option2")
        .removeAttr("name");
      parent
        .find("div[class^='quantity-break']:not(.active)")
        .find(".custom-vari .option1")
        .removeAttr("name");

      // Display the selected bundle's details
      $(this).find(".bundle-variation").css("display", "flex");
      if ($(this).find('.img_thumbnail[src!=""]').length) {
        $(this).find(".variant-title").css("width", "100%");
      }
      $(this).find(".radio_select").prop("checked", true);
    });

    // Update the displayed regular and sale price on the product page according to the selected bundle's price
    var regularPrice = $(bundle).find(".bundle-cprice").attr("value") || 0;
    var salePrice = $(bundle).find(".bundle-price").attr("value") || 0;

    updateDisplayedProductPrice(salePrice, regularPrice);
  }

  /**
   * Add offer to cart
   * @param mixed $atcButton
   * @param mixed $bundlerWidget
   * @return void
   */
  function addOfferToCart($atcButton, $bundlerWidget) {
    var id = $atcButton.val(),
      nonce = $bundlerWidget.find("input[name=wbdl_nonce]").val(),
      productId = $bundlerWidget.data("product_id") || id,
      productType =
        $bundlerWidget.find("input[name=wbdl_product_type]").val() || "simple",
      productQty = $form.find("input[name=quantity]").val() || 1,
      productsNb =
        $bundlerWidget.find(".quantity-break.active").data("products_number") ||
        1;

    var data = {
      nonce: nonce,
      product_id: productId,
      products_num: productsNb,
      product_quantity: productQty,
    };

    var ajax_url = woocommerce_params.wc_ajax_url
      .toString()
      .replace("%%endpoint%%", "wbdl_add_to_cart");

    const cart_redirect = bdlrData.cart_redirect;
    const checkout_redirect = bdlrData.checkout_redirect;

    $(document.body).trigger("adding_to_cart", [$atcButton, data]);

    $.ajax({
      url: ajax_url,
      type: "POST",
      dataType: "json",
      data: data,
      beforeSend: function (response) {
        $atcButton.removeClass("added").addClass("loading");
      },
      complete: function (response) {
        $atcButton.addClass("added").removeClass("loading");
        // $(document.body).trigger("wc_reload_fragments");
        // console.log("complete " + JSON.stringify(response.responseJSON));
      },
      success: function (response) {
        if (response.error) {
          alert(response.message);
          return;
        }
        $(document.body).trigger("added_to_cart", [
          response.fragments,
          response.cart_hash,
          $atcButton,
        ]);
        $(document.body).trigger("wc_fragment_refresh");

        if (checkout_redirect === "on") {
          window.location = bdlrData.woo_checkout_url;
        } else if (cart_redirect == "on") {
          window.location = bdlrData.woo_cart_url;
        }
      },
    });
  }

  /**
   * Click on the preselected bundle offer
   */
  $(".wbdl_widget")
    .find("div[class^='quantity-break']:not(.active)")
    .find(".bundle-variation")
    .css("display", "none");
  $(".wbdl_widget")
    .find(".quantity-break__radio.active")
    .each(function (i, obj) {
      setTimeout(function () {
        $(obj).closest("div[class^='quantity-break']").click();
      }, 1000);
    });

  /**
   * Action after a bundle offer is selected
   * */
  $(".wbdl_widget")
    .find(".quantity-break")
    .on("click", function (e) {
      if ($(this).hasClass("active")) return;
      $(".single_add_to_cart_button").removeClass("disabled");
      selectOffer(this);
    });

  /**
   * Action after add to cart (Ajax add to cart)
   */
  $(".single_add_to_cart_button").on("click touchend", function (e) {
    if ($(this).hasClass("disabled")) return;
    var $atcButton = $(this),
      $form = $atcButton.closest("form.cart");

    var $bundlerWidget = $form.find("div[class='wbdl_widget']").length
      ? $form.find("div[class='wbdl_widget']")
      : $(document).find("div[class='wbdl_widget']");

    if ($bundlerWidget.length) {
      // If Any bundles for this product
      if ($bundlerWidget.find(".radio_select").is(":checked")) {
        e.preventDefault();
        e.stopPropagation();
        e.stopImmediatePropagation(); // Stop propagation to prevent AJAX add-to-cart
        addOfferToCart($atcButton, $bundlerWidget);
      } else {
        e.preventDefault();
        e.stopImmediatePropagation(); // Stop propagation to prevent AJAX add-to-cart
        alert(bdlrData.i18n.select_offer_message);
      }
    }
  });
});
