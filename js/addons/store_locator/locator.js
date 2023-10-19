(function (_, $) {
  var methods = {
    setLocation: function setLocation(location, $container) {
      location.state = location.state_code;
      location.locality_text = location.locality;
      $.ceGeoMapLocation('setLocation', location, $container);
    },

    /**
     * @param {jQuery} $elm
     */
    initCitySelector: function initCitySelector($elm) {
      $elm.on('click touch', function (e) {
        var $parent_container = $(this).closest('[id^=geo_maps_location_block_]');
        e.preventDefault();
        methods.setLocation({
          country: $elm.data('caStoreLocatorLocationCountry') || '',
          country_text: $elm.data('caStoreLocatorLocationCountryName') || '',
          state_code: $elm.data('caStoreLocatorLocationState') || '',
          state_text: $elm.data('caStoreLocatorLocationStateName') || '',
          locality: $elm.data('caStoreLocatorLocationCity') || ''
        }, $parent_container);
      });
    }
  };
  $.extend({
    ceStoreLocatorLocation: function ceStoreLocatorLocation(method) {
      if (methods[method]) {
        return methods[method].apply(this, Array.prototype.slice.call(arguments, 1));
      } else {
        $.error('ty.store-locator-location: method ' + method + ' does not exist');
      }
    }
  });
  $.ceEvent('on', 'ce.commoninit', function (context) {
    var city_selectors = $('[data-ca-store-locator-location-element="city"]', context);

    if (city_selectors.length) {
      city_selectors.each(function (i, elm) {
        $.ceStoreLocatorLocation('initCitySelector', $(elm));
      });
    }
  });
})(Tygh, Tygh.$);