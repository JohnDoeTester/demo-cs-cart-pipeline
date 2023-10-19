(function (_, $) {
  var methods = {
    setHandlers: function setHandlers(data) {
      handlers = data;
    }
  };
  var handlers = {
    getCoords: function getCoords(location) {
      var d = $.Deferred();
      d.reject();
      return d.promise();
    }
  };

  $.ceGeoCode = function (method) {
    if (methods[method]) {
      return methods[method].apply(this, Array.prototype.slice.call(arguments, 1));
    } else if (handlers[method]) {
      return handlers[method].apply(this, Array.prototype.slice.call(arguments, 1));
    } else {
      $.error('ty.geoCode: method ' + method + ' does not exist');
    }
  };
})(Tygh, Tygh.$);