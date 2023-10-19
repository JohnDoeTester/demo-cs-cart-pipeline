(function (_, $) {
  $.ceEvent('on', 'ce.commoninit', function (context) {
    var $generator = $(context).find('.cm-variations-generator');

    if (!$generator.length) {
      return;
    }

    initVariationsGeneratorForm($generator);
  });

  function initVariationsGeneratorForm($generator) {
    var $selectFeaturesElem = $generator.find('.cm-variations-generator__features .cm-object-picker'),
        $featuresContainerElem = $generator.find('.cm-variations-generator__features-variants'),
        $form = $generator.find('form'),
        $quickAddFeature = $generator.find($generator.data('caQuickAddFeatureSelector')),
        conainerId = $generator.data('caContainerId'),
        featuresConainerId = $generator.data('caFeaturesContainerId'),
        buttonsConainerId = $generator.data('caButtonsContainerId'),
        combinationsConainerId = $generator.data('caCombinationsContainerId'),
        selectedFeatureIds = new Set();
    $selectFeaturesElem.on('ce:object_picker:change', function (e, $elem, selected) {
      if (!selected.length) {
        return;
      }

      var featureIds = [];
      selected.forEach(function (item) {
        $featuresContainerElem.append($('<input/>', {
          type: 'hidden',
          name: 'feature_ids[]',
          value: item.id
        }));
        selectedFeatureIds.add(item.id);
      });
      $elem.extendSearchRequestData({
        exclude_feature_ids: Array.from(selectedFeatureIds)
      });
      $elem.setSelectedObjectIds(null);
      reloadVariationsGeneratorForm($form, [featuresConainerId, combinationsConainerId, buttonsConainerId]);
    }); // Open inline dialog when create new feature

    $selectFeaturesElem.on('ce:object_picker:object_selected', function (event, objectPicker, selected) {
      if (!selected.isNew) {
        return;
      }

      $selectFeaturesElem.ceObjectPicker('closeDropdown');
      $quickAddFeature.ceInlineDialog('init', {
        data: {
          feature_data: {
            internal_name: selected.text,
            description: selected.text
          }
        }
      });
    });
    $featuresContainerElem.on('ce:object_picker:change', '.cm-object-picker', function () {
      reloadVariationsGeneratorForm($form, [combinationsConainerId, buttonsConainerId]);
    });
    $featuresContainerElem.on('click', '.cm-variations-generator__delete-feature-variation', function () {
      var $control = $(this).closest('.cm-variations-generator__select-feature-variations');
      $control.find(':input').prop('disabled', true);
      reloadVariationsGeneratorForm($form, [conainerId]);
    });
    $featuresContainerElem.on('click', '.cm-variations-generator_add-all-variants', function () {
      var featureId = $(this).data('ca-feature-id');
      var $selectFeature = $(this).closest('.cm-variations-generator__select-feature-variations');
      var $objectPicker = $('.cm-object-picker', $selectFeature);
      $.ceAjax('request', fn_url('product_features.get_variants_list'), {
        data: {
          feature_id: featureId,
          page_size: 0
        },
        callback: function callback(data) {
          $objectPicker.ceObjectPicker('addObjects', data.objects, true, false);
        }
      });
    });
    $generator.on('change', '.cm-variations-generator__combination-activity-checbox', $.debounce(function () {
      var $row = $(this).closest('.cm-variations-generator__combination-row'),
          combinationId = $row.data('caCombinationId'),
          parentCombinationId = $row.data('caParentCombinationId'),
          isChecked = $(this).prop('checked');

      if (!parentCombinationId) {
        $form.find('.cm-variations-generator__parent-combination-' + combinationId).find('.cm-variations-generator__combination-activity-checbox').prop('checked', isChecked);
      } else if (isChecked) {
        $form.find('.cm-variations-generator__combination-' + parentCombinationId).find('.cm-variations-generator__combination-activity-checbox').prop('checked', isChecked);
      }

      reloadVariationsGeneratorForm($form, [combinationsConainerId, buttonsConainerId]);
    }));
    $generator.on('click', '.cm-variations-generator__combination-set-default-link', function () {
      var $elem = $(this),
          $input = $($elem.data('caInputSelector'));
      $input.prop('disabled', false);
      reloadVariationsGeneratorForm($form, [combinationsConainerId, buttonsConainerId]);
      return false;
    }); // Close inline dialog after feature created and refresh generation dialog

    $.ceEvent('on', 'ce.product_variation_generator.product_feature_save', function (response) {
      if (!response.success) {
        return;
      }

      $quickAddFeature.ceInlineDialog('destroy');
      $featuresContainerElem.append($('<input>', {
        type: 'hidden',
        name: 'feature_ids[]',
        value: response.feature_id
      }));

      if (response.variants) {
        for (var i in response.variants) {
          $featuresContainerElem.append($('<input>', {
            type: 'hidden',
            name: 'features_variants_ids[' + response.feature_id + '][]',
            value: response.variants[i].variant_id
          }));
        }
      }

      reloadVariationsGeneratorForm($form, [featuresConainerId, combinationsConainerId, buttonsConainerId]);
    });
  }
  /**
   * @param {jQuery} $form
   * @param {Array}  targetIds
   */


  function reloadVariationsGeneratorForm($form, targetIds) {
    $.ceAjax('request', fn_url('product_variations.create_variations?is_ajax=1'), {
      data: $form.serializeObject(),
      method: 'post',
      result_ids: targetIds.join(',')
    });
  } // /Delete me

})(Tygh, Tygh.$);