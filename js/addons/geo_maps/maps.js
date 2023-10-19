function _typeof(obj) { if (typeof Symbol === "function" && typeof Symbol.iterator === "symbol") { _typeof = function _typeof(obj) { return typeof obj; }; } else { _typeof = function _typeof(obj) { return obj && typeof Symbol === "function" && obj.constructor === Symbol && obj !== Symbol.prototype ? "symbol" : typeof obj; }; } return _typeof(obj); }

(function (_, $) {
  var handlers = {
    init: function init() {
      return true;
    },
    setCenter: function setCenter(lat, lng, zoom) {
      return true;
    },
    getCenter: function getCenter() {
      return {};
    },
    removeAllMarkers: function removeAllMarkers() {
      return true;
    },
    resize: function resize() {
      return true;
    },
    destroy: function destroy() {
      return true;
    },
    addMarkers: function addMarkers() {
      return true;
    },
    adjustMapBoundariesToSeeAllMarkers: function adjustMapBoundariesToSeeAllMarkers() {
      return true;
    },
    exitFullscreen: function exitFullscreen() {
      return true;
    }
  };
  var methods = {
    prepareMarkers: function prepareMarkers(marker_selector) {
      var markers = [];
      $(marker_selector).each(function (index, marker) {
        var $marker = $(marker);
        markers.push({
          lat: $marker.data('caGeoMapMarkerLat'),
          lng: $marker.data('caGeoMapMarkerLng'),
          selected: !!$marker.data('caGeoMapMarkerSelected'),
          content: $marker.html(),
          static: !!$marker.data('caGeoMapMarkerStatic'),
          header: $marker.data('caGeoMapMarkerHeader')
        });
      });
      return markers;
    },
    setHandlers: function setHandlers(data) {
      handlers = data;
    }
  };

  $.fn.ceGeoMap = function (method) {
    if (handlers[method]) {
      return handlers[method].apply(this, Array.prototype.slice.call(arguments, 1));
    } else if (_typeof(method) === 'object' || !method) {
      return handlers.init.apply(this, arguments);
    } else {
      $.error('ty.geoMap: method ' + method + ' does not exist');
    }
  };

  $.ceGeoMap = function (action, data) {
    if (methods[action]) {
      return methods[action].apply(this, Array.prototype.slice.call(arguments, 1));
    } else {
      $.error('ty.geoMap: action ' + action + ' does not exist');
    }
  };
})(Tygh, Tygh.$);