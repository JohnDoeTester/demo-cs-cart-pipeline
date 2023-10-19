(function (_, $) {
  var $vkontakte = $('[data-ca-social-buttons="vkontakte"]');

  if (!$vkontakte.length || typeof $vkontakte.data() === 'undefined' || !$vkontakte.data('caSocialButtonsSrc')) {
    return;
  }

  $.ceLazyLoader({
    src: $vkontakte.data('caSocialButtonsSrc'),
    event_suffix: 'vkontakte',
    callback: function callback() {
      var $vkontakte = $('[data-ca-social-buttons="vkontakte"]');

      if (typeof VK === 'undefined' || !$vkontakte.length || typeof $vkontakte.data() === 'undefined' || !$vkontakte.data('caSocialButtonsApiId') || !$vkontakte.data('caSocialButtonsSettings') || !$vkontakte.data('caSocialButtonsHash')) {
        return;
      }

      var vkData = $vkontakte.data();
      VK.init({
        apiId: vkData.caSocialButtonsApiId,
        onlyWidgets: true
      }); // Create object from object with keys without quotes

      var vkontakteSettings = new Function('return' + vkData.caSocialButtonsSettings)();
      VK.Widgets.Like('vk_like', vkontakteSettings, vkData.caSocialButtonsHash);
    }
  });
})(Tygh, Tygh.$);