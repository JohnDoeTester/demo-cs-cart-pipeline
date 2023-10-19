(function (_, $) {
  var $yandex = $('[data-ca-social-buttons="yandex"]');

  if (!$yandex.length || typeof $yandex.data() === 'undefined' || !$yandex.data('caSocialButtonsSrc')) {
    return;
  }

  $.ceLazyLoader({
    src: $yandex.data('caSocialButtonsSrc'),
    event_suffix: 'yandex',
    callback: function callback() {
      $('.ya-share2').attr('id', 'ya-share2');

      if (typeof Ya === 'undefined') {
        return;
      }

      Ya.share2('ya-share2');
    }
  });
})(Tygh, Tygh.$);