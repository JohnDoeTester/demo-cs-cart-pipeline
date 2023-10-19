function _typeof(obj) { if (typeof Symbol === "function" && typeof Symbol.iterator === "symbol") { _typeof = function _typeof(obj) { return typeof obj; }; } else { _typeof = function _typeof(obj) { return obj && typeof Symbol === "function" && obj.constructor === Symbol && obj !== Symbol.prototype ? "symbol" : typeof obj; }; } return _typeof(obj); }

(function (_, $) {
  var methods = {
    init: function init() {
      var $elems = $(this);

      if (!window.google && !$.fn.geocomplete) {
        $.ceGeolocate('loadMapApi').done(function () {
          $.getScript('js/addons/vendor_locations/lib/geocomplete/jquery.geocomplete.min.js', function () {
            methods._init($elems);
          });
        });
      } else {
        methods._init($elems);
      }

      return $elems;
    },
    setElementLocation: function setElementLocation(location) {
      methods._setElementLocation(location, $(this));
    },
    _init: function _init($elems) {
      return $elems.each(function () {
        var $elem = $(this),
            type = $elem.data('caGeocompleteType') || 'geocode',
            country = $elem.data('caGeocompleteCountry') || _.vendor_locations.country,
            place_id = $elem.data('caGeocompletePlaceId');

        $elem.geocomplete({
          types: [type],
          country: country
        }).on('geocode:result', function (event, result) {
          var location = $.ceGeolocate('convertPlaceToLocation', result);
          $.ceGeolocate('loadNormalizedLocationData', location).done(function (normalized_location) {
            $.ceGeolocate('saveLocationToLocalStorage', normalized_location.place_id, normalized_location);

            methods._setElementLocation(normalized_location, $elem);
          }).fail(function () {// TODO
          });
        }).on('change', function (event) {
          if (!$elem.val()) {
            var $value_elem = $('#' + $elem.data('caGeocompleteValueElemId'));
            $value_elem.prop("disabled", false);
          }
        });

        if (place_id) {
          var location = $.ceGeolocate('getLocationFromLocalStorage', place_id);

          if (location) {
            $elem.val(location.formatted_address);
            $elem[0].defaultValue = location.formatted_address;
          } else {
            $.ceGeolocate('loadLocationDataByPlaceId', place_id).done(function (location) {
              $.ceGeolocate('loadNormalizedLocationData', location).done(function (normalized_location) {
                $.ceGeolocate('saveLocationToLocalStorage', normalized_location.place_id, normalized_location);
              });
              $elem.val(location.formatted_address);
              $elem[0].defaultValue = location.formatted_address;
            }).fail(function () {// TODO
            });
          }
        }
      });
    },
    _setElementLocation: function _setElementLocation(location, $elem) {
      var $value_elem = $('#' + $elem.data('caGeocompleteValueElemId'));

      if ($value_elem.length) {
        $value_elem.prop("disabled", false);
        $value_elem.val(JSON.stringify(location));
      }

      $elem.val(location.formatted_address).data('caLocation', location).trigger('ce.geocomplete.select', location);
      $.ceEvent('trigger', 'ce.geocomplete.select', [$elem, location, location]);
    }
  };

  $.fn.ceGeocomplete = function (method) {
    if (methods[method]) {
      return methods[method].apply(this, Array.prototype.slice.call(arguments, 1));
    } else if (_typeof(method) === 'object' || !method) {
      return methods.init.apply(this, arguments);
    } else {
      $.error('ty.geocomplete: method ' + method + ' does not exist');
    }
  };
})(Tygh, Tygh.$);