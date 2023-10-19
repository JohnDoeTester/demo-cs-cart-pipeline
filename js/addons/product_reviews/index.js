(function (_, $) {
  $(_.doc).on('change', '[data-ca-product-review="newProductReviewAdditionalWriteAnonymouslyCheckbox"]', function () {
    var isChecked = $(this).prop('checked'),
        $customerLabels = $("[data-ca-product-review=\"newProductReviewCustomerTitle\"],\n                                  [data-ca-product-review=\"newProductReviewCustomerProfileNameLabel\"],\n                                  [data-ca-product-review=\"newProductReviewCustomerProfileCityLabel\"],\n                                  [data-ca-product-review=\"newProductReviewCustomerProfileCountryLabel\"]");
    $customerInputs = $("[data-ca-product-review=\"newProductReviewCustomerProfileNameInput\"],\n                                  [data-ca-product-review=\"newProductReviewCustomerProfileCityInput\"],\n                                  [data-ca-product-review=\"newProductReviewCustomerProfileCountryInput\"]");

    if (!$customerLabels.length || !$customerInputs.length) {
      return;
    }

    $customerLabels.toggleClass('cm-required', !isChecked);
    $customerInputs.each(function () {
      var $elem = $(this);
      var newLabel = isChecked ? $elem.data('caProductReviewLabel') : $elem.data('caProductReviewLabelRequired');
      $elem.attr('title', newLabel);

      if ($elem.is('select')) {
        var newOption = isChecked ? $elem.data('caProductReviewOption') : $elem.data('caProductReviewOptionRequired');
        $elem.find('option[value=""]').text(newOption);
      } else {
        $elem.attr('placeholder', newLabel);
      }
    });
  });
  $.ceEvent('on', 'ce.fileuploader.display_filename', function (id, file_type, file) {
    var countFiles = $('[data-ca-product-review="newProductReviewMedia"] .cm-fu-file:visible').length,
        showMessage = countFiles < Math.round(_.max_images_upload / 2),
        canAddFiles = countFiles < _.max_images_upload,
        $uploadFileContainer = $('[data-ca-product-review="fileuploaderDropZone"]'),
        $infoMessageBlock = $('[data-ca-product-review="newProductReviewMediaInfo"]'),
        $dropZone = $('[data-ca-product-review="newProductReviewMedia"] [data-ca-product-review="fileuploaderDropZone"]'),
        $addFileButtons = $('[data-ca-product-review="newProductReviewMedia"] [data-ca-product-review="fileuploaderDropZoneButtons"]');
    $uploadFileContainer.toggleClass('hidden', !canAddFiles);
    $infoMessageBlock.toggleClass('hidden', showMessage);
    $dropZone.toggleClass('[data-ca-product-review="fileuploaderDropZone"]', countFiles === 0);
    $addFileButtons.toggleClass('[data-ca-product-review="fileuploaderDropZoneButtons"]', countFiles === 0);
  });
})(Tygh, Tygh.$);