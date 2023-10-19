(function (_, $) {
  var api_url = 'https://maps.googleapis.com/maps/api/js',
      clusterer_url = 'js/addons/geo_maps/provider/google/lib/markerclusterer/markerclusterer.js';

  var fn_get_google_api_loader = function fn_get_google_api_loader() {
    var d = $.Deferred(),
        loading_started = false,
        loading_failed = false,
        google_api_initialized = false;
    return function (options) {
      options = $.extend(options || {}, _.geo_maps);

      if (!options.api_key) {
        return d.reject().promise();
      }

      if (google_api_initialized || loading_started || loading_failed) {
        return d.promise();
      }

      loading_started = true;
      var url = fn_generate_api_url(options);
      $.getScript(url).then(function () {
        $.getScript(clusterer_url).then(function () {
          google_api_initialized = true;

          if ($.browser.msie) {
            fn_apply_ie_resizing_bug_workaround();
          }

          d.resolve();
          clearTimeout(await_timeout);
        }).fail(function () {
          loading_failed = true;
          d.reject();
        });
      }).fail(function () {
        loading_failed = true;
        d.reject();
      }); // .fail() does not work for cross domain requests

      var await_timeout = setTimeout(function () {
        if (d.state() === 'pending') {
          loading_failed = true;
          d.reject();
        }
      }, 5000);
      return d.promise();
    };
  };

  function fn_generate_api_url(options) {
    var data = ['key=' + options.api_key, 'libraries=places', 'callback=$.ceGeoMapOnLoadGoogleIndex'];
    var url = api_url + '?' + data.join('&');
    return url;
  }

  function fn_apply_ie_resizing_bug_workaround() {
    HTMLElement.prototype.getBoundingClientRect = function () {
      var oldGetBoundingClientRect = HTMLElement.prototype.getBoundingClientRect;
      return function () {
        try {
          return oldGetBoundingClientRect.apply(this, arguments);
        } catch (e) {
          return {
            left: '',
            right: '',
            top: '',
            bottom: ''
          };
        }
      };
    }();
  }

  $.ceGeoMapOnLoadGoogleIndex = function () {
    $.ceEvent('trigger', 'ce:geomap:onload', ['google', 'index']);
  };

  $.geoMapInitGoogleApi = fn_get_google_api_loader();
})(Tygh, Tygh.$);