(function (_, $) {
  $.ceEvent('on', 'ce.commoninit', function (context) {
    var $mapContainers = $('.cm-geo-map-container', context);

    if (!$mapContainers.length) {
      return;
    }

    fn_init_maps(context);
    fn_init_address_on_map(context);
  });

  function fn_init_maps(context) {
    $(context).find('.cm-geo-map-container').each(function (index, container) {
      var $container = $(container),
          marker_selector = $container.data('caGeoMapMarkerSelector');
      var options = {
        initial_lat: $container.data('caGeoMapInitialLat'),
        initial_lng: $container.data('caGeoMapInitialLng'),
        zoom: $container.data('caGeoMapZoom'),
        language: $container.data('caGeoMapLanguage'),
        controls: {
          no_controls: fn_get_boolean_from_data_attribute($container, 'caGeoMapControlsNoControls'),
          enable_traffic: fn_get_boolean_from_data_attribute($container, 'caGeoMapControlsEnableTraffic'),
          enable_layers: fn_get_boolean_from_data_attribute($container, 'caGeoMapControlsEnableLayers'),
          enable_fullscreen: fn_get_boolean_from_data_attribute($container, 'caGeoMapControlsEnableFullscreen'),
          enable_zoom: fn_get_boolean_from_data_attribute($container, 'caGeoMapControlsEnableZoom'),
          enable_ruler: fn_get_boolean_from_data_attribute($container, 'caGeoMapControlsEnableRuler'),
          enable_search: fn_get_boolean_from_data_attribute($container, 'caGeoMapControlsEnableSearch'),
          enable_routing: fn_get_boolean_from_data_attribute($container, 'caGeoMapControlsEnableRouting'),
          enable_geolocation: fn_get_boolean_from_data_attribute($container, 'caGeoMapControlsEnableGeolocation'),
          enable_panorama: fn_get_boolean_from_data_attribute($container, 'caGeoMapControlsEnablePanorama'),
          enable_rotation: fn_get_boolean_from_data_attribute($container, 'caGeoMapControlsEnableRotation')
        },
        behaviors: {
          no_behaviors: fn_get_boolean_from_data_attribute($container, 'caGeoMapBehaviorsNoBehaviors'),
          enable_drag: fn_get_boolean_from_data_attribute($container, 'caGeoMapBehaviorsEnableDrag'),
          enable_smart_drag: fn_get_boolean_from_data_attribute($container, 'caGeoMapBehaviorsEnableSmartDrag'),
          enable_drag_on_mobile: fn_get_boolean_from_data_attribute($container, 'caGeoMapBehaviorsEnableDragOnMobile'),
          enable_scroll_zoom: fn_get_boolean_from_data_attribute($container, 'caGeoMapBehaviorsEnableScrollZoom'),
          enable_dbl_click_zoom: fn_get_boolean_from_data_attribute($container, 'caGeoMapBehaviorsEnableDblClickZoom'),
          enable_multi_touch: fn_get_boolean_from_data_attribute($container, 'caGeoMapBehaviorsEnableMultiTouch'),
          enable_ruler: fn_get_boolean_from_data_attribute($container, 'caGeoMapBehaviorsEnableRuler'),
          enable_route_editor: fn_get_boolean_from_data_attribute($container, 'caGeoMapBehaviorsEnableRouteEditor')
        }
      };
      options.markers = $.ceGeoMap('prepareMarkers', marker_selector);
      options.controls = fn_filter_out_object_nulls(options.controls);
      options.behaviors = fn_filter_out_object_nulls(options.behaviors);
      options.behaviors = fn_process_additional_behaviors(options.behaviors);
      $container.ceGeoMap(options);
    });
  }

  function fn_filter_out_object_nulls(object) {
    var filtered = {};
    $.each(object, function (index, value) {
      if (value !== null) {
        filtered[index] = value;
      }
    });
    return filtered;
  }

  function fn_get_boolean_from_data_attribute($elm, attribute_name) {
    return $elm.data(attribute_name) !== undefined ? !!$elm.data(attribute_name) : null;
  }

  function fn_init_address_on_map(context) {
    var $address_on_map_container = $(context).find('.cm-aom-map-container');
    $address_on_map_container.on('ce:geomap:init', function (e) {
      var address = [$address_on_map_container.data('caAomCountry'), $address_on_map_container.data('caAomCity'), $address_on_map_container.data('caAomAddress')].filter(function (item) {
        return !!item;
      }).join(', ');

      if (!address) {
        return;
      }

      $.ceGeoCode('getCoords', address).done(function (data) {
        if (data.lat && data.lng) {
          data.static = true;
          data.content = address;
          $address_on_map_container.ceGeoMap('removeAllMarkers');
          $address_on_map_container.ceGeoMap('addMarkers', [data]);
          $address_on_map_container.ceGeoMap('setCenter', data.lat, data.lng);
        }
      });
    });
  }

  function fn_process_additional_behaviors(behaviors) {
    if ($.isMobile() != null) {
      behaviors.enable_drag = behaviors.enable_drag_on_mobile;
    }

    return behaviors;
  }
})(Tygh, Tygh.$);