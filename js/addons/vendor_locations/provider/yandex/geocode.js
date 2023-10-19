(function (_, $) {
  var methods = {
    getCoords: function getCoords(location) {
      var d = $.Deferred(),
          self = methods;
      $.vendorLocationsInitYandexApi().done(function () {
        ymaps.geocode(location).then(function (response) {
          var data = self._normalizeGeoCodeResponse(response);

          d.resolve(data);
        });
      }).fail(function () {// TODO
      });
      return d.promise();
    },
    _normalizeGeoCodeResponse: function _normalizeGeoCodeResponse(res) {
      var coords = res.geoObjects.get(0).geometry.getCoordinates();
      return {
        lat: coords[0],
        lng: coords[1]
      };
    }
  };

  $.ceGeocode = function (method) {
    if (methods[method]) {
      return methods[method].apply(this, Array.prototype.slice.call(arguments, 1));
    } else {
      $.error('ty.geoCode: method ' + method + ' does not exist');
    }
  };
})(Tygh, Tygh.$);