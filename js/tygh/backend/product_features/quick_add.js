(function (_, $) {
  $.ceEvent('on', 'ce.commoninit', function (context) {
    var $blockElem = $('[data-ca-features-create-elem="block"]', context);

    if (!$blockElem.length) {
      return;
    }

    $blockElem.each(function () {
      initQuickAddForm($(this));
    });
  });

  function initQuickAddForm($block) {
    var $blockVariants = $block.find($block.data('caFeaturesCreateVariantsSelector')),
        $variantsData = $block.find($block.data('caFeaturesCreateVariantsDataSelector')),
        requestForm = $block.data('caFeaturesCreateRequestForm'),
        blockVariantsSelected = 0; // Create hidden input for submit form when creating variants

    $blockVariants.on('ce:object_picker:object_selected', function (event, objectPicker, selected, event2) {
      var $input = $('<input>').attr({
        type: 'hidden',
        name: 'feature_data[variants][' + blockVariantsSelected++ + '][variant]',
        value: selected.text,
        form: requestForm
      });
      $input.appendTo($variantsData);
      selected.data.$input = $input;
    }); // Delete hidden input for submit form when deleting variants

    $blockVariants.on('ce:object_picker:object_unselected', function (event, objectPicker, selected, event2) {
      if (selected.data.$input) {
        selected.data.$input.remove();
      }
    }); // Checking the tab for changes before reloading 

    $.ceEvent('on', "ce.formpost_".concat(requestForm), function (frm, elm) {
      var $formContainer = $("[data-ca-features-create-request-form=\"".concat(requestForm, "\"]")).closest('#product_features_quick_add_feature'),
          target_id = $formContainer.data('caTargetId'),
          changed = false,
          confirmText = $block.data('caFeaturesCreateConfirmText');
      $(':input:visible,.cm-wysiwyg,.cm-object-picker', "#".concat(target_id)).each(function () {
        changed = $(this).fieldIsChanged();
        return !changed;
      });

      if (changed && !confirm(confirmText)) {
        return false;
      }

      return true;
    });
  }
})(Tygh, Tygh.$);