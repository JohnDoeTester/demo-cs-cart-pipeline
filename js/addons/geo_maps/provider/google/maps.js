(function (_, $) {
  var google_map = {
    default_zoom: 16,
    clusterer_initialized: false,
    default_controls: {
      enable_search: true,
      enable_traffic: true,
      enable_layers: true,
      enable_zoom: true,
      enable_fullscreen: true
    },
    default_behaviors: {
      enable_scroll_zoom: true,
      enable_drag: true,
      enable_smart_drag: true,
      enable_dbl_click_zoom: true
    },
    init: function init(options) {
      var $container = $(this),
          self = google_map;

      if ($container.data('ceGeoMapInitialized')) {
        return true;
      }

      $.geoMapInitGoogleApi(options).done(function () {
        self._initMap($container, options);

        self._registerMapClickEvent($container);

        self._fireEvent($container, 'ce:geomap:init');

        $container.data('ceGeoMapInitialized', true);
      }).fail(function () {
        self._fireEvent($container, 'ce:geomap:init_failed');
      });
      return this;
    },
    _initMap: function _initMap($container, options) {
      options = options || {};

      var self = google_map,
          controls = self._initMapControls(options),
          behaviors = self._initMapBehaviors(options);

      var map_options = {
        center: new google.maps.LatLng(options.initial_lat || 0, options.initial_lng || 0),
        zoom: parseInt(options.zoom) || self.default_zoom,
        mapTypeId: google.maps.MapTypeId.ROADMAP
      };
      map_options = $.extend(map_options, controls, behaviors);
      var map = new google.maps.Map($container[0], map_options);
      $container.data('caGeoMap', map);

      self._renderMarkers($container, options.markers, options);

      self._initMapManualControls($container, options);
    },
    _initMapControls: function _initMapControls(options) {
      var self = google_map,
          controls = Object.assign({}, self.default_controls, options.controls),
          ctls = {
        zoomControl: false,
        mapTypeControl: false,
        scaleControl: false,
        streetViewControl: false,
        rotateControl: false,
        fullscreenControl: false
      };

      if (controls.no_controls) {
        return ctls;
      }

      if (controls.enable_layers) {
        ctls.mapTypeControl = true;
        ctls.mapTypeControlOptions = {
          mapTypeIds: [google.maps.MapTypeId.ROADMAP, google.maps.MapTypeId.TERRAIN, google.maps.MapTypeId.SATELLITE, google.maps.MapTypeId.HYBRID],
          position: google.maps.ControlPosition.TOP_RIGHT,
          style: google.maps.MapTypeControlStyle.DROPDOWN_MENU
        };
      }

      if (controls.enable_fullscreen) {
        ctls.fullscreenControl = true;
      }

      if (controls.enable_zoom) {
        ctls.zoomControl = true;
        ctls.zoomControlOptions = {
          position: google.maps.ControlPosition.LEFT_CENTER
        };
      }

      if (controls.enable_ruler) {
        // no native ruler control exists
        ctls.scaleControl = true;
      }

      if (controls.enable_panorama) {
        ctls.streetViewControl = true;
      }

      if (controls.enable_rotation) {
        ctls.rotateControl = true;
      }

      return ctls;
    },
    _initMapBehaviors: function _initMapBehaviors(options) {
      var self = google_map,
          behaviors = Object.assign({}, self.default_behaviors, options.behaviors),
          bhvs = {
        scrollwheel: false,
        draggable: false,
        disableDoubleClickZoom: true
      };

      if (behaviors.no_behaviors) {
        return bhvs;
      }

      if (behaviors.enable_drag || behaviors.enable_smart_drag) {
        bhvs.draggable = true;
      }

      if (behaviors.enable_scroll_zoom) {
        bhvs.scrollwheel = true;
      }

      if (behaviors.enable_dbl_click_zoom) {
        bhvs.disableDoubleClickZoom = false;
      }

      return bhvs;
    },
    _renderMarkers: function _renderMarkers($container, markers, options) {
      options = options || {};

      var self = google_map,
          map = self._getGeoMap($container);

      if (!map) {
        return false;
      }

      $container.ceGeoMap('removeAllMarkers');

      self._addMarkersToCluster($container, markers);

      self._showSelectedMarker($container, markers, options);

      return true;
    },
    _getGeoMap: function _getGeoMap($container) {
      return $container.data('caGeoMap');
    },
    _addMarkersToCluster: function _addMarkersToCluster($container, markers) {
      var self = google_map,
          map = self._getGeoMap($container),
          clusterer = self._getClusterer($container) || (map ? self._createClusterer(map) : null),
          cluster = [];

      if (!clusterer) {
        return false;
      }

      $.each(markers, function (index, marker) {
        var position = new google.maps.LatLng(marker.lat, marker.lng),
            marker_data = {
          position: position,
          map: map
        };
        var map_marker = new google.maps.Marker(marker_data);

        if (marker.content) {
          var infowindow = new google.maps.InfoWindow({
            content: marker.content
          });
          map_marker.ce_geo_map_infowindow = infowindow;
          map_marker.addListener('click', function () {
            $.each(clusterer.getMarkers(), function (index, mrk) {
              if (mrk.ce_geo_map_infowindow) {
                mrk.ce_geo_map_infowindow.close(map, mrk);
              }
            });
            infowindow.open(map, map_marker);
          });
        }

        map_marker.addListener('click', function (e) {
          var marker = self._normalizeMarkerClickResult(e);

          self._fireEvent($container, 'ce:geomap:click_marker', [marker]);
        });
        cluster.push(map_marker);
      });
      clusterer.addMarkers(cluster);
      $container.data('caGoogleClusterer', clusterer);
      return clusterer;
    },
    _normalizeMarkerClickResult: function _normalizeMarkerClickResult(result) {
      var coords = result.latLng.toJSON();
      return coords;
    },
    _getClusterer: function _getClusterer($container) {
      return $container.data('caGoogleClusterer');
    },
    _createClusterer: function _createClusterer(map) {
      return new MarkerClusterer(map, [], {
        imagePath: 'js/addons/geo_maps/provider/google/lib/markerclusterer/m'
      });
    },
    _showSelectedMarker: function _showSelectedMarker($container, markers, options) {
      var self = google_map,
          markers_quantity = markers.length;

      if (markers_quantity === 1) {
        var selected_marker = markers[0];
      } else {
        var selected_marker = $.grep(markers, function (marker) {
          return marker.selected;
        })[0];
      }

      if (selected_marker) {
        $container.ceGeoMap('setCenter', selected_marker.lat, selected_marker.lng, parseInt(options.zoom) || self.default_zoom);
      } else if (markers_quantity) {
        $container.ceGeoMap('adjustMapBoundariesToSeeAllMarkers');
      }

      return true;
    },
    _initMapManualControls: function _initMapManualControls($container, options) {
      var self = google_map,
          controls = Object.assign({}, self.default_controls, options.controls);

      if (controls.enable_traffic) {// TODO: button is required for convenient enabling/disabling traffic info on map
        // self._enableTrafficLayer($container);
      }

      if (controls.enable_search) {
        self._enableSearch($container);
      }
    },
    _enableTrafficLayer: function _enableTrafficLayer($container) {
      var self = google_map,
          map = self._getGeoMap($container);

      if (!map) {
        return false;
      }

      var trafficLayer = new google.maps.TrafficLayer();
      trafficLayer.setMap(map);
      return true;
    },
    _enableSearch: function _enableSearch($container) {
      var self = google_map,
          map = self._getGeoMap($container);

      if (!map) {
        return false;
      }

      var search_bar = $('<input>').attr('type', 'text').attr('placeholder', _.tr('geo_maps_google_search_bar_placeholder')).addClass('geo-map-google-search-bar');
      var search_box = new google.maps.places.SearchBox(search_bar[0]);
      map.controls[google.maps.ControlPosition.TOP_LEFT].push(search_bar[0]);
      search_bar.on('focus', function () {
        $('.pac-container').css('z-index', 9999);
      });
      map.addListener('bounds_changed', function () {
        search_box.setBounds(map.getBounds());
      });
      $container.data('caSearchBox', search_box);

      self._registerSearchEvent($container);

      return true;
    },
    _registerSearchEvent: function _registerSearchEvent($container) {
      var self = google_map,
          search_box = $container.data('caSearchBox');

      if (!search_box) {
        return false;
      }

      search_box.addListener('places_changed', function () {
        var places = search_box.getPlaces();

        var data = self._normalizeSearchResult(places);

        self._fireEvent($container, 'ce:geomap:search_result_select', [data]);
      });
    },
    _normalizeSearchResult: function _normalizeSearchResult(result) {
      if (!result.length) {
        return false;
      }

      var location = result[0].geometry.location,
          coords = location.toJSON();
      return coords;
    },
    _fireEvent: function _fireEvent($container, name, data) {
      data = data || [];
      $container.trigger(name, data);
      data.unshift($container);
      $.ceEvent('trigger', name, data);
    },
    _registerMapClickEvent: function _registerMapClickEvent($container) {
      var self = google_map,
          map = self._getGeoMap($container);

      if (!map) {
        return false;
      }

      google.maps.event.addListener(map, 'click', function (result) {
        var data = self._normalizeClickResult(result);

        self._fireEvent($container, 'ce:geomap:click', [data]);
      });
      return true;
    },
    _normalizeClickResult: function _normalizeClickResult(result) {
      var coords = result.latLng.toJSON();
      return coords;
    },
    resize: function resize() {
      return true;
    },
    destroy: function destroy() {
      return true;
    },
    removeAllMarkers: function removeAllMarkers() {
      var self = google_map,
          $container = $(this),
          clusterer = self._getClusterer($container);

      if (!clusterer) {
        return false;
      }

      clusterer.removeMarkers(clusterer.getMarkers());
      clusterer.clearMarkers();
      return true;
    },
    addMarkers: function addMarkers(markers) {
      var self = google_map;

      self._addMarkersToCluster($(this), markers);
    },
    adjustMapBoundariesToSeeAllMarkers: function adjustMapBoundariesToSeeAllMarkers() {
      var self = google_map,
          $container = $(this),
          clusterer = self._getClusterer($container);

      if (!clusterer) {
        return false;
      }

      var markers_bounds = new google.maps.LatLngBounds();
      $.each(clusterer.getMarkers(), function (index, marker) {
        markers_bounds.extend(marker.position);
      });
      clusterer.map.setCenter(markers_bounds.getCenter());
      clusterer.map.fitBounds(markers_bounds);
      return true;
    },
    setCenter: function setCenter(lat, lng, zoom) {
      var self = google_map,
          $container = $(this),
          map = self._getGeoMap($container);

      if (!map) {
        return false;
      }

      var position = new google.maps.LatLng(lat, lng),
          markers_bounds = new google.maps.LatLngBounds();
      markers_bounds.extend(position);
      map.setCenter(markers_bounds.getCenter());
      map.setZoom(parseInt(zoom) || self.default_zoom);
      return true;
    },
    getCenter: function getCenter() {
      var self = google_map,
          $container = $(this),
          map = self._getGeoMap($container);

      if (!map) {
        return {};
      }

      var coords = map.getCenter();
      return coords.toJSON();
    },
    exitFullscreen: function exitFullscreen() {
      var self = google_map,
          $container = $(this),
          map = self._getGeoMap($container);

      if (map) {
        var methods = ['exitFullscreen', 'webkitExitFullscreen', 'mozCancelFullScreen', 'msExitFullscreen'];

        for (var index in methods) {
          if (methods[index] in window.document) {
            window.document[methods[index]]();
            return true;
          }
        }
      }

      return false;
    }
  };
  $.ceGeoMap('setHandlers', google_map);
})(Tygh, Tygh.$);