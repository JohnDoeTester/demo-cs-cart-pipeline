(function ($, _) {
  $($('.cm-seo-check-changed-block-container', _.doc)).on('keyup', '.cm-seo-check-changed', function () {
    var self = $(this);

    if (self.prop('defaultValue') !== '') {
      self.parents('.cm-seo-check-changed-block-container').find('.cm-seo-check-changed-block').switchAvailability(self.val() == self.prop('defaultValue'), true);
    }
  });
  $(document).ready(function () {
    var $titleElm = $('.cm-seo-srs-title');

    if (!$titleElm.length) {
      return false;
    }

    var $priceElm = $('#sec_elm_seo_srs_price');
    var $descriptionElm = $('.cm-seo-srs-description');
    var $productPageTitleElm = $('#elm_product_page_title');
    var $productDescriptionElm = $('#product_description_product');
    var $productFullDescriptionElm = $('#elm_product_full_descr');
    var $productShortDescriptionElm = $('#elm_product_short_descr');
    var $productMetaDescriptionElm = $('#elm_product_meta_descr');
    var titleLength = $productPageTitleElm.data('caSeoLength') || 60;
    var descriptionLength = $productMetaDescriptionElm.data('caSeoLength') || 145;
    $productDescriptionElm.change(function () {
      $titleElm.text(format($(this).val(), titleLength));
    });
    $productPageTitleElm.on('input', function () {
      if ($(this).val()) {
        $titleElm.text(format($(this).val(), titleLength));
      } else {
        $titleElm.text(format($productDescriptionElm.val(), titleLength));
      }
    });
    $('#elm_price_price').change(function () {
      $priceElm.text($(this).val());
    });
    $productFullDescriptionElm.ceEditor('change', function (html) {
      $descriptionElm.text(format(html, descriptionLength));
    });
    $productShortDescriptionElm.ceEditor('change', function (html) {
      if (!$productFullDescriptionElm.ceEditor('val')) {
        $descriptionElm.text(format(html, descriptionLength));
      }
    });
    $('#elm_product_meta_descr').on('input', function () {
      if ($(this).val()) {
        $descriptionElm.text(format($(this).val(), descriptionLength));
      } else if ($productFullDescriptionElm.val()) {
        $descriptionElm.text(format($productFullDescriptionElm.val(), descriptionLength));
      } else {
        $descriptionElm.text(format($productShortDescriptionElm.val(), descriptionLength));
      }
    });
  });

  function format(str, len) {
    str = fn_strip_tags(str);
    var modifiedStr = str.substr(0, len);

    if (str.length > len) {
      modifiedStr += ' ...';
    }

    return modifiedStr;
  }
})(Tygh.$, Tygh);