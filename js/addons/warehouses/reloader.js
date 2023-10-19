(function (_, $) {
  $.ceEvent('on', 'ce:geomap:location_set_after', function (location, $container, response, auto_detect) {
    if (!response.is_detected || !$(_.body).find('.cm-warehouse-block-depends-by-location').length) {
      return;
    }

    $.ceAjax('request', _.current_url, {
      result_ids: _.container,
      full_render: true
    });
  });
})(Tygh, Tygh.$);