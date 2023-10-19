function _typeof(obj) { if (typeof Symbol === "function" && typeof Symbol.iterator === "symbol") { _typeof = function _typeof(obj) { return typeof obj; }; } else { _typeof = function _typeof(obj) { return obj && typeof Symbol === "function" && obj.constructor === Symbol && obj !== Symbol.prototype ? "symbol" : typeof obj; }; } return _typeof(obj); }

(function (_, $) {
  var methods = {
    init: function init() {
      var $elems = $(this);
      $.vendorLocationsInitYandexApi().done(function () {
        methods._init($elems);
      }).fail(function () {//TODO
      });
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
            placeId = $elem.data('caGeocompletePlaceId');

        $elem[0].placeholder = _.tr('enter_location');
        var suggestView = new ymaps.SuggestView($elem[0], {
          results: 5
        });
        suggestView.events.add('select', function (e) {
          var selectValue = e.get('item').value;
          ymaps.geocode(selectValue, {
            results: 1
          }).then(function (res) {
            var geo_object = res.geoObjects.get(0),
                location = $.ceGeolocate('getGeoObjectLocation', geo_object);
            $.ceGeolocate('loadNormalizedLocationData', location).done(function (normalized_location) {
              $.ceGeolocate('saveLocationToLocalStorage', normalized_location.place_id, normalized_location);

              methods._setElementLocation(normalized_location, $elem);
            }).fail(function () {// TODO
            });
          });
          suggestView.destroy();
        });

        if (placeId) {
          var location = $.ceGeolocate('getLocationFromLocalStorage', placeId);

          if (location) {
            $elem.val(location.formatted_address);
            $elem[0].defaultValue = location.formatted_address;
          }
        }
      });
    },
    _setElementLocation: function _setElementLocation(location, $elem) {
      var $valueElem = $('#' + $elem.data('caGeocompleteValueElemId'));

      if ($valueElem.length) {
        $valueElem.prop("disabled", false);
        $valueElem.val(JSON.stringify(location));
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