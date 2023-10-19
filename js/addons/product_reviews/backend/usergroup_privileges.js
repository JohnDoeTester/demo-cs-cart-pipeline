(function (_, $) {
  $.ceEvent('on', 'ce.commoninit', function (context) {
    $(context).find('div[id$=privileges_list_products_product_reviews]').on('click', function (e) {
      var $privilege = $(e.target);

      if ($privilege.prop('checked')) {
        $(this).find('input[id=privilege_products_product_reviews_view_product_reviews]').prop('checked', true);
      }
    });
  });
})(Tygh, Tygh.$);