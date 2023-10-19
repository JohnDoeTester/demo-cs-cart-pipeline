(function (_, $) {
  var geolocate = {
    getCurrentLocation: function getCurrentLocation() {
      return geolocate._getCurrentPosition().then(geolocate.getLocationByCoords);
    },
    _getCurrentPosition: function _getCurrentPosition() {
      return geolocate._detectCurrentPosition().then(geolocate._extractCoordinatesFromGeoObject);
    },
    _detectCurrentPosition: function _detectCurrentPosition() {
      var d = $.Deferred();
      $.geoMapInitYandexApi().done(function () {
        geo_maps_yandex.geolocation.get({
          provider: location.protocol === 'https:' ? 'auto' : 'yandex'
        }).then(function (result) {
          d.resolve(result.geoObjects.get(0));
        }, function () {
          d.reject();
        });
      });
      return d.promise();
    },
    _extractCoordinatesFromGeoObject: function _extractCoordinatesFromGeoObject(geo_object) {
      var coords = geo_object.geometry.getCoordinates();
      return $.Deferred().resolve(coords[0], coords[1]).promise();
    },
    getLocationByCoords: function getLocationByCoords(lat, lng) {
      var self = geolocate,
          d = $.Deferred();
      geo_maps_yandex.geocode([lat, lng]).then(d.resolve).fail(d.reject);
      return d.then(self._extractLocationFromGeocodeResponse).then(self._getStateCode).promise();
    },
    _extractLocationFromGeocodeResponse: function _extractLocationFromGeocodeResponse(res) {
      var geo_object = res.geoObjects.get(0),
          meta = geo_object.properties.get('metaDataProperty').GeocoderMetaData,
          coords = geo_object.geometry.getCoordinates(),
          location = {
        place_id: meta.id,
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
            location.locality = location.locality_text = !location.locality ? component.name : location.locality;
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
      return $.Deferred().resolve(location).promise();
    },
    _getStateCode: function _getStateCode(location) {
      var self = geolocate,
          d = $.Deferred(),
          options = {
        quality: 0
      };
      geo_maps_yandex.borders.load(location.country, options).then(function (geojson) {
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
    getProviderCode: function getProviderCode() {
      return 'yandex';
    },
    getLanguageCode: function getLanguageCode() {
      var geo_maps_yandex = geo_maps_yandex || null;
      return geo_maps_yandex && geo_maps_yandex.meta && geo_maps_yandex.meta.languageCode || _.geo_maps.language;
    }
  };
  $.ceGeoLocate('setHandlers', geolocate);
})(Tygh, Tygh.$);