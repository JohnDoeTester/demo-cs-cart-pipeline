(function (_, $) {
  var newOptionName = '';
  $.ceEvent('on', 'ce.products_update_options.product_option_save', function (response) {
    if (!response.success) {
      return;
    }

    var $quickAddOptionDialog = $('#product_options_quick_add_option'),
        $productOptionPickerElem = $('.cm-object-picker.object-picker__select--product-options'),
        targetId = $quickAddOptionDialog.data('caTargetId'),
        productId = $quickAddOptionDialog.data('caProductId');
    $productOptionPickerElem.ceObjectPicker('unselectObjectId', newOptionName);
    $quickAddOptionDialog.ceInlineDialog('destroy');
    $.ceAjax('request', fn_url('products.update_option'), {
      method: 'post',
      result_ids: targetId,
      data: {
        product_id: productId,
        option_id: response.option_id
      }
    });
  });
  $(_.doc).on('ce:object_picker:object_selected', '.cm-object-picker.object-picker__select--product-options', function (event, objectPicker, selected) {
    if (!selected.isNew) {
      return;
    }

    newOptionName = selected.text;
    $('#product_options_quick_add_option').ceInlineDialog('init', {
      data: {
        option_data: {
          internal_option_name: selected.text,
          option_name: selected.text
        }
      }
    });
  });
  $(_.doc).on('ce:object_picker:before_create_object', '.cm-object-picker.object-picker__select--product-options', function (event, objectPicker, selected) {
    if ($('.options-create__block').length > 0) {
      selected.enableCreateNewObject = false;
    }
  });
  $(_.doc).on('ce:inline_dialog:closed', '#product_options_quick_add_option', function (object) {
    var $productOptionPickerElem = $('.cm-object-picker.object-picker__select--product-options');

    if ($productOptionPickerElem.length === 0) {
      return;
    }

    $productOptionPickerElem.ceObjectPicker('unselectObjectId', newOptionName);
  });
})(Tygh, Tygh.$);