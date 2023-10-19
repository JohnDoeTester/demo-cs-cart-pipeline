(function (_, $) {
  $.ceEvent('on', 'ce.commoninit', function (context) {
    var $selector = $('[data-ca-paypal-commerce-platform-element="currency"]');

    if ($selector.length) {
      var $credit = $($selector.data('caPaypalCommercePlatformCreditSelector'));
      $selector.on('change', function () {
        if ($selector.val() !== 'USD') {
          $credit.prop('disabled', 'disabled');
          $credit.removeProp('checked');
        } else {
          $credit.removeProp('disabled');
        }
      });
      $selector.trigger('change');
    }
  });
})(Tygh, Tygh.$);