(function (_, $) {
  $.ceEvent('on', 'ce.tab.show', function (tabId, $tabsElm) {
    var isVariationsTabActive = $('#variations').hasClass('active');
    var isNeedProductSave = $('[name="product_update_form"]').formIsChanged(true);
    var $manageVariationProductsForm = $('.js-manage-variation-products-form');
    var isNeedVariationsSave = $manageVariationProductsForm.formIsChanged(true);
    toggleVariationFields($manageVariationProductsForm, isVariationsTabActive, isNeedProductSave);
    toggleSaveButtons(isVariationsTabActive, isNeedProductSave);
    toggleBeforeEditNotification($manageVariationProductsForm, isNeedProductSave);
    showSaveVariationsNotification(isVariationsTabActive, isNeedVariationsSave, $tabsElm);
  });
  $.ceEvent('on', 'ce.commoninit', function (context) {
    var $variation_manage_form = context.find('.js-manage-variation-products-form');

    if (!$variation_manage_form.length) {
      return;
    }

    var feature_select_map = {};
    context.find('.js-product-variation-feature').each(function () {
      var $select = $(this);
      feature_select_map[$select.data('caFeatureId')] = $select;
    });
    $variation_manage_form.on('mouseenter touchstart', '.js-product-variation-feature-item', function () {
      var $select = $(this),
          val = $select.val(),
          feature_id = $select.data('caFeatureId');

      if (!feature_select_map[feature_id] || $select.hasClass('js-loaded')) {
        return;
      }

      $select.empty();
      feature_select_map[feature_id].find('option').clone().appendTo($select);
      $select.addClass('js-loaded').val(val);
      $select.find(':selected').prop('defaultSelected', true);
    });
  });

  function toggleVariationFields($manageVariationProductsForm, isVariationsTabActive, isNeedProductSave) {
    if (!isVariationsTabActive) {
      return;
    }

    var inputsSelector = '[data-ca-product-variations-temp-inputs-disabled="true"]';
    var hideWithInputsSelector = '[data-ca-product-variations-temp-hide-with-inputs-disabled="true"]';
    var linksSelector = '[data-ca-product-variations-temp-links-disabled="true"]';

    if (isNeedProductSave) {
      inputsSelector = ':input:enabled:not(.hidden)';
      hideWithInputsSelector = '.cm-hide-with-inputs:not(.hidden)';
      linksSelector = '[href]:not(.disabled):not(.hidden)';
    }

    $(inputsSelector, $manageVariationProductsForm).prop('disabled', isNeedProductSave).attr('data-ca-product-variations-temp-inputs-disabled', isNeedProductSave);
    $(hideWithInputsSelector, $manageVariationProductsForm).toggleClass('hidden', isNeedProductSave).attr('data-ca-product-variations-temp-hide-with-inputs-disabled', isNeedProductSave);
    $(linksSelector, $manageVariationProductsForm).toggleClass('disabled', isNeedProductSave).attr('data-ca-product-variations-temp-links-disabled', isNeedProductSave);
  }
  /**
   * Toggles save buttons on variations tabs of product update page
   */


  function toggleSaveButtons(isVariationsTabActive, isNeedProductSave) {
    var isShowProductSaveBtn = true;
    var isShowVariationsSaveBtn = false;

    if (isVariationsTabActive && !isNeedProductSave) {
      isShowProductSaveBtn = false;
      isShowVariationsSaveBtn = true;
    }

    $actions_panel = $('#actions_panel');
    $('.cm-product-save-buttons', $actions_panel).toggleClass('hidden', !isShowProductSaveBtn);
    $('#tools_variations_btn', $actions_panel).toggleClass('hidden', !isShowVariationsSaveBtn);
  }
  /**
   * Toggles save before edit notification on variations tabs of product update page
   */


  function toggleBeforeEditNotification($manageVariationProductsForm, isNeedProductSave) {
    $('[data-ca-product-variations="beforeEditNotification"]', $manageVariationProductsForm).toggleClass('hidden', !isNeedProductSave);
  }
  /**
   * Show save notification on variations tabs of product update page
   */


  function showSaveVariationsNotification(isVariationsTabActive, isNeedVariationsSave, $tabsElm) {
    if (!isVariationsTabActive && isNeedVariationsSave && !confirm(_.tr('text_changes_not_saved'))) {
      $tabsElm.ceTabs('switch', 'variations');
    }
  }
})(Tygh, Tygh.$);