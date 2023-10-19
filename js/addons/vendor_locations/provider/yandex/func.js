(function (_, $) {
  $.ceEvent('on', 'ce.commoninit', function (context) {
    var $mapContainers = $('[data-ca-vendor-locations="vendorsMap"]', context);

    if (_.area === 'C') {
      var $elems = context.find('.cm-geocomplete');

      if ($elems.length) {
        $elems.ceGeocomplete();
      }

      initCurrentLocation(context);
      initVendorsFilter(context);
    }

    if (!$mapContainers.length) {
      return;
    }

    fnInitMaps(context);
  });
  $.ceEvent('on', 'ce.formpost_geolocation_form', function () {
    return false;
  });

  function saveCustomerLocation(location, locality, hidden) {
    var resultIds = [];
    $('.cm-reload-on-geolocation-change', _.doc).each(function () {
      var $elem = $(this),
          id = $elem.prop('id');

      if (id) {
        resultIds.push(id);
      }
    });
    $.ceAjax('request', fn_url('vendor_locations.set_geolocation'), {
      method: 'post',
      hidden: hidden !== false,
      data: {
        location: location,
        locality: locality,
        result_ids: resultIds.join(','),
        full_render: true,
        redirect_url: _.current_url
      },
      callback: function callback(data) {
        $('.cm-geolocation-current-location').text(data.locality);
      }
    });
  }

  function initCurrentLocation(context) {
    var $currentLocationElems = context.find('.cm-geolocation-current-location'),
        $searchCurrentLocationElems = context.find('.cm-geolocation-search-current-location'),
        $selectCurrentLocationElems = context.find('.cm-geolocation-select-current-location');
    $selectCurrentLocationElems.closest('form').ceFormValidator('setClicked', $selectCurrentLocationElems);

    if ($currentLocationElems.length && !$currentLocationElems.hasClass('location-selected')) {
      $.ceGeolocate('getCurrentLocation').done(function (location, locality) {
        saveCustomerLocation(location, locality);
      }).fail(function () {//TODO
      });
    }

    if ($searchCurrentLocationElems.length) {
      $searchCurrentLocationElems.on('ce.geocomplete.select', function (e, location) {
        var $elem = $(this);
        $.ceGeolocate('identifyCurrentLocality', location).then(function (locality) {
          $elem.data('caLocality', locality);
        }).fail(function () {//TODO
        });
      });
    }

    if ($selectCurrentLocationElems.length) {
      $selectCurrentLocationElems.on('click', function () {
        var $form = $selectCurrentLocationElems.closest('form'),
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

    var $useMyLocationButton = context.find('.cm-filter-geolocation-use-my-location-button');

    if ($useMyLocationButton.length) {
      $useMyLocationButton.on('click', function (event) {
        var $elem = $(this),
            $input = $('#' + $elem.data('caFilterGeocompleteElemId')),
            filterType = $input.data('caFilterType');
        $.ceGeolocate('getCurrentLocation').done(function (location, locality) {
          if (filterType === 'region') {
            $input.ceGeocomplete('setElementLocation', locality);
          } else {
            $input.ceGeocomplete('setElementLocation', location);
          }
        }).fail(function () {// TODO
        });
      });
    }
  }

  function fnInitMaps(context) {
    $(context).find('[data-ca-vendor-locations="vendorsMap"]').each(function (index, container) {
      var $container = $(container),
          markerSelector = $container.data('caGeomapMarkerSelector');
      var options = {
        initial_lat: $container.data('caGeoMapInitialLat'),
        initial_lng: $container.data('caGeoMapInitialLng'),
        zoom: $container.data('caGeoMapZoom'),
        language: $container.data('caGeoMapLanguage'),
        controls: {
          enable_zoom: true
        }
      };
      options.markers = $.ceGeomap('prepareMarkers', markerSelector);
      $container.ceGeomap(options);
    });
  }
})(Tygh, Tygh.$);