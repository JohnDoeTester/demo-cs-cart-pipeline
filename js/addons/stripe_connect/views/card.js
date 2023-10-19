(function (_, $) {
  function isFormValid($form) {
    var isValid = true;
    $('.sc-field', $form).each(function () {
      var $checkoutField = $(this);
      var isFieldComplete = $checkoutField.hasClass('sc-field--complete');
      $checkoutField.closest('.ty-control-group').toggleClass('error', !isFieldComplete).find('.cm-required').toggleClass('cm-failed-label', !isFieldComplete);
      isValid = isValid && isFieldComplete;
    });
    return isValid;
  }

  _.stripe = _.stripe || {};
  _.stripe.view = {
    id: 'card',
    name: 'Credit Card',
    render: function render(stripeInstance, stripeElementsApi, stripeElements, elements) {
      var $card = elements.card,
          $expiryDate = elements.expiry,
          $cvc = elements.cvc,
          $postalCode = elements.postalCode;
      var options = {
        classes: {
          base: 'sc-field',
          complete: 'sc-field--complete',
          empty: 'sc-field--empty',
          focus: 'sc-field--focus',
          invalid: 'sc-field--invalid',
          webkitAutofill: 'sc-field--autofill'
        },
        style: {
          base: {
            fontSize: '18px',
            color: '#2e3a47'
          },
          invalid: {
            color: '#bf4d4d'
          }
        }
      };
      stripeElements.card = stripeElementsApi.create('cardNumber', options);
      stripeElements.card.mount($card[0]);
      stripeElements.expiry = stripeElementsApi.create('cardExpiry', options);
      stripeElements.expiry.mount($expiryDate[0]);
      stripeElements.cvc = stripeElementsApi.create('cardCvc', options);
      stripeElements.cvc.mount($cvc[0]);

      if ($postalCode[0].dataset.caStripeElementValue !== undefined) {
        options.value = $postalCode[0].dataset.caStripeElementValue;
      }

      stripeElements.postalCode = stripeElementsApi.create('postalCode', options);
      stripeElements.postalCode.mount($postalCode[0]);
      return stripeElements;
    },
    placeOrderAndCheckConfirmation: function placeOrderAndCheckConfirmation(stripeInstance, result, $paymentIntentId, $submitBtn, $form) {
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

          if (response.order_id) {
            $paymentIntentId.data('caStripeOrderId', response.order_id);
          }

          $paymentIntentId.data('caStripeRedirectUrl', response.current_url);

          _.stripe.view.checkConfirmation(stripeInstance, result, $paymentIntentId, $submitBtn);
        }
      });
    },
    checkConfirmation: function checkConfirmation(stripeInstance, paymentIntentResult, $paymentIntentId, $submitBtn) {
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
            $submitBtn.prop('disabled', null);
            $.ceNotification('show', {
              type: 'E',
              title: _.tr('error'),
              message: response.error.message
            });
            return;
          }

          if (response.requires_confirmation) {
            _.stripe.view.requireConfirmation(stripeInstance, response.client_secret, $paymentIntentId, $submitBtn);
          } else {
            _.stripe.view.confirmPaymentIntent(response.payment_intent_id, $paymentIntentId, $submitBtn);
          }
        }
      });
    },
    confirmPaymentIntent: function confirmPaymentIntent(paymentIntentId, $paymentIntentId, $submitBtn) {
      $paymentIntentId.val(paymentIntentId);
      $paymentIntentId.data('caStripeIsSubmitted', true);
      $submitBtn.prop('disabled', null);

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
        $submitBtn.trigger('click');
      }, 0);
    },
    requireConfirmation: function requireConfirmation(stripeInstance, paymentIntentClientSecret, $paymentIntentId, $submitBtn) {
      stripeInstance.handleCardAction(paymentIntentClientSecret).then(function (result) {
        if (result.error) {
          $submitBtn.prop('disabled', null);
          $.ceNotification('show', {
            type: 'E',
            title: _.tr('error'),
            message: result.error.message
          });
          return;
        }

        _.stripe.view.confirmPaymentIntent(result.paymentIntent.id, $paymentIntentId, $submitBtn);
      });
    },
    addSubmitHandler: function addSubmitHandler(stripeInstance, stripeElementsApi, stripeElements, elements) {
      var $form = elements.form,
          $paymentIntentId = elements.paymentIntentId,
          paymentId = $paymentIntentId.data('caStripePaymentId');
      $.ceEvent('on', 'ce.formpost_' + $form.prop('name'), function ($form, $submitBtn) {
        var elements = _.stripe.view.getElements($form),
            $paymentIntentId = elements.paymentIntentId,
            $name = elements.name;

        var isSubmit = !$submitBtn.hasClass('cm-skip-validation') && $paymentIntentId.length !== 0;
        var isValidPayment = $paymentIntentId.data('caStripePaymentId') === paymentId;
        var isPaymentSkipped = $paymentIntentId.data('caStripeProcessPaymentName') && $submitBtn.prop('name') !== $paymentIntentId.data('caStripeProcessPaymentName');
        var isSubmitted = $paymentIntentId.data('caStripeIsSubmitted');

        if (!isSubmit || !isValidPayment || isPaymentSkipped || isSubmitted) {
          return true;
        }

        $submitBtn.prop('disabled', 'disabled');

        if (isFormValid($form)) {
          stripeInstance.createPaymentMethod('card', stripeElements.card, {
            billing_details: {
              name: $name.val()
            }
          }).then(function (result) {
            if (result.error) {
              $.toggleStatusBox('hide');
              $submitBtn.prop('disabled', null);
              $.ceNotification('show', {
                type: 'E',
                title: _.tr('error'),
                message: result.error.message
              });
              return;
            }

            _.stripe.view.placeOrderAndCheckConfirmation(stripeInstance, result, $paymentIntentId, $submitBtn, $form);
          });
        } else {
          $.toggleStatusBox('hide');
          $submitBtn.prop('disabled', null);
        }

        return false;
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
      stripeElements.card && stripeElements.card.destroy();
      stripeElements.expiry && stripeElements.expiry.destroy();
      stripeElements.cvc && stripeElements.cvc.destroy();
      stripeElements.postalCode && stripeElements.postalCode.destroy();
    },
    getElements: function getElements($form) {
      return {
        card: $('[data-ca-stripe-element="card"]', $form),
        expiry: $('[data-ca-stripe-element="expiry"]', $form),
        cvc: $('[data-ca-stripe-element="cvc"]', $form),
        name: $('[data-ca-stripe-element="name"]', $form),
        paymentIntentId: $('[data-ca-stripe-element="paymentIntentId"]', $form),
        postalCode: $('[data-ca-stripe-element="postal_code"]', $form)
      };
    }
  };
})(Tygh, Tygh.$);