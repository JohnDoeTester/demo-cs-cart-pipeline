(function (_, $) {
  function initMainProductVariationFeaturesPicker($self) {
    var $option;

    if ($self.prop('tagName').toLowerCase() === 'select') {
      $option = $self.find('option:selected');
    } else {
      $option = $self;
    }

    if ($option.length) {
      if ($self.hasClass('cm-ajax')) {
        $.ceAjax('request', $option.data('caProductUrl'), {
          result_ids: $self.data('caTargetId'),
          save_history: $self.hasClass('cm-history'),
          force_exec: $self.hasClass('cm-ajax-force'),
          caching: true
        });
      } else {
        $.redirect($option.data('caProductUrl'));
      }
    }
  }

  function initCartProductVariationFeaturesPicker($self) {
    var $option = $self.find('option:selected');

    if ($option.length) {
      $.ceAjax('request', $option.data('caChangeUrl'), {
        method: 'post',
        full_render: true,
        result_ids: $self.data('caTargetId')
      });
    }
  }

  $(_.doc).on('change', '.cm-picker-product-variation-features select, .cm-picker-product-variation-features input[type="radio"]', function () {
    initMainProductVariationFeaturesPicker($(this));
  });
  $(_.doc).on('change', '.cm-picker-cart-product-variation-features select', function () {
    initCartProductVariationFeaturesPicker($(this));
  });
})(Tygh, Tygh.$);