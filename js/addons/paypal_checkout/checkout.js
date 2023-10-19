(function (_, $) {
  var isCheckoutScriptLoaded, validationLoop, isPlaceOrderAllowed, orderId;
  var methods = {
    /**
     * Changes default 'Submit my order' button ID.
     * Submit button ID must be altered to prevent 'button_already_has_paypal_click_listener' warning.
     *
     * @param {string} buttonId Button ID
     * @returns {string} New button ID
     */
    setSubmitButtonId: function setSubmitButtonId(buttonId) {
      var newButtonId = buttonId + '_' + Date.now();
      var $button = $('#' + buttonId);
      $button.attr('id', newButtonId);
      return newButtonId;
    },

    /**
     * Provides request to place an order.
     *
     * @param {jQuery} $paymentForm
     * @returns {{redirect_on_charge: string, is_ajax: number}}
     */
    getOrderPlacementRequest: function getOrderPlacementRequest($paymentForm) {
      var formData = {
        is_ajax: 1,
        custom_paypal_button: 1
      };
      var fields = $paymentForm.serializeArray();

      for (var i in fields) {
        formData[fields[i].name] = fields[i].value;
      }

      formData.result_ids = null;
      return formData;
    },

    /**
     * Renders payment buttons.
     *
     * @param {Object} params Payment form config
     */
    setupPaymentForm: function setupPaymentForm(params) {
      params = params || {};
      params.payment_form = params.payment_form || null;
      params.submit_button_id = params.submit_button_id || '';
      params.style = params.style || {};
      params.style.layout = params.style.layout || 'vertical';
      params.style.color = params.style.color || 'gold';
      params.style.height = params.style.height || 40;
      params.style.shape = params.style.shape || 'rect';
      params.style.label = params.style.label || 'pay';
      params.style.tagline = params.style.tagline || false;
      methods.stopValidation();
      methods.createPaymentButtonsContainer(params.submit_button_id);
      paypal.Buttons({
        style: params.style,
        onInit: function onInit(data, actions) {
          methods.forbidOrderPlacement(actions);
          methods.startValidation(params.payment_form, actions);
        },
        onClick: function onClick(data, actions) {
          params.payment_form.ceFormValidator('checkFields', false);
        },
        createOrder: function createOrder(data, actions) {
          var deferredOrder = $.Deferred();
          orderId = null;
          $.ceAjax('request', fn_url('checkout.place_order'), {
            data: methods.getOrderPlacementRequest(params.payment_form),
            method: 'post',
            hidden: true,
            caching: false,
            callback: function callback(res) {
              if (res.error) {
                deferredOrder.reject(res);
                return;
              }

              if (res.order_id_in_paypal) {
                orderId = res.order_id;
                deferredOrder.resolve(res);
                return;
              }

              deferredOrder.reject({
                error: ''
              });
            }
          });
          return deferredOrder.promise().then(function (success) {
            return success.order_id_in_paypal;
          }, function (fail) {
            new Error(fail.error);
          });
        },
        onApprove: function onApprove(data, actions) {
          $.toggleStatusBox('show');
          var redirectUrl = fn_url('payment_notification.return' + '?order_id=' + orderId + '&order_id_in_paypal=' + data.orderID + '&payment=paypal_checkout');
          actions.redirect(redirectUrl);
        }
      }).render('#' + params.submit_button_id + '_container').catch(function () {});
    },

    /**
     * Gets PayPal Smart Buttons script load options.
     *
     * @param $payment
     * @returns {{clientId: string, debug: boolean, disableFunding: string, currency: string}}
     */
    getSmartButtonsLoadOptions: function getSmartButtonsLoadOptions($payment) {
      return {
        clientId: $payment.data('caPaypalCheckoutClientId'),
        currency: $payment.data('caPaypalCheckoutCurrency'),
        disableFunding: $payment.data('caPaypalCheckoutDisableFunding'),
        debug: $payment.data('caPaypalCheckoutDebug')
      };
    },

    /**
     * Gets URL to load the customized PayPal Smart Buttons script.
     * @param {object} options
     * @returns {string}
     */
    getSmartButtonsLoadUrl: function getSmartButtonsLoadUrl(options) {
      var url = 'https://www.paypal.com/sdk/js' + '?client-id=' + options.clientId + '&components=buttons' + '&currency=' + options.currency + '&debug=' + (options.debug ? 'true' : 'false') + '&intent=capture' + '&commit=true' + '&integration-date=2022-10-10';

      if (options.disableFunding) {
        url += '&disable-funding=' + options.disableFunding;
      }

      return url;
    },

    /**
     * Initializes payment form.
     *
     * @param {jQuery} $payment Payment method
     */
    init: function init($payment) {
      var $payment_form = $payment.closest('form');
      var submitButtonId = methods.setSubmitButtonId($payment.data('caPaypalCheckoutButton')),
          $submitButton = $('#' + submitButtonId);
      $submitButton.addClass('hidden');

      var checkoutScriptLoadCallback = function checkoutScriptLoadCallback() {
        isCheckoutScriptLoaded = true;
        methods.setupWindowClosedErrorHandler(window);
        methods.setupPaymentForm({
          payment_form: $payment_form,
          submit_button_id: submitButtonId,
          style: {
            layout: $payment.data('caPaypalCheckoutStyleLayout'),
            color: $payment.data('caPaypalCheckoutStyleColor'),
            height: $payment.data('caPaypalCheckoutStyleHeight'),
            shape: $payment.data('caPaypalCheckoutStyleShape'),
            label: $payment.data('caPaypalCheckoutStyleLabel'),
            tagline: $payment.data('caPaypalCheckoutStyleTagline')
          }
        });
      };

      if (isCheckoutScriptLoaded) {
        checkoutScriptLoadCallback();
      } else {
        var options = methods.getSmartButtonsLoadOptions($payment),
            url = methods.getSmartButtonsLoadUrl(options);
        methods.loadScript(url, checkoutScriptLoadCallback);
      }
    },

    /**
     * Forbids order placement (e.g., due to the validation)
     *
     * @param {object} actions
     */
    forbidOrderPlacement: function forbidOrderPlacement(actions) {
      isPlaceOrderAllowed = false;
      actions.disable();
    },

    /**
     * Allows order placement.
     *
     * @param {object} actions
     */
    allowOrderPlacement: function allowOrderPlacement(actions) {
      isPlaceOrderAllowed = true;
      actions.enable();
    },

    /**
     * Runs validation loop on the order placement fom.
     *
     * @param {jQuery} $paymentForm
     * @param {object} actions
     */
    startValidation: function startValidation($paymentForm, actions) {
      validationLoop = setInterval(function () {
        var formIsValid = $paymentForm.ceFormValidator('checkFields', true);

        if (formIsValid && !isPlaceOrderAllowed) {
          methods.allowOrderPlacement(actions);
        } else if (!formIsValid && isPlaceOrderAllowed) {
          methods.forbidOrderPlacement(actions);
        }
      }, 300);
    },

    /**
     * Stops validation on the order placement form.
     */
    stopValidation: function stopValidation() {
      if (validationLoop) {
        clearInterval(validationLoop);
      }
    },

    /**
     * Creates container for PayPal Smart Buttons.
     *
     * @param {string} submitButtonId
     */
    createPaymentButtonsContainer: function createPaymentButtonsContainer(submitButtonId) {
      $('<div class="ty-paypal-checkout-buttons-container" id="' + submitButtonId + '_container"></div>').insertAfter($('#' + submitButtonId));
    },

    /**
     * Sets up global error handler to work around the following issue:
     * https://github.com/paypal/paypal-checkout-components/issues/1107.
     *
     * @param {window} window
     */
    setupWindowClosedErrorHandler: function setupWindowClosedErrorHandler(window) {
      // Window closed
      window.onerror = function (message, source, lineno, colno, error) {
        console.log(message, source, lineno, colno, error);
      };
    },

    /**
     * Loads Smart Payment Buttons script.
     *
     * @param {string}   url                        Script URL
     * @param {callback} checkoutScriptLoadCallback Action to execute after script is loaded
     */
    loadScript: function loadScript(url, checkoutScriptLoadCallback) {
      var checkoutScript = _.doc.createElement('script');

      checkoutScript.setAttribute('src', url);
      checkoutScript.onload = checkoutScriptLoadCallback;

      _.doc.head.appendChild(checkoutScript);
    }
  };
  $.extend({
    cePaypalCheckout: function cePaypalCheckout(method) {
      if (methods[method]) {
        return methods[method].apply(this, Array.prototype.slice.call(arguments, 1));
      } else {
        $.error('ty.paypalCheckout: method ' + method + ' does not exist');
      }
    }
  });
  $.ceEvent('on', 'ce.commoninit', function (context) {
    if (_.embedded) {
      return;
    }

    var isCheckoutButtonLoaded = !!$('[name="dispatch[checkout.place_order]"]', context).length;

    if (!isCheckoutButtonLoaded) {
      return;
    }

    var $payment = $('[data-ca-paypal-checkout]');

    if (!$payment.length) {
      return;
    }

    $.cePaypalCheckout('init', $payment);
  });
})(Tygh, Tygh.$);