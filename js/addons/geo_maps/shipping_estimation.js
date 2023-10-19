(function (_, $) {
  function fn_geo_maps_get_shipping_estimation_content(force_load) {
    var $containers = $('[data-ca-geo-maps-shipping-estimation-product-id]');
    $containers.each(function (i, elm) {
      var $container = $(elm),
          product_id = $container.data('caGeoMapsShippingEstimationProductId'),
          is_loaded = $container.data('caGeoMapsShippingEstimationIsLoaded'),
          show_title = $container.data('caGeoMapsShippingEstimationShowTitle');
      no_shippings_available_short_text = $container.data('caGeoMapsShippingEstimationNoShippingsAvailableShortText');

      if (!product_id || is_loaded && !force_load) {
        return;
      }

      $container.data('caGeoMapsShippingEstimationIsLoaded', true);
      var shippings_list_popup = $.ceDialog('get_last'),
          is_shippings_list_popup = shippings_list_popup.attr('id') === $container.data('caGeoMapsShippingsMethodsListId'),
          is_popup_opened = shippings_list_popup && shippings_list_popup.is(':visible'),
          show_ajax_progress_icon = false;

      if (is_shippings_list_popup && is_popup_opened) {
        show_ajax_progress_icon = true;
      }

      var data = {
        product_id: product_id,
        show_title: show_title,
        no_shippings_available_short_text: no_shippings_available_short_text
      };
      var components = $.parseUrl(location.href);

      if (components.parsed_query.action !== undefined && components.parsed_query.action === 'preview') {
        data.preview = 1;
      }

      $.ceAjax('request', fn_url('geo_maps.shipping_estimation'), {
        result_ids: 'geo_maps_shipping_estimation_' + product_id + ',geo_maps_shipping_methods_list_' + product_id,
        data: data,
        method: 'get',
        hidden: show_ajax_progress_icon ? false : true
      });
    });
  }

  $.ceEvent('on', 'ce:geomap:location_set_after', function (location, $container, response, auto_detect) {
    fn_geo_maps_get_shipping_estimation_content(true);
  });
  $(_.doc).ready(function () {
    fn_geo_maps_get_shipping_estimation_content();
  });
  $.ceEvent('on', 'ce.commoninit', function (context) {
    if ($('[data-ca-geo-maps-shipping-estimation-product-id]', context).length) {
      fn_geo_maps_get_shipping_estimation_content();
    }
  });
})(Tygh, Tygh.$);