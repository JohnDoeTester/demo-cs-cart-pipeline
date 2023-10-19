(function (_, $) {
  var methods = {
    defaultLangCode: null,
    apiInstancesByLangCode: {},
    apiUrl: 'https://maps.googleapis.com/maps/api/geocode/json',
    identifyCurrentLocation: function identifyCurrentLocation() {
      return methods.identifyCurrentPositionByBrowser().then(null, methods.identifyCurrentPositionByApi).then(methods.loadLocationDataByLatLng).then(methods.loadNormalizedLocationData);
    },
    identifyCurrentLocality: function identifyCurrentLocality(location) {
      if (location.locality_place_id) {
        return methods.loadLocationDataByPlaceId(location.locality_place_id).then(methods.loadNormalizedLocationData);
      } else if (location.place_id) {
        return methods.loadLocationDataByPlaceId(location.place_id).then(methods.loadNormalizedLocationData);
      }

      return $.Deferred().reject().promise();
    },
    saveCurrentLocation: function saveCurrentLocation(location) {
      methods.saveToLocalSession('vendor_locations.' + _.vendor_locations.storage_key_geolocation, JSON.stringify(location));
      return location;
    },
    saveCurrentLocality: function saveCurrentLocality(locality) {
      methods.saveToLocalSession('vendor_locations.' + _.vendor_locations.storage_key_locality, JSON.stringify(locality));
      return locality;
    },
    getCurrentLocation: function getCurrentLocation() {
      var location = methods.getFromLocalSession('vendor_locations.' + _.vendor_locations.storage_key_geolocation),
          locality = methods.getFromLocalSession('vendor_locations.' + _.vendor_locations.storage_key_locality),
          d = $.Deferred();

      if (location.place_id && locality.place_id) {
        d.resolve(location, locality);
      } else {
        methods.identifyCurrentLocation().then(function (location) {
          methods.identifyCurrentLocality(location).then(function (locality) {
            methods.setCurrentLocation(location, locality);
            d.resolve(location, locality);
          }).fail(d.reject);
        }).fail(d.reject);
      }

      return d.promise();
    },
    setCurrentLocation: function setCurrentLocation(location, locality) {
      methods.saveCurrentLocation(location);
      methods.saveCurrentLocality(locality);
    },
    saveToLocalSession: function saveToLocalSession(key, value) {
      try {
        sessionStorage.setItem(key, value);
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
    },
    identifyCurrentPositionByBrowser: function identifyCurrentPositionByBrowser() {
      var d = $.Deferred();

      if (navigator.geolocation) {
        navigator.geolocation.getCurrentPosition(function (position) {
          d.resolve(position.coords.latitude, position.coords.longitude);
        }, function (error) {
          d.reject();
        }, {
          maximumAge: 50000,
          timeout: 5000
        });
      } else {
        d.reject();
      }

      return d.promise();
    },
    identifyCurrentPositionByApi: function identifyCurrentPositionByApi() {
      return $.post("https://www.googleapis.com/geolocation/v1/geolocate?key=" + _.vendor_locations.api_key).then(function (data) {
        return $.Deferred().resolve(data.location.lat, data.location.lng).promise();
      });
    },
    saveLocationToLocalStorage: function saveLocationToLocalStorage(place_id, location) {
      try {
        localStorage.setItem('vendor_locations.locations.' + place_id, JSON.stringify(location));
      } catch (e) {}
    },
    getLocationFromLocalStorage: function getLocationFromLocalStorage(place_id) {
      try {
        var value = localStorage.getItem('vendor_locations.locations.' + place_id);

        if (value) {
          return JSON.parse(value);
        }
      } catch (e) {}

      return false;
    },
    convertPlaceToLocation: function convertPlaceToLocation(place) {
      if (typeof place.geometry.location.lat === 'function') {
        place.geometry.location.lat = place.geometry.location.lat();
      }

      if (typeof place.geometry.location.lng === 'function') {
        place.geometry.location.lng = place.geometry.location.lng();
      }

      return methods._mergeLocationResults([place]);
    },
    loadLocationDataByLatLng: function loadLocationDataByLatLng(lat, lng) {
      return methods.geocode({
        location: {
          lat: parseFloat(lat),
          lng: parseFloat(lng)
        }
      }).then(function (results) {
        return methods._mergeLocationResults(results);
      });
    },
    loadLocationDataByPlaceId: function loadLocationDataByPlaceId(place_id) {
      return methods.geocode({
        placeId: place_id
      }).then(function (results) {
        return methods._mergeLocationResults(results);
      });
    },
    loadNormalizedLocationData: function loadNormalizedLocationData(location) {
      var params = {},
          types = null;

      if (location.type === 'country') {
        types = ['country'];
      } else if (location.type === 'administrative_area_level_1') {
        types = ['country', 'state'];
      } else if (location.type === 'locality') {
        types = ['country', 'state', 'locality'];
      }

      if (typeof location.lat === 'function') {
        location.lat = location.lat();
      }

      if (typeof location.lng === 'function') {
        location.lng = location.lng();
      }

      if ($.inArray(location.type, ['country', 'locality', 'administrative_area_level_1']) !== -1) {
        params.placeId = location.place_id;
      } else {
        params.location = {
          lat: parseFloat(location.lat),
          lng: parseFloat(location.lng)
        };
      }

      return methods.geocode(params, 'en').then(function (results) {
        var result = methods._normalizeLocation(methods._mergeLocationResults(results, types), location);

        if (result.type !== 'locality') {
          var locality = methods._extractByType(results, 'locality');

          result.locality_place_id = locality.place_id;
          result.locality = locality.locality;
          result.locality_text = locality.locality_text;
        }

        if (result.type !== 'country') {
          var country = methods._extractByType(results, 'country');

          result.country_place_id = country.place_id;
        }

        return result;
      });
    },
    base64encode: function base64encode(string) {
      return window.btoa(unescape(encodeURIComponent(string)));
    },
    loadMapApi: function loadMapApi(lang_code) {
      lang_code = lang_code || methods.defaultLangCode;
      var url = 'https://maps.googleapis.com/maps/api/js?key=' + _.vendor_locations.api_key + '&libraries=places&callback=$.ceVendorLocationsOnLoadGoogleGeolocate',
          key = lang_code || 'default',
          d = $.Deferred();

      if (methods.apiInstancesByLangCode[key]) {
        window.google = methods.apiInstancesByLangCode[key];
        d.resolve();
      } else {
        delete window.google;

        if (lang_code) {
          url += "&language=" + lang_code;
        }

        $.getScript(url).then(function () {
          methods.apiInstancesByLangCode[key] = window.google;
          d.resolve();
        });
      }

      return d.promise();
    },
    geocode: function geocode(params, lang_code) {
      var d = $.Deferred();
      lang_code = lang_code || methods.defaultLangCode;
      methods.loadMapApi(lang_code).then(function () {
        var geocoder = new google.maps.Geocoder();
        geocoder.geocode(params, function (results, status) {
          if (status === 'OK') {
            d.resolve(results);
          } else {
            d.reject();
          }
        });
      });
      d.done(function () {
        if (lang_code !== methods.defaultLangCode) {
          methods.loadMapApi(methods.defaultLangCode);
        }
      });
      return d.promise();
    },
    _extractByType: function _extractByType(locations, type) {
      var location = $(locations).filter(function (key, location) {
        return location.types && location.types[0] === type;
      });

      if (location.length) {
        return methods._mergeLocationResults(location);
      }

      return {};
    },
    _mergeLocationResults: function _mergeLocationResults(results, types) {
      var result = {
        place_id: null,
        lat: null,
        lng: null,
        formatted_address: null,
        type: null
      };
      types = types || ['country', 'state', 'locality', 'route', 'postal_code', 'street_number'];
      $.each(results, function (key, item) {
        if (!result.place_id) {
          result.place_id = item.place_id;
          result.formatted_address = item.formatted_address;
          result.type = item.types[0];
          result.lat = item.geometry.location.lat;
          result.lng = item.geometry.location.lng;
          result.viewport = {
            north_east: {
              lat: item.geometry.viewport.getNorthEast().lat(),
              lng: item.geometry.viewport.getNorthEast().lng()
            },
            south_west: {
              lat: item.geometry.viewport.getSouthWest().lat(),
              lng: item.geometry.viewport.getSouthWest().lng()
            }
          };
        }

        result = $.extend(result, methods._retrieveLocationComponents(item.address_components, types));
      });
      return result;
    },
    _retrieveLocationComponents: function _retrieveLocationComponents(components, types) {
      var result = {},
          map = {
        country: 'country',
        administrative_area_level_1: 'state',
        locality: 'locality',
        route: 'route',
        postal_code: 'postal_code',
        street_number: 'street_number'
      };
      $.each(components, function (key, component) {
        var type = component.types[0];

        if (map[type]) {
          type = map[type];
        }

        if ($.inArray(type, types) !== -1) {
          result[type] = component.short_name;
          result[type + '_text'] = component.long_name;
        }
      });
      return result;
    },
    _normalizeLocation: function _normalizeLocation(normalized_location, location) {
      if (normalized_location.country) {
        location.country = methods._normalizeLocationCode(normalized_location.country);
        location.country_text = normalized_location.country_text;
      }

      if (normalized_location.state) {
        location.state = methods._normalizeLocationCode(normalized_location.state);
        location.state_text = normalized_location.state_text;
      }

      if (normalized_location.locality) {
        location.locality = normalized_location.locality;
        location.locality_text = normalized_location.locality_text;
      }

      if (normalized_location.route) {
        location.route = normalized_location.route;
        location.route_text = normalized_location.route_text;
      }

      if (normalized_location.postal_code) {
        location.postal_code = normalized_location.postal_code;
        location.postal_code_text = normalized_location.postal_code_text;
      }

      if (normalized_location.street_number) {
        location.street_number = normalized_location.street_number;
        location.street_number_text = normalized_location.street_number_text;
      }

      if (normalized_location.formatted_address) {
        location.formatted_address = normalized_location.formatted_address;
      }

      return location;
    },
    _normalizeLocationCode: function _normalizeLocationCode(code) {
      return $.trim(code.replace(/[\s]/g, '_')).toUpperCase();
    }
  };

  $.ceVendorLocationsOnLoadGoogleGeolocate = function () {
    $.ceEvent('trigger', 'ce:vendor_locations:onload', ['google', 'geolocate']);
  };

  $.ceGeolocate = function (method) {
    if (methods[method]) {
      return methods[method].apply(this, Array.prototype.slice.call(arguments, 1));
    } else {
      $.error('ty.geolocate: method ' + method + ' does not exist');
    }
  };
})(Tygh, Tygh.$);