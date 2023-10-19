(function (_, $) {
  var $stripeInstantPayment = $('[data-ca-stripe="stripe"]');

  if (!$stripeInstantPayment.length || typeof $stripeInstantPayment.data() === 'undefined' || !$stripeInstantPayment.data('caStripeSrc')) {
    return;
  }

  $.ceLazyLoader({
    src: $stripeInstantPayment.data('caStripeSrc'),
    event_suffix: 'stripe'
  });
})(Tygh, Tygh.$);