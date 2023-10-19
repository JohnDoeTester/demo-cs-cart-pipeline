(function (_, $) {
  var isValid = true;
  $.ceEvent('on', 'ce.commoninit', function (context) {
    var $formula = $('[data-ca-vendor-rating-formula-input]', context),
        $controlGroup = $('[data-ca-vendor-rating-formula]', context),
        $error = $('[data-ca-vendor-rating-formula-error]', $controlGroup);

    if (!$formula.length) {
      return;
    }

    $.ceFormValidator('registerValidator', {
      class_name: $formula.data('caVendorRatingLabelClass'),
      message: $formula.data('caVendorRatingFormulaErrorMessage'),
      'func': function func(id) {
        return isValid;
      }
    });
    var validationCallback = $.debounce(function () {
      isValid = false;
      $.ceAjax('request', fn_url('vendor_rating.validate_formula'), {
        method: 'post',
        hidden: true,
        caching: false,
        data: {
          formula: $formula.val()
        },
        callback: function callback(response) {
          response.is_valid = response.is_valid || false;
          response.error_message = response.error_message || '';
          isValid = response.is_valid;
          $formula.toggleClass('cm-failed-field', !response.is_valid);
          $controlGroup.toggleClass('error', !response.is_valid);
          $('.help-inline:not([data-ca-vendor-rating-formula-error])', $controlGroup).remove();
          $error.html(response.error_message);
        }
      });
    }, 400);
    $formula.on('input', validationCallback);
  });
})(Tygh, Tygh.$);