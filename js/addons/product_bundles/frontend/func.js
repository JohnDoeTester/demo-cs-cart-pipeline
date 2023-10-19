(function (_, $) {
  function submitBundleForm($form) {
    var $targetForm = $($form.data('caProductBundlesTargetForm'));
    var $formInner = $('[data-ca-product-bundles="formInner"]', $targetForm);
    var bundleProductKey = $form.data('caProductBundlesBundleProductKey');
    var productId = $form.data('caProductBundlesProductId');
    var $optionsRequired = $('[data-ca-product-bundles="optionsRequired"]', $targetForm); // Create a temporary container for the fields in the form

    var $formData = $('<div>').attr({
      'data-ca-product-bundles': 'formData',
      class: 'hidden'
    });
    $formInner.append($formData); // Move fields to the temporary container

    $(':input:enabled[name]', $form.find('[data-ca-product-bundles="fieldContainer"]')).appendTo($formData); // Rename "Product ID" to "Product ID + Bundle product key"

    $("[name^=\"product_data[".concat(productId, "][product_options]\"]"), $formData).each(function () {
      $(this).attr('name', $(this).attr('name').replace("product_data[".concat(productId, "][product_options]"), "product_data[".concat(productId, "_").concat(bundleProductKey, "][product_options]")));
    }); // Disable validation of required fields

    $optionsRequired.removeClass('cm-required'); // Submit the bundle form

    setTimeout(function () {
      $targetForm.trigger('submit');
    }, 0);
  } // Fixing the width of the owl carousel when it is opened in a popup.


  function fixScrollerWidthInPopup(scrollerData, stage) {
    var $body = $('body');
    var isMobile = $body.hasClass('screen--xs') || $body.hasClass('screen--xs-large') || $body.hasClass('screen--sm');
    var $scroller = scrollerData.$elem;
    var $bundleDialog = {};

    if (stage === 'beforeInit' || stage === 'afterInit') {
      $bundleDialog = $scroller.closest('[data-ca-product-bundles="getProductBundlesPopupContent"]') ? $scroller.closest('[data-ca-product-bundles="getProductBundlesPopupContent"]').closest('[id^="content_"]') : undefined;
    } else if (stage === 'beforeUpdate') {
      $bundleDialog = $.ceDialog('get_last');
    }

    if (!$scroller.length || !$scroller.is('[data-ca-product-bundles="scroller"]') || !$bundleDialog.length || !$bundleDialog.has($scroller).length) {
      return;
    }

    if (stage === 'beforeInit' || stage === 'afterInit') {
      $scroller.toggleClass('ty-product-bundles-bundle-form__products--width-fix', stage === 'beforeInit');
    } else if (stage === 'beforeUpdate' && !isMobile) {
      // Update bundle scroller size
      scrollerData.reinit(); // Update bundle dialog position

      $bundleDialog.dialog({
        position: {
          of: window
        }
      });
    }
  } // Events


  $.ceEvent('on', 'ce.formpost_product_bundles_get_feature_variants', function ($form) {
    submitBundleForm($form);
    return false;
  });
  $.ceEvent('on', 'ce.formpost_product_bundles_get_option_variants', function ($form) {
    submitBundleForm($form);
    return false;
  });
  $.ceEvent('on', 'ce.scroller.beforeInit', function (scrollerData) {
    fixScrollerWidthInPopup(scrollerData, 'beforeInit');
  });
  $.ceEvent('on', 'ce.scroller.afterInit', function (scrollerData) {
    fixScrollerWidthInPopup(scrollerData, 'afterInit');
  });
  $.ceEvent('on', 'ce.scroller.beforeUpdate', function (scrollerData) {
    fixScrollerWidthInPopup(scrollerData, 'beforeUpdate');
  });
})(Tygh, Tygh.$);