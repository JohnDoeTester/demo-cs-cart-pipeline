(function (_, $) {
  $.ceEvent('on', 'ce.products_update_features.product_feature_save', function (response) {
    if (!response.success) {
      return;
    }

    var $quickAddFeatureDialog = $('#product_features_quick_add_feature'),
        productId = $quickAddFeatureDialog.data('caProductId'),
        returnUrl = $quickAddFeatureDialog.data('caReturnUrl'),
        targetId = $quickAddFeatureDialog.data('caTargetId'),
        featureValue = '';

    if (response.variants && Object.keys(response.variants).length) {
      var variant = response.variants[Object.keys(response.variants)[0]];
      featureValue = variant.variant_id;
    }

    $quickAddFeatureDialog.ceInlineDialog('destroy');
    $.ceAjax('request', fn_url('products.update_feature'), {
      method: 'post',
      result_ids: targetId,
      data: {
        product_id: productId,
        feature_id: response.feature_id,
        feature_value: featureValue
      }
    });
  });
})(Tygh, Tygh.$);