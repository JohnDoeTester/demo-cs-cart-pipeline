(function (_, $) {
  $.ceEvent('on', 'ce.commoninit', function (context) {
    var $refillBalanceLabel = $('.cm-refill-balance-label', context);

    if (!$refillBalanceLabel.length) {
      return;
    }

    $.ceFormValidator('registerValidator', {
      class_name: 'cm-refill-balance-label',
      message: _.tr('error_refill_amount_lower_than_zero'),
      func: function func(id, elm) {
        return !$.is.blank(elm.val()) && parseFloat(elm.autoNumeric('get')) > 0;
      }
    });
  });
})(Tygh, Tygh.$);