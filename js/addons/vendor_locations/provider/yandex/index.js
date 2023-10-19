(function (_, $) {
  var commercialApiUrl = 'https://enterprise.api-maps.yandex.ru/',
      freeApiUrl = 'https://api-maps.yandex.ru/',
      defaultLanguage = 'en',
      apiVersion = '2.1',
      locales = {
    'ru': 'ru_RU',
    'en': 'en_US',
    'uk': 'uk_UA',
    'tr': 'tr_TR'
  };

  function fnGetYandexApiLoader() {
    var d = $.Deferred(),
        yandexApiInitialized = false,
        loadingFailed = false,
        loadingStarted = false;
    return function (options) {
      if (yandexApiInitialized || loadingStarted || loadingFailed) {
        return d.promise();
      }

      loadingStarted = true;
      options = $.extend(options || {}, _.vendor_locations);
      var url = fnGenerateApiUrl(options || {});
      $.getScript(url).then(function () {
        ymaps.ready(function () {
          yandexApiInitialized = true;
          clearTimeout(awaitTimeout);
          d.resolve();
        });
      }).fail(function () {
        loadingFailed = true;
        d.reject();
      }); // .fail() does not work for cross domain requests

      var awaitTimeout = setTimeout(function () {
        if (d.state() === 'pending') {
          loadingFailed = true;
          d.reject();
        }
      }, 7000);
      return d.promise();
    };
  }

  function fnGenerateApiUrl(options) {
    var data = ['lang=' + fnGetLocale(options.language || ''), 'onload=$.ceVendorLocationsOnLoadYandexIndex'];
    var url = freeApiUrl;

    if (options.yandex_commercial) {
      url = commercialApiUrl;
    }

    if (options.api_key) {
      data.push('apikey=' + options.api_key);
    }

    url += apiVersion + '?' + data.join('&');
    return url;
  }

  function fnGetLocale(lang_code) {
    return locales[lang_code.toLowerCase()] || locales[defaultLanguage];
  }

  $.ceVendorLocationsOnLoadYandexIndex = function () {
    $.ceEvent('trigger', 'ce:vendor_locations:onload', ['yandex', 'index']);
  };

  $.vendorLocationsInitYandexApi = fnGetYandexApiLoader();
})(Tygh, Tygh.$);