(function (_, $) {
  $.ceEvent('on', 'ce.commoninit', function (context) {
    var dispatch = 'geo_maps.map',
        delayTime = 1000,
        timeout,
        $yandexApiKeyInput = $('input[id^=addon_option_geo_maps_yandex_api_key]', context),
        $yandexCommercial = $('input[id^=addon_option_geo_maps_yandex_commercial]', context),
        $googleApiKeyInput = $("input[id^=addon_option_geo_maps_google_api_key]", context),
        $yandexMapContainer = $('#geo-map-yandex-container', context),
        $googleMapContainer = $('#geo-map-google-container', context),
        $yandexMapIframe = $('#geo-map-iframe-yandex', context),
        $googleMapIframe = $('#geo-map-iframe-google', context);

    if ($yandexApiKeyInput.val()) {
      $yandexMapContainer.removeClass('hidden');
    }

    if ($googleApiKeyInput.val()) {
      $googleMapContainer.removeClass('hidden');
    }

    if ($.browser.mozilla) {
      // workaround of a bug in firefox with display: none;
      $('#geo_maps_yandex', context).on('click', function () {
        $yandexMapIframe.contents().find('body').html('');
        $yandexMapIframe[0].contentWindow.location.reload();
      });
    }

    $yandexCommercial.on('change', function (e) {
      clearTimeout(timeout);
      timeout = setTimeout(function () {
        $yandexMapIframe[0].contentWindow.location.replace(fn_url(dispatch + '?provider=yandex&api_key=' + $yandexApiKeyInput.val() + '&yandex_commercial=' + (e.target.checked ? 'Y' : 'N')));
      }, delayTime);
    });
    $yandexApiKeyInput.on('input', function (e) {
      clearTimeout(timeout);
      timeout = setTimeout(function () {
        if (!e.target.value) {
          $yandexMapContainer.addClass('hidden');
          return;
        } else {
          $yandexMapIframe.contents().find('body').html('');
          $yandexMapContainer.removeClass('hidden');
        }

        $yandexMapIframe[0].contentWindow.location.replace(fn_url(dispatch + '?provider=yandex&api_key=' + e.target.value + '&yandex_commercial=' + $yandexCommercial.prop('checked')));
      }, delayTime);
    });
    $googleApiKeyInput.on('input', function (e) {
      clearTimeout(timeout);
      timeout = setTimeout(function () {
        if (!e.target.value) {
          $googleMapContainer.addClass('hidden');
          return;
        } else {
          $googleMapIframe.contents().find('body').html('');
          $googleMapContainer.removeClass('hidden');
        }

        $googleMapIframe[0].contentWindow.location.replace(fn_url(dispatch + '?provider=google&api_key=' + e.target.value));
      }, delayTime);
    });
  });
})(Tygh, Tygh.$);