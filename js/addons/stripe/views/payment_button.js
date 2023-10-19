(function (_, $) {
  var canMakePayment = false;

  function showUnsupportedPaymentMethodMessage($paymentButton) {
    var $message = $($paymentButton.data('caStripeUnsupportedPayment'));
    $message.removeClass('hidden');
  }

  _.stripe = _.stripe || {};
  _.stripe.view = {
    id: 'payment_button',
    name: 'Payment Button',
    render: function render(stripeInstance, stripeElementsApi, stripeElements, elements) {
      var $paymentButton = elements.paymentButton,
          $form = elements.form,
          $submitButton = $('[type="submit"]', $form);
      $submitButton.addClass('hidden');
      _.stripe.paymentRequest = new stripeInstance.paymentRequest({
        country: $paymentButton.data('caStripeCountry').toUpperCase(),
        currency: $paymentButton.data('caStripeCurrency').toLowerCase(),
        total: {
          amount: $paymentButton.data('caStripeTotal'),
          label: $paymentButton.data('caStripePaymentLabel')
        },
        requestPayerName: false,
        requestPayerEmail: false,
        requestPayerPhone: false,
        requestShipping: false
      });

      _.stripe.paymentRequest.canMakePayment().then(function (result) {
        if (_.stripe.payment.canMakePayment(result)) {
          canMakePayment = true;
          $submitButton.removeClass('hidden');
          return;
        }

        showUnsupportedPaymentMethodMessage($paymentButton);
      });
    },
    placeOrderAndCheckConfirmation: function placeOrderAndCheckConfirmation(stripeInstance, result, $paymentIntentId, $form) {
      var data = $form.serializeArray().reduce(function (data, _ref) {
        var name = _ref.name,
            value = _ref.value;
        data[name] = value;
        return data;
      }, {
        is_ajax: 1
      });
      $.ceAjax('request', fn_url('checkout.place_order'), {
        method: 'post',
        data: data,
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

          if (!response.order_id) {
            $submitBtn.prop('disabled', null);
            $.ceNotification('show', {
              type: 'E',
              title: _.tr('error'),
              message: _.tr('order_was_not_placed')
            });
            return;
          }

          $paymentIntentId.data('caStripeOrderId', response.order_id);
          $paymentIntentId.data('caStripeRedirectUrl', response.current_url);

          _.stripe.view.checkConfirmation(stripeInstance, result, $paymentIntentId, $form);
        }
      });
    },
    checkConfirmation: function checkConfirmation(stripeInstance, paymentIntentResult, $paymentIntentId, $form) {
      $.ceAjax('request', $paymentIntentId.data('caStripeConfirmationUrl'), {
        method: 'post',
        hidden: false,
        caching: false,
        data: {
          order_id: $paymentIntentId.data('caStripeOrderId'),
          payment_id: $paymentIntentId.data('caStripePaymentId'),
          payment_intent_id: paymentIntentResult.paymentMethod.id
        },
        callback: function callback(response) {
          $.toggleStatusBox('hide');

          if (response.error) {
            $.ceNotification('show', {
              type: 'E',
              title: _.tr('error'),
              message: response.error.message
            });
            return;
          }

          if (response.requires_confirmation) {
            _.stripe.view.requireConfirmation(stripeInstance, response.client_secret, $paymentIntentId, $form);
          } else {
            _.stripe.view.confirmPaymentIntent(response.payment_intent_id, $paymentIntentId, $form);
          }
        }
      });
    },
    confirmPaymentIntent: function confirmPaymentIntent(paymentIntentId, $paymentIntentId, $form) {
      $.toggleStatusBox('hide');
      $paymentIntentId.val(paymentIntentId);

      if ($paymentIntentId.data('caStripeRedirectUrl')) {
        $.ceAjax('request', $paymentIntentId.data('caStripeConfirmUrl'), {
          method: 'post',
          hidden: false,
          caching: false,
          data: {
            payment_id: $paymentIntentId.data('caStripePaymentId'),
            order_id: $paymentIntentId.data('caStripeOrderId')
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

            $.redirect($paymentIntentId.data('caStripeRedirectUrl'));
          }
        });
        return;
      }

      setTimeout(function () {
        var $submitBtn = $form.find(':submit');

        if ($submitBtn.length) {
          $form.append('<input type="hidden" name="' + $submitBtn.prop('name') + '" />');
        }

        $form.submit();
      }, 0);
    },
    requireConfirmation: function requireConfirmation(stripeInstance, paymentIntentClientSecret, $paymentIntentId, $form) {
      stripeInstance.handleCardAction(paymentIntentClientSecret).then(function (result) {
        if (result.error) {
          $.ceNotification('show', {
            type: 'E',
            title: _.tr('error'),
            message: result.error.message
          });
          return;
        }

        _.stripe.view.confirmPaymentIntent(result.paymentIntent.id, $paymentIntentId, $form);
      });
    },
    addSubmitHandler: function addSubmitHandler(stripeInstance, stripeElementsApi, stripeElements, elements) {
      var $form = elements.form,
          $paymentIntentId = elements.paymentIntentId,
          $submitButton = $('[type="submit"]', $form);
      $form.data('caStripePaymentId', $paymentIntentId.data('caStripePaymentId')); // payment request must be shown within the click handler

      $submitButton.on('click', function (e) {
        if ($form.ceFormValidator('check')) {
          e.preventDefault();

          if (canMakePayment) {
            _.stripe.paymentRequest.show();
          }
        }
      });

      _.stripe.paymentRequest.on('paymentmethod', function (result) {
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

        _.stripe.view.placeOrderAndCheckConfirmation(stripeInstance, result, $paymentIntentId, $form);
      });

      $.ceEvent('on', 'ce.formpost_' + $form.prop('name'), function ($form, $submitBtn) {
        var elements = _.stripe.view.getElements($form),
            $paymentIntentId = elements.paymentIntentId;

        var isSubmit = !$submitBtn.hasClass('cm-skip-validation') && $paymentIntentId.length !== 0;
        var isValidPayment = $paymentIntentId.data('caStripePaymentId') === $form.data('caStripePaymentId');
        var isSubmitted = $paymentIntentId.data('caStripeIsSubmitted');

        if (!isSubmit || !isValidPayment || isSubmitted) {
          return true;
        }

        $paymentIntentId.data('caStripeIsSubmitted', true);
      });
      $.ceEvent('on', 'ce.ajaxdone', function (elms, scripts, params, responseData, responseText) {
        if (responseData.has_errors) {
          $paymentIntentId.data('caStripeIsSubmitted', false);
        }
      });
      $paymentIntentId.data('caStripeIsFormReady', true);
    },
    isInitialized: function isInitialized(elements) {
      return elements.paymentIntentId && elements.paymentIntentId.data('caStripeIsFormReady');
    },
    teardown: function teardown(stripeElements) {
      stripeElements.paymentButton && stripeElements.paymentButton.destroy();
    },
    getElements: function getElements($form) {
      return {
        paymentButton: $('[data-ca-stripe-element="paymentButton"]', $form),
        paymentIntentId: $('[data-ca-stripe-element="paymentIntentId"]', $form)
      };
    }
  };
})(Tygh, Tygh.$);