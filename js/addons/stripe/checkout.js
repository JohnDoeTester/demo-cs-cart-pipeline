(function (_, $) {
  var stripeInstance;
  var stripeElementsApi;
  var stripeElements;
  var self = {
    init: function init(publishableKey, langCode, elements) {
      if (_.stripe.view.isInitialized(elements)) {
        return;
      }

      stripeElements = stripeElements || {};
      stripeInstance = stripeInstance || Stripe(publishableKey);
      stripeElementsApi = stripeElementsApi || stripeInstance.elements({
        locale: langCode
      }); // remove previously rendered form

      _.stripe.view.teardown(stripeElements); // render payment form


      _.stripe.view.render(stripeInstance, stripeElementsApi, stripeElements, elements); // add submit logic


      _.stripe.view.addSubmitHandler(stripeInstance, stripeElementsApi, stripeElements, elements);
    }
  };
  $.extend({
    ceStripeCheckout: function ceStripeCheckout(method) {
      if (self[method]) {
        return self[method].apply(this, Array.prototype.slice.call(arguments, 1));
      } else {
        $.error('ty.stripeCheckout: method ' + method + ' does not exist');
      }
    }
  });
  $.ceEvent('on', 'ce.commoninit', function (context) {
    var $form = $('[data-ca-stripe-element="form"]', context);

    if (!$form.length) {
      return;
    }

    var publishableKey = $form.data('caStripePublishableKey');

    var elements = _.stripe.view.getElements($form);

    elements.form = $form.closest('form');

    if (publishableKey) {
      if (typeof Stripe === "undefined") {
        $.getScript('https://js.stripe.com/v3/', function () {
          $.ceStripeCheckout('init', publishableKey, _.cart_language, elements);
        });
      } else {
        $.ceStripeCheckout('init', publishableKey, _.cart_language, elements);
      }
    }
  });
})(Tygh, Tygh.$);