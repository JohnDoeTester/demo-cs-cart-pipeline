(function (_, $) {
  var hasShownButtons = false;

  function init(stripeInstances, publishableKey, $paymentButton, isLastPaymentButton) {
    if (!stripeInstances[publishableKey]) {
      stripeInstances[publishableKey] = Stripe(publishableKey);
    }

    var stripeInstance = stripeInstances[publishableKey];

    try {
      setupPaymentButton(stripeInstance, $paymentButton, isLastPaymentButton);
    } catch (exception) {
      console.error(exception);
    }
  }

  function getPaymentRequestData(result, $paymentButton, paymentIntentId) {
    var paymentId = $paymentButton.data('caStripePaymentId'),
        productId = $paymentButton.data('caStripeProductId'),
        productOptions = $paymentButton.data('caStripeProductOptions'),
        shippingId,
        userData,
        paymentInfo;

    if (result.shippingOption) {
      $paymentButton.data('caStripeShippingId', parseInt(result.shippingOption.id.split('_').pop()));
    }

    if ($paymentButton.data('caStripeShippingId')) {
      shippingId = $paymentButton.data('caStripeShippingId');
    }

    if (result.shippingAddress) {
      userData = userData || {};
      userData.s_country = result.shippingAddress.country;
      userData.s_state = result.shippingAddress.region;
      userData.s_city = result.shippingAddress.city;
      userData.s_zipcode = result.shippingAddress.postalCode;
      userData.s_address = result.shippingAddress.addressLine[0];
    }

    if (result.payerName) {
      userData = userData || {};
      var name = result.payerName.split(' '),
          lastname = name.pop(),
          firstname = name.join(' ');

      if (!firstname) {
        var _ref = [lastname, firstname];
        firstname = _ref[0];
        lastname = _ref[1];
      }

      userData.firstname = userData.s_firstname = firstname;
      userData.lastname = userData.s_lastname = lastname;
    }

    if (result.payerEmail) {
      userData = userData || {};
      userData.email = result.payerEmail;
    }

    if (result.payerPhone) {
      userData = userData || {};
      userData.phone = userData.s_phone = result.payerPhone;
    }

    if (paymentIntentId) {
      paymentInfo = {
        'stripe.payment_intent_id': paymentIntentId
      };
    }

    return {
      products: [{
        product_id: productId,
        product_options: productOptions
      }],
      user_data: userData ? userData : null,
      shipping_ids: shippingId ? [shippingId] : null,
      payment_id: paymentId,
      payment_info: paymentInfo ? paymentInfo : null,
      redirect_mode: 'checkout',
      redirect_url: _.current_url
    };
  }

  function placeOrderAndCheckConfirmation(stripeInstance, paymentIntentResult, $paymentButton) {
    $.ceAjax('request', fn_url('checkout.place_order.instant_payment'), {
      method: 'post',
      data: getPaymentRequestData(paymentIntentResult, $paymentButton),
      hidden: false,
      caching: false,
      callback: function callback(response) {
        if (response.error) {
          $.ceNotification('show', {
            type: 'E',
            title: _.tr('error'),
            message: response.error.message
          });
          return;
        }

        for (var i in response.notifications) {
          if (response.notifications[i].type === 'N') {
            delete response.notifications[i];
          }
        }

        if (response.order_id) {
          paymentIntentResult.orderId = response.order_id;
        }

        paymentIntentResult.redirectUrl = response.current_url;
        checkConfirmation(stripeInstance, paymentIntentResult, $paymentButton);
      }
    });
  }

  function checkConfirmation(stripeInstance, paymentIntentResult, $paymentButton) {
    $.ceAjax('request', $paymentButton.data('caStripeConfirmationUrl'), {
      method: 'post',
      hidden: false,
      caching: false,
      data: {
        total: $paymentButton.data('caStripeTotalRaw'),
        payment_id: $paymentButton.data('caStripePaymentId'),
        payment_intent_id: paymentIntentResult.paymentMethod.id,
        email: paymentIntentResult.payerEmail,
        order_id: paymentIntentResult.orderId
      },
      callback: function callback(response) {
        if (response.error) {
          $.ceNotification('show', {
            type: 'E',
            title: _.tr('error'),
            message: response.error.message
          });
          return;
        }

        if (response.requires_confirmation) {
          requireConfirmation(stripeInstance, response.client_secret, paymentIntentResult, $paymentButton);
        } else {
          confirmPaymentIntent(paymentIntentResult, $paymentButton);
        }
      }
    });
  }

  function requireConfirmation(stripeInstance, paymentIntentClientSecret, paymentIntentResult, $paymentButton) {
    stripeInstance.handleCardAction(paymentIntentClientSecret).then(function (result) {
      if (result.error) {
        $.ceNotification('show', {
          type: 'E',
          title: _.tr('error'),
          message: result.error.message
        });
        return;
      }

      confirmPaymentIntent(paymentIntentResult, $paymentButton);
    });
  }

  function confirmPaymentIntent(paymentMethodResult, $paymentButton) {
    $.ceAjax('request', $paymentButton.data('caStripeConfirmUrl'), {
      method: 'post',
      hidden: false,
      caching: false,
      data: {
        payment_id: $paymentButton.data('caStripePaymentId'),
        order_id: paymentMethodResult.orderId
      },
      callback: function callback(response) {
        if (response.error) {
          $.ceNotification('show', {
            type: 'E',
            title: _.tr('error'),
            message: response.error.message
          });
          return;
        }

        $.redirect(paymentMethodResult.redirectUrl);
      }
    });
  }

  function updatePaymentRequest(result, response, $paymentButton) {
    // if any error occured, payment request must fail
    if (!response.stripe_payment_buttons) {
      result.updateWith({
        status: 'fail'
      });
      return;
    }

    var paymentId = $paymentButton.data('caStripePaymentId'),
        btn = response.stripe_payment_buttons[paymentId];
    result.updateWith({
      status: 'success',
      total: {
        amount: btn.total,
        label: $paymentButton.data('caStripePaymentLabel'),
        pending: true
      },
      displayItems: btn.display_items,
      shippingOptions: btn.shipping_options
    });
  }

  function setupPaymentButton(stripeInstance, $paymentButton, isLastPaymentButton) {
    var paymentRequest = new stripeInstance.paymentRequest({
      country: $paymentButton.data('caStripeCountry').toUpperCase(),
      currency: $paymentButton.data('caStripeCurrency').toLowerCase(),
      total: {
        amount: $paymentButton.data('caStripeTotal'),
        label: $paymentButton.data('caStripePaymentLabel'),
        pending: true
      },
      requestPayerName: true,
      requestPayerEmail: true,
      requestPayerPhone: true,
      requestShipping: true,
      displayItems: $paymentButton.data('caStripeDisplayItems'),
      shippingOptions: $paymentButton.data('caStripeShippingOptions')
    });
    paymentRequest.on('shippingaddresschange', function (result) {
      $.ceAjax('request', fn_url('checkout.customer_info.instant_payment'), {
        method: 'post',
        data: getPaymentRequestData(result, $paymentButton),
        hidden: true,
        caching: false,
        callback: function callback(response) {
          updatePaymentRequest(result, response, $paymentButton);
        }
      });
    });
    paymentRequest.on('shippingoptionchange', function (result) {
      $.ceAjax('request', fn_url('checkout.update_shipping.instant_payment'), {
        method: 'post',
        data: getPaymentRequestData(result, $paymentButton),
        hidden: true,
        caching: false,
        callback: function callback(response) {
          updatePaymentRequest(result, response, $paymentButton);
        }
      });
    });
    paymentRequest.on('paymentmethod', function (result) {
      if (result.error) {
        result.complete('fail');
        $.ceNotification('show', {
          type: 'E',
          title: _.tr('error'),
          message: result.error.message
        });
        return;
      }

      result.complete('success');
      placeOrderAndCheckConfirmation(stripeInstance, result, $paymentButton);
    });
    paymentRequest.canMakePayment().then(function (result) {
      var groupId;

      if (result && result.applePay && $paymentButton.data('caStripePaymentType') === 'apple_pay') {
        $paymentButton.removeClass('hidden');
        groupId = $paymentButton.data('caStripeButtonGroupId');
        $('[data-ca-stripe-test-mode-notification-group-id="' + groupId + '"]').removeClass('hidden');
        hasShownButtons = true;
      }

      if (result && !result.applePay && $paymentButton.data('caStripePaymentType') === 'google_pay') {
        $paymentButton.removeClass('hidden');
        groupId = $paymentButton.data('caStripeButtonGroupId');
        $('[data-ca-stripe-test-mode-notification-group-id="' + groupId + '"]').removeClass('hidden');
        hasShownButtons = true;
      }

      if (isLastPaymentButton) {
        $.ceEvent('trigger', 'ce.stripe.instant_payment.loaded', [hasShownButtons]);
      }
    });
    $paymentButton.unbind('click');
    $paymentButton.on('click', function (e) {
      e.preventDefault();

      if ($paymentButton.closest('form').ceFormValidator('checkFields', false)) {
        paymentRequest.show();
      }
    });
  }

  function initializationPaymentButtons() {
    var stripeInstances = {};
    var $buttons = $('[data-ca-stripe-element="instantPaymentButton"]');
    var paymentButtonsCount = $buttons.length;

    if (!paymentButtonsCount) {
      return;
    }

    $buttons.each(function (i, button) {
      var $paymentButton = $(button),
          publishableKey = $paymentButton.data('caStripePublishableKey'),
          isLastPaymentButton = i === paymentButtonsCount - 1;

      if ($paymentButton.hasClass('hidden') && publishableKey) {
        if (typeof Stripe === "undefined") {
          $.getScript('https://js.stripe.com/v3/', function () {
            init(stripeInstances, publishableKey, $paymentButton, isLastPaymentButton);
          });
        } else {
          init(stripeInstances, publishableKey, $paymentButton, isLastPaymentButton);
        }
      }
    });
  }

  initializationPaymentButtons();
  $.ceEvent('on', 'ce.product_option_changed_post', function (productId, variantId, optionId, updateIds) {
    /**
     * New option values are delivered in the ajax response when changing an option that affects the product price.
     * Manual processing is required only when product has no options that affect price.
     */
    if (updateIds.length) {
      return;
    }

    var $btn = $('[data-ca-stripe-product-id="' + productId + '"]');

    if (!$btn.length) {
      return;
    }

    var options = $btn.data('caStripeProductOptions');
    options[optionId] = parseInt(variantId);
    $btn.data('caStripeProductOptions', options);
  });
  $.ceEvent('on', 'ce.commoninit', function (context) {
    initializationPaymentButtons();
  });
})(Tygh, Tygh.$);