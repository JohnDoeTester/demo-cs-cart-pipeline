(function (_, $) {
  var methods = {
    setHandlers: function setHandlers(data) {
      handlers = data;
    }
  };
  var handlers = {
    getCurrentLocation: function getCurrentLocation() {
      return $.Deferred().reject().promise();
    },
    getLocationByCoords: function getLocationByCoords(lat, lng) {
      return $.Deferred().reject().promise();
    },
    getProviderCode: function getProviderCode() {
      return 'default';
    },
    getLanguageCode: function getLanguageCode() {
      return _.geo_maps.language;
    }
  };
  var caching_decorator = {
    getCurrentLocation: function getCurrentLocation() {
      var location_key = 'geo_maps_customer_location_' + handlers.getProviderCode() + '_' + handlers.getLanguageCode(),
          location = caching_decorator.getFromLocalSession(location_key),
          d = $.Deferred();

      if (!location) {
        handlers.getCurrentLocation().then(function (location) {
          caching_decorator.saveToLocalSession(location_key, location);
          d.resolve(location);
        });
      } else {
        d.resolve(location);
      }

      return d.promise();
    },
    getLocationByCoords: function getLocationByCoords(lat, lng) {
      var location_key = ['geo_maps_coords_location', handlers.getProviderCode(), lat, lng, handlers.getLanguageCode()].join('_'),
          location = caching_decorator.getFromLocalSession(location_key),
          d = $.Deferred();

      if (!location) {
        handlers.getLocationByCoords(lat, lng).then(function (location) {
          caching_decorator.saveToLocalSession(location_key, location);
          d.resolve(location);
        }, d.reject);
      } else {
        d.resolve(location);
      }

      return d.promise();
    },
    saveToLocalSession: function saveToLocalSession(key, value) {
      try {
        sessionStorage.setItem(key, JSON.stringify(value));
      } catch (e) {}
    },
    getFromLocalSession: function getFromLocalSession(key) {
      try {
        var value = sessionStorage.getItem(key);

        if (value) {
          return JSON.parse(value);
        }
      } catch (e) {}

      return false;
    }
  };

  $.ceGeoLocate = function (method) {
    if (methods[method]) {
      return methods[method].apply(this, Array.prototype.slice.call(arguments, 1));
    } else if (caching_decorator[method]) {
      return caching_decorator[method].apply(this, Array.prototype.slice.call(arguments, 1));
    } else if (handlers[method]) {
      return handlers[method].apply(this, Array.prototype.slice.call(arguments, 1));
    } else {
      $.error('ty.geoLocate: method ' + method + ' does not exist');
    }
  };
})(Tygh, Tygh.$);