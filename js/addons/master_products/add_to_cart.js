(function (_, $) {
  $.ceEvent('on', 'ce.commoninit', function (context) {
    if ($('[data-ca-master-products-element="product_form"]', context).length) {
      $('[data-ca-master-products-element="product_form"]', context).each(function (i, elm) {
        var $elm = $(elm);
        $.ceEvent('on', 'ce.formpre_' + $elm.prop('name'), function () {
          var masterProductId = $elm.data('caMasterProductsMasterProductId'),
              offerProductId = $elm.data('caMasterProductsProductId'),
              $sellerListContainer = $elm.closest('.js-sellers-list'),
              requestProductId = $sellerListContainer.data('caSellerListRequestProductId') || masterProductId,
              $masterOptionInputs = $('input,select,textarea', '.js-product-options-' + requestProductId),
              $offerOptionsContainer = $('.ty-sellers-list__options', $elm),
              $masterForm = $masterOptionInputs.closest('form');

          if (!$masterOptionInputs.length || !$offerOptionsContainer.length) {
            return;
          }

          if (!$masterForm.ceFormValidator('checkFields', false, '.js-product-options-' + requestProductId)) {
            return false;
          }

          $offerOptionsContainer.empty();
          $masterOptionInputs.each(function (i, elm) {
            var $elm = $(elm);

            if (/^product_data\[\d+\]\[product_options\]\[\d+\]/.test($elm.prop('name')) || /^product_data\[custom_files\]\[\d+\]/.test($elm.prop('name')) || /^type_product_data\[\d+\]/.test($elm.prop('name'))) {
              var $clonedInput = $elm.clone(true);

              if (/^product_data\[custom_files\]\[\d+\]/.test($elm.prop('name'))) {
                $clonedInput.val($elm.val().replace(requestProductId, offerProductId));
              } else {
                $clonedInput.val($elm.val());
              }

              $clonedInput.prop('name', $clonedInput.prop('name').replace('[' + requestProductId + ']', '[' + offerProductId + ']'));
              $offerOptionsContainer.append($clonedInput);
            } else if (/^file_product_data\[\d+\]/.test($elm.prop('name'))) {
              // files must be moved to the vendor product form and replaced with their clone in the original form
              var $clonedFileInput = $elm.clone(),
                  $fileInputContainer = $elm.parent();
              $offerOptionsContainer.append($elm);
              $fileInputContainer.append($clonedFileInput);
            }
          });
        });
      });
    }
  });
  $.ceEvent('on', 'ce.product_option_changed', function (obj_id, id, option_id, update_ids, formData) {
    var $sellerList = $(_.doc).find('.js-sellers-list');

    if ($sellerList.length) {
      formData.push({
        name: 'reload_tabs',
        value: 1
      });
      $sellerList.each(function () {
        update_ids.push($(this).attr('id'));
      });
    }
  });
})(Tygh, Tygh.$);