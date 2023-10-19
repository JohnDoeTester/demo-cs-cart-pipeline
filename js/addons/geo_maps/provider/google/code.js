(function (_, $) {
  var geocode = {
    getCoords: function getCoords(location) {
      var d = $.Deferred(),
          self = geocode;
      $.geoMapInitGoogleApi({}).done(function () {
        var geocoder = new google.maps.Geocoder();
        geocoder.geocode({
          'address': location
        }, function (response, status) {
          if (status === google.maps.GeocoderStatus.OK) {
            var data = self._normalizeGeoCodeResponse(response);

            d.resolve(data);
          } else {
            d.reject();
          }
        });
      }).fail(function () {// TODO
      });
      return d.promise();
    },
    _normalizeGeoCodeResponse: function _normalizeGeoCodeResponse(res) {
      if (!res.length) {
        return {};
      }

      var coords = res[0].geometry.location.toJSON();
      return coords;
    }
  };
  $.ceGeoCode('setHandlers', geocode);
})(Tygh, Tygh.$);