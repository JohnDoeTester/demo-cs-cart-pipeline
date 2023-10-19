(function (_, $) {
  var methods = {
    defaultLangCode: null,
    identifyCurrentLocation: function identifyCurrentLocation() {
      return methods.identifyCurrentPositionByBrowser().then(null, methods.identifyCurrentPositionByApi).then(methods.loadLocationDataByLatLng);
    },
    identifyCurrentLocality: function identifyCurrentLocality(location) {
      var d = $.Deferred();
      $.vendorLocationsInitYandexApi().done(function () {
        ymaps.geocode(location.locality).then(function (results) {
          var location = methods._extractLocation(results.geoObjects.get(0));

          return d.resolve(location);
        });
      });
      return d.promise();
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
      var d = $.Deferred();
      $.vendorLocationsInitYandexApi().done(function () {
        ymaps.geolocation.get().then(function (result) {
          var coords = result.geoObjects['position'];
          return d.resolve(coords[0], coords[1]);
        });
      });
      return d.promise();
    },
    loadLocationDataByLatLng: function loadLocationDataByLatLng(lat, lng) {
      var d = $.Deferred();
      $.vendorLocationsInitYandexApi().done(function () {
        ymaps.geocode([lat, lng]).then(function (results) {
          var location = methods._extractLocation(results.geoObjects.get(0));

          return d.resolve(location);
        });
      });
      return d.promise();
    },
    loadMapApiEn: function loadMapApiEn() {
      var commercialApiUrl = 'https://enterprise.api-maps.yandex.ru/',
          freeApiUrl = 'https://api-maps.yandex.ru/',
          url = freeApiUrl,
          apiVersion = '2.1',
          d = $.Deferred();

      if (_.vendor_locations.yandex_commercial) {
        url = commercialApiUrl;
      }

      url += apiVersion + '?lang=en_US&ns=ymaps_en&onload=$.ceVendorLocationsOnLoadYandexGeolocate&apikey=' + _.vendor_locations.api_key;
      $.getScript(url).then(function () {
        ymaps_en.ready(function () {
          d.resolve();
        });
      });
      return d.promise();
    },
    geocode: function geocode(request, options) {
      var d = $.Deferred();
      methods.loadMapApiEn().done(function () {
        ymaps_en.geocode(request, options).then(function (res) {
          d.resolve(res);
        }, function (err) {
          d.reject();
        });
      });
      return d.promise();
    },
    getGeoObjectLocation: function getGeoObjectLocation(object) {
      return methods._extractLocation(object);
    },
    _extractLocation: function _extractLocation(geoObject) {
      var meta = geoObject.properties.get('metaDataProperty').GeocoderMetaData,
          coords = geoObject.geometry.getCoordinates(),
          location = {
        place_id: (coords[0].toString() + coords[1].toString()).replace(/\./g, ''),
        lat: coords[0],
        lng: coords[1],
        formatted_address: meta.Address.formatted,
        type: meta.kind,
        country: meta.Address.country_code,
        postal_code: meta.Address.postal_code,
        postal_code_text: meta.Address.postal_code
      };
      $.each(meta.Address.Components, function (index, component) {
        switch (component.kind) {
          case 'country':
            location.country_text = component.name;
            break;

          case 'province':
            location.state = location.state_text = component.name;
            location.locality = location.locality_text = component.name;
            break;

          case 'district':
            location.locality = location.locality_text = !location.locality ? component.name : location.locality;
            break;

          case 'locality':
            location.locality = location.locality_text = component.name;
            break;

          case 'area':
            location.locality = location.locality_text = !location.locality ? component.name : location.locality;
            break;

          case 'street':
            location.route = location.route_text = component.name;
            break;

          case 'house':
            location.street_number = location.street_number_text = component.name;
            break;
        }
      });
      return location;
    },
    loadNormalizedLocationData: function loadNormalizedLocationData(location) {
      return methods.geocode([location.lat, location.lng], {}).then(function (results) {
        var geo_object = results.geoObjects.get(0);
        return methods._normalizeLocation(methods.getGeoObjectLocation(geo_object), location);
      });
    },
    _normalizeLocation: function _normalizeLocation(normalized_location, location) {
      if (normalized_location.country) {
        location.country = normalized_location.country;
        location.country_text = location.country_text || normalized_location.country_text;
      }

      if (normalized_location.state) {
        location.state = normalized_location.state;
        location.state_text = location.state_text || normalized_location.state_text;
      }

      if (normalized_location.locality) {
        location.locality = normalized_location.locality;
        location.locality_text = location.locality_text || normalized_location.locality_text;
      }

      if (location.route && normalized_location.route) {
        location.route = normalized_location.route;
        location.route_text = location.route_text || normalized_location.route_text;
      }

      if (location.street_number && normalized_location.street_number) {
        location.street_number = normalized_location.street_number;
        location.street_number_text = location.street_number_text || normalized_location.street_number_text;
      }

      return location;
    },
    _getStateCode: function _getStateCode(location) {
      var self = methods,
          d = $.Deferred(),
          options = {
        quality: 0
      };
      ymaps.borders.load(location.country, options).then(function (geojson) {
        location.state_code = self._getStateCodeFromResponse(geojson, location.state_text);
        d.resolve(location);
      }, function () {
        location.state_code = '';
        d.resolve(location);
      });
      return d.promise();
    },
    _getStateCodeFromResponse: function _getStateCodeFromResponse(geojson, state) {
      var state_code = '';

      for (var i = 0; i < geojson.features.length; i++) {
        var region = geojson.features[i].properties; // HOTFIX: YMaps JS API bug fix, remove this when borders.load starts returning name-field such as location stateName-field

        var state_name_equals = 'Республика ' + region.name === state;

        if (region.name === state || state_name_equals) {
          state_code = region.iso3166.split('-').pop();
          break;
        }
      }

      return state_code;
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
    base64encode: function base64encode(string) {
      return window.btoa(unescape(encodeURIComponent(string)));
    }
  };

  $.ceVendorLocationsOnLoadYandexGeolocate = function () {
    $.ceEvent('trigger', 'ce:vendor_locations:onload', ['yandex', 'geolocate']);
  };

  $.ceGeolocate = function (method) {
    if (methods[method]) {
      return methods[method].apply(this, Array.prototype.slice.call(arguments, 1));
    } else {
      $.error('ty.geolocate: method ' + method + ' does not exist');
    }
  };
})(Tygh, Tygh.$);