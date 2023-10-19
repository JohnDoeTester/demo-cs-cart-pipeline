(function (_, $) {
  $.ceEvent('on', 'ce.commoninit', function () {
    var elm_channels_list = $('.pingpp-channels-list');
    var elm_payment = $('.pingpp-payment-container');
    var elm_qr = $('.pingpp-qr-wrapper');
    /**
     * Set device type on payment form to filter payments out
     */

    if (elm_channels_list.length) {
      var scope_class;

      if (navigator.userAgent.match(/micromessenger/i)) {
        scope_class = 'pingpp-scope-wx';
        $('.', elm_channels_list).first().find('input[type="radio"]').prop('checked', true);
      } else if ($.isMobile()) {
        scope_class = 'pingpp-scope-mobile';
      } else {
        scope_class = 'pingpp-scope-pc';
      }

      elm_channels_list.addClass(scope_class);
      $('.' + scope_class, elm_channels_list).first().find('input[type="radio"]').prop('checked', true);
    }

    if (elm_payment.length) {
      /**
       * QR payment
       */
      if (elm_qr.length) {
        var is_running = false;
        var check_payment_status = setInterval(function () {
          if (is_running) {
            return;
          }

          is_running = true;
          $.ceAjax('request', fn_url('payment_notification.check'), {
            method: 'get',
            caching: false,
            hidden: true,
            data: {
              payment: 'pingpp',
              order_id: elm_payment.data('caPingppOrderId')
            },
            callback: function callback(response) {
              is_running = false;

              if (response.current_url) {
                clearInterval(check_payment_status);
                $.redirect(response.current_url);
              }
            }
          });
        }, 5000);
      }
      /**
       * WeChat payment form
       */


      if (pingpp_wx_pay_request) {
        var init_wx_payment = function init_wx_payment() {
          WeixinJSBridge.invoke('getBrandWCPayRequest', pingpp_wx_pay_request, function (res) {
            if (res.err_msg === 'get_brand_wcpay_request:ok') {
              $.redirect(fn_url('payment_notification.notify?payment=pingpp&order_id=' + elm_payment.data('caPingppOrderId')));
            } else {
              $.redirect(fn_url('payment_notification.cancel?payment=pingpp&order_id=' + elm_payment.data('caPingppOrderId')));
            }
          });
        };

        if (typeof WeixinJSBridge === 'undefined') {
          if (document.addEventListener) {
            document.addEventListener('WeixinJSBridgeReady', init_wx_payment, false);
          } else if (document.attachEvent) {
            document.attachEvent('WeixinJSBridgeReady', init_wx_payment);
            document.attachEvent('onWeixinJSBridgeReady', init_wx_payment);
          }
        } else {
          init_wx_payment();
        }
      }
    }
  });
})(Tygh, Tygh.$);