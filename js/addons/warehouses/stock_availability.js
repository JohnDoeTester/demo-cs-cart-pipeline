(function (_, $) {
  function fn_warehouses_get_stock_avilability() {
    var $containers = $('[data-ca-warehouses-stock-availability-product-id]');
    $containers.each(function (i, elm) {
      var $container = $(elm),
          productId = $container.data('caWarehousesStockAvailabilityProductId'),
          isLoaded = $container.data('caWarehousesStockAvailabilityIsLoaded');

      if (!productId || isLoaded) {
        return;
      }

      $container.data('caWarehousesStockAvailabilityIsLoaded', true);
      $.ceAjax('request', fn_url('warehouses.stock_availability'), {
        result_ids: 'warehouses_stock_availability_' + productId,
        data: {
          product_id: productId
        },
        method: 'get',
        hidden: true
      });
    });
  }

  $(_.doc).ready(fn_warehouses_get_stock_avilability);
  $(_.doc).on('click', '.ty-warehouses-shipping__link', function (e) {
    var $control = $(this),
        $tab = $($control.data('caWarehousesTabSelector')),
        $dialogOpener = $($control.data('caWarehousesDialogOpenerSelector'));

    if ($tab.length === 0 && $dialogOpener.length === 0) {
      $control.prop('href', $control.data('caWarehousesHref'));
      return;
    }

    if ($tab.length !== 0) {
      $control.prop('href', $control.data('caWarehousesTabAnchor'));
    }
  });
  $.ceEvent('on', 'ce.commoninit', function (context) {
    if ($('[data-ca-warehouses-stock-availability-product-id]', context).length) {
      fn_warehouses_get_stock_avilability();
    }
  });
})(Tygh, Tygh.$);