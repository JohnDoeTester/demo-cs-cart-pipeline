(function (_, $) {
  var is_paypal_script_loaded;
  var methods = {
    set_submit_button_id: function set_submit_button_id(button_id) {
      var button_id_new = button_id + '_' + Date.now();
      var button = $('#' + button_id);
      button.attr('id', button_id_new);
      return button_id_new;
    },
    get_token_request: function get_token_request(payment_form) {
      var form_data = {
        in_context_order: 1
      };
      var fields = payment_form.serializeArray();

      for (var i in fields) {
        form_data[fields[i].name] = fields[i].value;
      }

      form_data.result_ids = null;
      return form_data;
    },
    set_window_close_error_handler: function set_window_close_error_handler() {
      window.onerror = function (e) {
        $.redirect(_.current_url);
      };
    },
    setup_payment_form: function setup_payment_form(params) {
      params = params || {};
      params.merchat_id = params.merchat_id || '';
      params.environment = params.environment || 'sandbox';
      params.payment_form = params.payment_form || null;
      params.submit_button_id = params.submit_button_id || '';
      paypal.checkout.setup(params.merchat_id, {
        environment: params.environment,
        buttons: [{
          button: params.submit_button_id,
          condition: function condition() {
            return $.ceLiteCheckout('check', function (result) {
              return result;
            });
          },
          click: function click(e) {
            $.ceEvent('on', 'ce.formpost_' + params.payment_form.prop('name'), function () {
              return false;
            });
            e.preventDefault(); // window has to be inited in 'click' handler to prevent browser pop-up blocking

            paypal.checkout.initXO();
            $.ceLiteCheckout('updateCustomerInfo', function () {
              var form_data = methods.get_token_request($("form[name=\"".concat(params.payment_form.prop('name'), "\"]")));
              $.ceAjax('request', fn_url('checkout.place_order'), {
                method: 'post',
                caching: false,
                hidden: true,
                data: form_data,
                callback: function callback(response) {
                  try {
                    if (response.token) {
                      var url = paypal.checkout.urlPrefix + response.token + '&useraction=commit';
                      paypal.checkout.startFlow(url);
                    }

                    if (response.error) {
                      paypal.checkout.closeFlow();
                    }
                  } catch (ex) {
                    paypal.checkout.initXO();
                  }
                }
              });
            }, false);
          }
        }]
      });
    },
    init: function init(jelm) {
      var payment_form = jelm.closest('form'); // submit button id must be altered to prevent 'button_already_has_paypal_click_listener' warning

      var submit_button_id = methods.set_submit_button_id(jelm.data('caPaypalButton')); // workaround for https://github.com/paypal/paypal-checkout/issues/469

      methods.set_window_close_error_handler();

      var paypal_script_load_callback = function paypal_script_load_callback() {
        is_paypal_script_loaded = true;
        var paypal_presence_checker = setInterval(function () {
          if (typeof paypal !== 'undefined') {
            clearInterval(paypal_presence_checker);
            methods.setup_payment_form({
              merchant_id: jelm.data('caPaypalMerchantId'),
              environment: jelm.data('caPaypalEnvironment'),
              payment_form: payment_form,
              submit_button_id: submit_button_id
            });
          }
        }, 300);
      };

      if (is_paypal_script_loaded) {
        paypal_script_load_callback();
      } else {
        $.getScript('https://www.paypalobjects.com/api/checkout.min.js', paypal_script_load_callback);
      }
    }
  };
  $.extend({
    cePaypalInContextCheckout: function cePaypalInContextCheckout(method) {
      if (methods[method]) {
        return methods[method].apply(this, Array.prototype.slice.call(arguments, 1));
      } else {
        $.error('ty.paypalInContextCheckout: method ' + method + ' does not exist');
      }
    }
  });
  $.ceEvent('on', 'ce.commoninit', function () {
    if (_.embedded) {
      return;
    }

    var jelm = $('[data-ca-paypal-in-context-checkout]');

    if (jelm.length) {
      $.cePaypalInContextCheckout('init', jelm);
    }
  });
})(Tygh, Tygh.$);