(function (_, $) {
  $(_.doc).on('click', '.cm-vendor-product-option', function () {
    $('.cm-apply-product-options-menu-item').toggleClass('hidden', $('.cm-vendor-product-option.selected').length);
  });
})(Tygh, Tygh.$);