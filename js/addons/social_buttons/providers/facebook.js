(function (_, $) {
  var $facebook = $('[data-ca-social-buttons="facebook"]');

  if (!$facebook.length || typeof $facebook.data() === 'undefined' || !$facebook.data('caSocialButtonsSrc')) {
    return;
  }

  $.ceLazyLoader({
    src: $facebook.data('caSocialButtonsSrc'),
    event_suffix: 'facebook',
    callback: function callback() {
      if (!$(".fb-like").length || typeof FB === 'undefined') {
        return;
      }

      FB.init({
        status: true,
        cookie: true,
        xfbml: true
      });
    }
  });
})(Tygh, Tygh.$);