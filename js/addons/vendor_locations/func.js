(function (_, $) {
  $.ceEvent('on', 'ce.commoninit', function (context) {
    initVendorLocations(context);
  });
  $.ceEvent('on', 'ce.gdpr_cookie_init', function (context) {
    initVendorLocations(context);
  });
  $.ceEvent('on', 'ce.formpost_geolocation_form', function () {
    return false;
  });

  function initVendorLocations(context) {
    var $elems = context.find('.cm-geocomplete');

    if ($elems.length) {
      $elems.ceGeocomplete();
    }

    if (_.area === 'C') {
      initCurrentLocation(context);
      initVendorsFilter(context);
    }

    initVendorsMap(context);
  }

  function saveCustomerLocation(location, locality, hidden) {
    var result_ids = [];
    $('.cm-reload-on-geolocation-change', _.doc).each(function () {
      var $elem = $(this),
          id = $elem.prop('id');

      if (id) {
        result_ids.push(id);
      }
    });
    $.ceAjax('request', fn_url('vendor_locations.set_geolocation'), {
      method: 'post',
      hidden: hidden !== false,
      data: {
        location: location,
        locality: locality,
        result_ids: result_ids.join(','),
        full_render: true,
        redirect_url: _.current_url
      },
      callback: function callback(data) {
        $('.cm-geolocation-current-location').text(data.locality);
      }
    });
  }

  function initCurrentLocation(context) {
    var $current_location_elems = context.find('.cm-geolocation-current-location'),
        $search_current_location_elems = context.find('.cm-geolocation-search-current-location'),
        $select_current_location_elems = context.find('.cm-geolocation-select-current-location');
    $select_current_location_elems.closest('form').ceFormValidator('setClicked', $select_current_location_elems);

    if ($current_location_elems.length && !$current_location_elems.hasClass('location-selected')) {
      $.ceGeolocate('getCurrentLocation').done(function (location, locality) {
        saveCustomerLocation(location, locality);
      }).fail(function () {//TODO
      });
    }

    if ($search_current_location_elems.length) {
      $search_current_location_elems.on('ce.geocomplete.select', function (e, location) {
        var $elem = $(this);
        $.ceGeolocate('identifyCurrentLocality', location).then(function (locality) {
          $elem.data('caLocality', locality);
        }).fail(function () {//TODO
        });
      });
    }

    if ($select_current_location_elems.length) {
      $select_current_location_elems.on('click', function () {
        var $form = $select_current_location_elems.closest('form'),
            $input = $form.find('.cm-geolocation-search-current-location'),
            location = $input.data('caLocation'),
            locality = $input.data('caLocality');

        if (location && locality) {
          $.ceGeolocate('setCurrentLocation', location, locality);
          saveCustomerLocation(location, locality, false);
        }

        $.ceDialog('get_last').ceDialog('close');
      });
    }
  }

  function initVendorsMap(context) {
    var $maps = context.find('[data-ca-vendor-locations="vendorsMap"]');

    if ($maps.length) {
      $maps.each(function () {
        $(this).ceGeomap();
      });
    }
  }

  function initVendorsFilter(context) {
    var $filter = context.find('.cm-filter-vendor-by-geolocation-input');

    if ($filter.length) {
      $filter.on('ce.geocomplete.select', function (event, location) {
        var $value_elem = $('#' + $filter.data('caGeocompleteValueElemId')),
            $form = $filter.closest('form');
        $value_elem.val($.ceGeolocate('base64encode', [location.place_id, location.country, null, location.locality].join('|')));
        $form.trigger('submit');
      });
    }

    var $use_my_location_button = context.find('.cm-filter-geolocation-use-my-location-button');

    if ($use_my_location_button.length) {
      $use_my_location_button.on('click', function (event) {
        var $elem = $(this),
            $input = $('#' + $elem.data('caFilterGeocompleteElemId')),
            filter_type = $input.data('caFilterType');
        $.ceGeolocate('getCurrentLocation').done(function (location, locality) {
          if (filter_type === 'region') {
            $input.ceGeocomplete('setElementLocation', locality);
          } else {
            $input.ceGeocomplete('setElementLocation', location);
          }
        }).fail(function () {// TODO
        });
      });
    }
  }

  $(document).ready(function () {
    var location = {},
        locality = {};

    if (_.vendor_locations.customer_geolocation && _.vendor_locations.customer_locality) {
      location = JSON.parse(_.vendor_locations.customer_geolocation);
      locality = JSON.parse(_.vendor_locations.customer_locality);
    }

    $.ceGeolocate('setCurrentLocation', location, locality);
  });
})(Tygh, Tygh.$);