(function (_, $) {
  $.ceEvent('on', 'ce.commoninit', function (context) {
    var $form = $('[data-ca-main-content-selector]', context);

    if (!$form.length) {
      return;
    }

    var products_table = $form.find($form.data('caMainContentSelector'));
    $form.off('submit.disable_unchanged_fields').on('submit.disable_unchanged_fields', function () {
      products_table.find('tbody').find('tr').each(function () {
        var $row_inputs = $(this).find(':input:enabled'),
            row_changed = false;
        $row_inputs.each(function () {
          if ($(this).fieldIsChanged(true) || _actualizeVendorsPrice(this)) {
            row_changed = true;
            return false;
          }
        });

        if (!row_changed) {
          $row_inputs.addClass('js-tmp-disabled').prop('disabled', true);
        }
      }); // Enable fields if the form has not been submitted.

      setTimeout(function () {
        $form.find('.js-tmp-disabled').removeClass('js-tmp-disabled').prop('disabled', false);
      }, 300);
    });
  });

  function _actualizeVendorsPrice(elm) {
    return elm.id.includes('hidden_update_all_vendors_price_') && elm.disabled === false && elm.value === 'Y';
  }
})(Tygh, Tygh.$);