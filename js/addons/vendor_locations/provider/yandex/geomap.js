function _typeof(obj) { if (typeof Symbol === "function" && typeof Symbol.iterator === "symbol") { _typeof = function _typeof(obj) { return typeof obj; }; } else { _typeof = function _typeof(obj) { return obj && typeof Symbol === "function" && obj.constructor === Symbol && obj !== Symbol.prototype ? "symbol" : typeof obj; }; } return _typeof(obj); }

(function (_, $) {
  var methods = {
    default_zoom: 10,
    default_max_zoom: 16,
    init: function init(options) {
      var $container = $(this),
          self = methods;

      if ($container.data('ceGeoMapInitialized')) {
        return true;
      }

      $.vendorLocationsInitYandexApi(options).done(function () {
        self._initMap($container, options);

        self._registerMapClickEvent($container);

        self._registerSearchEvent($container);

        self._fireEvent($container, 'ce:geomap:init');
      }).fail(function () {
        self._fireEvent($container, 'ce:geomap:init_failed');
      });
      return this;
    },
    _initMap: function _initMap($container, options) {
      options = options || {};

      var self = methods,
          controls = self._initMapControls(options);

      var map_state = {
        zoom: parseInt(options.zoom) || self.default_zoom,
        type: 'yandex#map',
        center: [options.initial_lat || 0, options.initial_lng || 0],
        controls: controls,
        draggableCursor: 'crosshair',
        draggingCursor: 'pointer'
      };
      $container.ceGeomap('destroy');
      var map = new ymaps.Map($container[0], map_state);
      $container.data('caGeoMap', map);
      var myCollection = new ymaps.GeoObjectCollection();
      $container.data('caMyCollection', myCollection);

      self._renderMarkers($container, options.markers, options);
    },
    _initMapControls: function _initMapControls(options) {
      var controls = options.controls;

      if ($.isEmptyObject(controls)) {
        return ['default'];
      } else if (controls.no_controls) {
        return [];
      }

      var ctls = [];

      if (controls.enable_traffic) {
        ctls.push('trafficControl');
      }

      if (controls.enable_layers) {
        ctls.push('typeSelector');
      }

      if (controls.enable_fullscreen) {
        ctls.push('fullscreenControl');
      }

      if (controls.enable_zoom) {
        ctls.push('zoomControl');
      }

      if (controls.enable_ruler) {
        ctls.push('rulerControl');
      }

      if (controls.enable_search) {
        ctls.push('searchControl');
      }

      if (controls.enable_routing) {
        ctls.push('routeButtonControl');
      }

      if (controls.enable_geolocation) {
        ctls.push('geolocationControl');
      }

      return ctls;
    },
    _renderMarkers: function _renderMarkers($container, markers, options) {
      var self = methods,
          map = self._getGeoMap($container);

      myCollection = self._getMyCollection($container);

      if (!markers.length) {
        self.initAddressOnMap($container);
        return;
      }

      $.each(markers, function (index, marker) {
        var map_marker = new ymaps.Placemark([marker.lat, marker.lng], {
          iconCaption: marker.label
        });

        if (marker.url) {
          map_marker.events.add('click', function () {
            $.redirect(marker.url, false);
          });
        }

        myCollection.add(map_marker);
      });
      map.geoObjects.add(myCollection);
      self.adjustMapBoundariesToSeeAllMarkers($container);
      return true;
    },
    _getGeoMap: function _getGeoMap($container) {
      return $container.data('caGeoMap');
    },
    prepareMarkers: function prepareMarkers(marker_selector) {
      var markers = [];
      $(marker_selector).each(function () {
        var $marker = $(this),
            lat = parseFloat($marker.data('caGeomapMarkerLat')),
            lng = parseFloat($marker.data('caGeomapMarkerLng')),
            url = $marker.data('caGeomapMarkerUrl'),
            label = $marker.data('caGeomapMarkerLabel');

        if (lat && lng) {
          var marker = {
            lat: lat,
            lng: lng,
            url: url,
            label: label
          };
          markers.push(marker);
        }
      });
      return markers;
    },
    _registerMapClickEvent: function _registerMapClickEvent($container) {
      var self = methods,
          map = self._getGeoMap($container);

      if (!map) {
        return false;
      }

      map.events.add('click', function (result) {
        map.balloon.close();
        var coords = result.get('coords');
        ymaps.geocode(coords).then(function (res) {
          var geo_object = res.geoObjects.get(0);

          self._createBalloonLayouts($container, geo_object, coords);

          map.options.set('balloonContentLayout', BalloonContentLayout);
          map.balloon.open(coords);
        });
      });
      return true;
    },
    _createBalloonLayouts: function _createBalloonLayouts($container, geo_object, coords) {
      var self = methods,
          map = self._getGeoMap($container),
          myCollection = self._getMyCollection($container);

      searchResults = self._getSearchResults($container);

      if (!map) {
        return false;
      }

      PlacemarkBalloonContent = ymaps.templateLayoutFactory.createClass('<b>' + _.tr('chosen_location') + '</b><br />' + '<p>' + geo_object.getAddressLine() + '</p>' + '<button id="ya-button-remove" class="btn btn-primary">' + _.tr('remove') + '</button>', {
        build: function build() {
          PlacemarkBalloonContent.superclass.build.call(this);
          $('#ya-button-remove').click(function (e) {
            e.preventDefault();
            myCollection.remove(myPlacemark);
            map.balloon.close();
          });
        }
      });
      BalloonContentLayout = ymaps.templateLayoutFactory.createClass('<b>' + _.tr('confirm_location') + '</b><br />' + '<p>' + geo_object.getAddressLine() + '</p>' + '<button id="ya-button-confirm" class="btn btn-primary">' + _.tr('confirm') + '</button>', {
        build: function build() {
          BalloonContentLayout.superclass.build.call(this);
          $('#ya-button-confirm').click(function (e) {
            e.preventDefault();
            searchResults.removeAll();
            myCollection.removeAll();

            self._removeAllMarkers($container);

            myPlacemark = new ymaps.Placemark(coords, {}, {
              balloonContentLayout: PlacemarkBalloonContent
            });
            myCollection.add(myPlacemark);
            map.geoObjects.add(myCollection);
            var location = $.ceGeolocate('getGeoObjectLocation', geo_object),
                markers = [{
              lat: location.lat,
              lng: location.lng
            }];

            self._addMarkers($container, markers);

            $.ceGeolocate('loadNormalizedLocationData', location).done(function (normalized_location) {
              var $value_elem = $('#elm_company_location_value');

              if ($value_elem.length) {
                $value_elem.prop("disabled", false);
                $value_elem.val(JSON.stringify(normalized_location));
              }

              map.balloon.close();
            }).fail(function () {// TODO
            });
          });
        }
      });
    },
    _registerFullscreenEvent: function _registerFullscreenEvent($container) {
      var self = methods,
          map = self._getGeoMap($container);

      if (!map) {
        return false;
      }

      map.container.events.add('fullscreenenter', function (e) {
        map.behaviors.enable('scrollZoom');
      });
      map.container.events.add('fullscreenexit', function (e) {
        map.behaviors.disable('scrollZoom');
      });
      return true;
    },
    _fireEvent: function _fireEvent($container, name, data) {
      data = data || [];
      $container.trigger(name, data);
      data.unshift($container);
      $.ceEvent('trigger', name, data);
    },
    _registerSearchEvent: function _registerSearchEvent($container) {
      var self = methods,
          map = self._getGeoMap($container);

      if (!map) {
        return false;
      }

      var searchControl = new ymaps.control.SearchControl({
        options: {
          noPlacemark: true,
          provider: 'yandex#map'
        }
      }),
          searchResults = new ymaps.GeoObjectCollection(null, {
        hintContentLayout: ymaps.templateLayoutFactory.createClass('$[properties.name]')
      });
      $container.data('caSearchResults', searchResults);
      map.controls.add(searchControl);
      map.geoObjects.add(searchResults);
      searchResults.events.add('click', function (e) {
        self._createBalloonLayouts($container, e.get('target'), e.get('coords'));

        e.get('target').options.set('balloonContentLayout', BalloonContentLayout);
      });
      searchControl.events.add('resultselect', function (e) {
        var index = e.get('index');
        searchControl.getResult(index).then(function (res) {
          searchResults.add(res);
        });
      }).add('resultshow', function (e) {
        var index = e.get('index');
        searchControl.getResult(index).then(function (res) {
          self._createBalloonLayouts($container, res, res.geometry.getCoordinates());

          res.options.set('balloonContentLayout', BalloonContentLayout);
          res.balloon.open();
        });
      }).add('submit', function () {
        searchResults.removeAll();
      });
      return true;
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
    },
    resize: function resize() {
      var self = methods,
          $container = $(this),
          map = self._getGeoMap($container);

      if (!map) {
        return false;
      }

      map.container.fitToViewport();
      return true;
    },
    destroy: function destroy() {
      var self = methods,
          $container = $(this),
          map = self._getGeoMap($container);

      if (!map) {
        return false;
      }

      map.destroy();
      return true;
    },
    removeAllMarkers: function removeAllMarkers($container) {
      var self = methods,
          map = self._getGeoMap($container);

      if (map) {
        map.geoObjects.removeAll();
      }

      return true;
    },
    _getMyCollection: function _getMyCollection($container) {
      return $container.data('caMyCollection');
    },
    _getSearchResults: function _getSearchResults($container) {
      return $container.data('caSearchResults');
    },
    adjustMapBoundariesToSeeAllMarkers: function adjustMapBoundariesToSeeAllMarkers($container) {
      var self = methods,
          collection = self._getMyCollection($container),
          map = self._getGeoMap($container);

      if (!collection || !map) {
        return false;
      }

      map.setBounds(collection.getBounds(), {
        checkZoomRange: true
      }).then(function () {
        if (map.getZoom() > self.default_max_zoom) map.setZoom(self.default_max_zoom);
      });
      return true;
    },
    setCenter: function setCenter($container, lat, lng, zoom) {
      var self = methods,
          map = self._getGeoMap($container);

      if (!map) {
        return false;
      }

      map.setCenter([lat, lng]);
      map.setZoom(parseInt(zoom) || self.default_zoom);
      return true;
    },
    getCenter: function getCenter() {
      var self = methods,
          $container = $(this),
          map = self._getGeoMap($container);

      if (!map) {
        return {};
      }

      var coords = map.getCenter();
      return {
        lat: coords[0],
        lng: coords[1]
      };
    },
    exitFullscreen: function exitFullscreen() {
      var self = methods,
          $container = $(this),
          map = self._getGeoMap($container);

      if (map) {
        map.container.exitFullscreen();
        return true;
      }

      return false;
    },
    initAddressOnMap: function initAddressOnMap($container) {
      var address = [$container.data('caAomCountry'), $container.data('caAomCity'), $container.data('caAomAddress')].filter(function (item) {
        return !!item;
      }).join(', ');

      if (!address) {
        return false;
      }

      $.ceGeocode('getCoords', address).done(function (data) {
        if (data.lat && data.lng) {
          methods.setCenter($container, data.lat, data.lng);
        }
      });
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
  });

  $.fn.ceGeomap = function (method) {
    if (methods[method]) {
      return methods[method].apply(this, Array.prototype.slice.call(arguments, 1));
    } else if (_typeof(method) === 'object' || !method) {
      return methods.init.apply(this, arguments);
    } else {
      $.error('ty.geoMap: method ' + method + ' does not exist');
    }
  };

  $.ceGeomap = function (action, data) {
    if (methods[action]) {
      return methods[action].apply(this, Array.prototype.slice.call(arguments, 1));
    } else {
      $.error('ty.geoMap: action ' + action + ' does not exist');
    }
  };
})(Tygh, Tygh.$);