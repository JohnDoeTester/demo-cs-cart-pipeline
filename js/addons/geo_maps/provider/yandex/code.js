(function (_, $) {
  var yandex = {
    getCoords: function getCoords(location) {
      var d = $.Deferred(),
          self = yandex;
      $.geoMapInitYandexApi().done(function () {
        geo_maps_yandex.geocode(location).then(function (response) {
          var data = self._normalizeGeoCodeResponse(response);

          d.resolve(data);
        });
      }).fail(function () {// TODO
      });
      return d.promise();
    },
    _normalizeGeoCodeResponse: function _normalizeGeoCodeResponse(res) {
      var coords = res.geoObjects.get(0).geometry.getCoordinates();
      var normalized_result = {
        lat: coords[0],
        lng: coords[1]
      };
      return normalized_result;
    }
  };
  $.ceGeoCode('setHandlers', yandex);
})(Tygh, Tygh.$);