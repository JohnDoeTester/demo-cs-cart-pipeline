(function (_, $) {
  var commercial_api_url = 'https://enterprise.api-maps.yandex.ru/',
      free_api_url = 'https://api-maps.yandex.ru/',
      default_language = 'en',
      api_version = '2.1',
      locales = {
    'ru': 'ru_RU',
    'en': 'en_US',
    'uk': 'uk_UA',
    'tr': 'tr_TR'
  };

  function fn_get_yandex_api_loader() {
    var d = $.Deferred(),
        yandex_api_initialized = false,
        loading_failed = false,
        loading_started = false;
    return function (options) {
      if (yandex_api_initialized || loading_started || loading_failed) {
        return d.promise();
      }

      loading_started = true;
      options = $.extend(options || {}, _.geo_maps);
      var url = fn_generate_api_url(options || {});
      $.getScript(url).then(function () {
        geo_maps_yandex.ready(function () {
          yandex_api_initialized = true;
          clearTimeout(await_timeout);
          d.resolve();
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
      }, 7000);
      return d.promise();
    };
  }

  function fn_generate_api_url(options) {
    var data = ['ns=geo_maps_yandex', 'lang=' + fn_get_locale(options.language || ''), 'onload=$.ceGeoMapOnLoadYandexIndex'];
    var url = free_api_url;

    if (options.yandex_commercial) {
      url = commercial_api_url;
    }

    if (options.api_key) {
      data.push('apikey=' + options.api_key);
    }

    url += api_version + '?' + data.join('&');
    return url;
  }

  function fn_get_locale(lang_code) {
    return locales[lang_code.toLowerCase()] || locales[default_language];
  }

  $.ceGeoMapOnLoadYandexIndex = function () {
    $.ceEvent('trigger', 'ce:geomap:onload', ['yandex', 'index']);
  };

  $.geoMapInitYandexApi = fn_get_yandex_api_loader();
})(Tygh, Tygh.$);