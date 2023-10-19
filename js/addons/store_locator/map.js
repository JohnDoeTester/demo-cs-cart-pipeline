(function (_, $) {
  $(_.doc).ready(function () {
    var $container = $('#map_picker_container');

    function fn_save_latest_coordinates(coords) {
      $('#elm_latitude').data('caLatestLatitude', coords.lat);
      $('#elm_longitude').data('caLatestLongitude', coords.lng);
    }

    ;
    $container.on('ce:geomap:click', function (e, data) {
      if (!data.lat || !data.lng) {
        return;
      }

      var $container = $(e.target);
      $container.ceGeoMap('removeAllMarkers');
      $container.ceGeoMap('addMarkers', [data]);
      fn_save_latest_coordinates(data);
    });
    $container.on('ce:geomap:search_result_select', function (e, data) {
      if (!data.lat || !data.lng) {
        return;
      }

      var $container = $(e.target);
      $container.ceGeoMap('removeAllMarkers');
      $container.ceGeoMap('addMarkers', [data]);
      $container.ceGeoMap('setCenter', data.lat, data.lng);
      fn_save_latest_coordinates(data);
    });
    $('.cm-map-save-location').on('click touch', function (e) {
      var lat = $('#elm_latitude').data('caLatestLatitude') || null;

      if (lat) {
        $('#elm_latitude').val(lat);
      }

      var lng = $('#elm_longitude').data('caLatestLongitude') || null;

      if (lng) {
        $('#elm_longitude').val(lng);
      }
    });
    $container.on('ce:geomap:init', function (e) {
      $('#store_locator_picker_opener').on('click touch', function () {
        if ($('#elm_longitude').val() && $('#elm_longitude').val()) {
          return;
        }

        var city = $('#elm_city').val();

        if (!city) {
          return;
        }

        $.ceGeoCode('getCoords', city).done(function (data) {
          if (data.lat && data.lng) {
            $container.ceGeoMap('removeAllMarkers');
            $container.ceGeoMap('setCenter', data.lat, data.lng, 9);
            fn_save_latest_coordinates(data);
          }
        });
      });
    });
  });
  $.ceEvent('on', 'ce.commoninit', function (context) {
    $(context).find('.cm-store-locator-view-location').on('click touch', function () {
      var $jelm = $(this),
          lat = $jelm.data('caGeoMapMarkerLat'),
          lng = $jelm.data('caGeoMapMarkerLng'),
          container_id = $jelm.data('caTargetMapId'),
          $container = $('#' + container_id),
          stores_map_selector = '.cm-geo-map-container',
          $all_stores_container = $jelm.closest('#store_locator_location').find('.cm-store-locator__all-stores');

      if (!$container.length || !lat || !lng) {
        return false;
      }

      $container.ceGeoMap('setCenter', lat, lng); // Set active pickup office

      $('#pickup_office_list .cm-store-locator-view-location.ty-sdek-office__selected').removeClass('ty-sdek-office__selected');
      $($jelm).addClass('ty-sdek-office__selected');

      if (Modernizr.touchevents) {
        if (stores_map_selector) {
          $.scrollToElm(stores_map_selector);
        }
      }

      $all_stores_container.removeClass('store-locator__all-stores--hidden');
    });
    $(context).find('.cm-store-locator-view-locations').on('click touch', function () {
      var container_id = $(this).data('caTargetMapId'),
          $container = $('#' + container_id),
          $all_stores_container = $(this).closest('#store_locator_location').find('.cm-store-locator__all-stores');

      if (!$container.length) {
        return false;
      }

      $container.ceGeoMap('adjustMapBoundariesToSeeAllMarkers'); // Unset active pickup office

      $('#pickup_office_list .cm-store-locator-view-location.ty-sdek-office__selected').removeClass('ty-sdek-office__selected');
      $('#' + $(this).data('caStoresListFilterId')).val('').trigger('input');
      $all_stores_container.addClass('store-locator__all-stores--hidden');
    });
    $(context).find('.sl-search-control').on('change', function (e) {
      var $form = $('#store_locator_search_form');
      fn_store_locator_send_stores_search_request($form);
    });
  });
  $.ceEvent('on', 'ce:geomap:location_set_after', function (location, locality, $container, response, auto_detect) {
    if ('locality_text' in locality) {
      $('#store_locator_search_city option[value="' + locality.locality_text + '"]').prop('selected', true);
      var $form = $('#store_locator_search_form');
      fn_store_locator_send_stores_search_request($form);
    }
  });

  function fn_store_locator_send_stores_search_request($form) {
    var form_data = $form.serializeObject();
    $.ceAjax('request', $form.attr('action'), {
      caching: false,
      data: form_data
    });
  }
})(Tygh, Tygh.$);