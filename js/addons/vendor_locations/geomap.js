function _typeof(obj) { if (typeof Symbol === "function" && typeof Symbol.iterator === "symbol") { _typeof = function _typeof(obj) { return typeof obj; }; } else { _typeof = function _typeof(obj) { return obj && typeof Symbol === "function" && obj.constructor === Symbol && obj !== Symbol.prototype ? "symbol" : typeof obj; }; } return _typeof(obj); }

(function (_, $) {
  var methods = {
    init: function init() {
      var $elems = $(this);

      if (!window.google) {
        $.ceGeolocate('loadMapApi').done(function () {
          methods._init($elems);
        });
      } else {
        methods._init($elems);
      }

      return $elems;
    },
    _init: function _init($elems) {
      return $elems.each(function () {
        var $elem = $(this),
            marker_selector = $elem.data('caGeomapMarkerSelector'),
            max_zoom = parseInt($elem.data('caGeomapMaxZoom'), 10),
            map = new google.maps.Map(this, {
          maxZoom: max_zoom
        }),
            markers_bounds = new google.maps.LatLngBounds(),
            markers = [];
        $(marker_selector).each(function () {
          var $marker = $(this),
              lat = parseFloat($marker.data('caGeomapMarkerLat')),
              lng = parseFloat($marker.data('caGeomapMarkerLng')),
              url = $marker.data('caGeomapMarkerUrl'),
              label = $marker.data('caGeomapMarkerLabel');

          if (lat && lng) {
            var marker = new google.maps.Marker({
              position: {
                lat: lat,
                lng: lng
              },
              map: map,
              label: label
            });

            if (url) {
              marker.addListener('click', function () {
                $.redirect(url, false);
              });
            }

            markers.push(marker);
            markers_bounds.extend({
              lat: lat,
              lng: lng
            });
          }
        });
        $.getScript('js/addons/vendor_locations/lib/markerclusterer/markerclusterer.js', function () {
          var markerCluster = new MarkerClusterer(map, markers, {
            imagePath: 'js/addons/vendor_locations/lib/markerclusterer/m'
          });
          map.setCenter(markers_bounds.getCenter());
          map.fitBounds(markers_bounds);
        });
      });
    },
    _removeAllMarkers: function _removeAllMarkers($container) {
      if (!$container.length) {
        return;
      }

      $($container.data('caGeomapMarkerSelector')).remove();
    },
    _addMarkers: function _addMarkers($container, markers) {
      if (!$container.length) {
        return;
      }

      var $markersContainer = $($container.data('caGeomapMarkersContainerSelector'));
      $.each(markers, function (index, marker) {
        $markersContainer.append($('<div>', {
          class: 'cm-vendor-map-marker-elm_company_location_map',
          'data-ca-geomap-marker-lat': marker.lat,
          'data-ca-geomap-marker-lng': marker.lng
        }));
      });
      $container.ceGeomap();
    },
    _toggleMap: function _toggleMap($container, isShow) {
      if (!$container.length) {
        return;
      }

      $container.toggleClass('hidden', !isShow);
    }
  };
  $.ceEvent('on', 'ce.geocomplete.select', function ($elem, location, result) {
    var markers = [{
      lat: location.lat,
      lng: location.lng
    }];
    var $container = $('#' + $elem.data('caGeocompleteMapElemId'));

    methods._removeAllMarkers($container);

    methods._addMarkers($container, markers);

    methods._toggleMap($container, true);
  });

  $.fn.ceGeomap = function (method) {
    if (methods[method]) {
      return methods[method].apply(this, Array.prototype.slice.call(arguments, 1));
    } else if (_typeof(method) === 'object' || !method) {
      return methods.init.apply(this, arguments);
    } else {
      $.error('ty.geomap: method ' + method + ' does not exist');
    }
  };
})(Tygh, Tygh.$);