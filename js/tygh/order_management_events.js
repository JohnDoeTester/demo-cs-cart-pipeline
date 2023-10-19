(function (_, $) {
  $.ceEvent('one', 'ce.commoninit', function () {
    $(document).on('click', '.select2-search__field', function (event) {
      var isMobile = $('body').hasClass('screen--xs') || $('body').hasClass('screen--xs-large') || $('body').hasClass('screen--sm');

      if (isMobile) {
        var self = $(event.target);
        var topBlock = $('.btn-bar.btn-toolbar.dropleft.pull-right');
        var offset = topBlock.offset().top + topBlock.height() - 15;
        $.scrollToElm('#product_add');
      }
    });
  });
  $.ceEvent('on', 'ce.formcheckfailed_om_cart_form', function ($form) {
    var $elemHavingError = $form.find('.order-management-options-content .control-group.error'),
        hasError = $elemHavingError.length > 0;

    if (hasError) {
      $elemHavingError.each(function () {
        var $parent = $(this).closest('td'),
            cart_id = $parent.find('[name="appearance[id]"]').val(),
            product_id = $parent.find("[name=\"cart_products[".concat(cart_id, "][product_id]\"]")).val(); //expand the list of options if validation error

        $form.find("#on_product_options_".concat(cart_id, "_").concat(product_id, ":visible")).click();
      });
    }
  });
})(Tygh, Tygh.$);