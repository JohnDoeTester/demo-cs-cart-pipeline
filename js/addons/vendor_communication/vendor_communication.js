(function (_, $) {
  function isIos() {
    return ['iPad Simulator', 'iPhone Simulator', 'iPod Simulator', 'iPad', 'iPhone', 'iPod'].includes(navigator.platform) // iPad on iOS 13 detection
    || navigator.userAgent.includes("Mac") && "ontouchend" in document;
  }

  function scrollToLastPost(context) {
    var postChatSelector = '[data-ca-vendor-communication="post-chat"]';
    var $postChat = $(context).find(postChatSelector).length ? $(context).find(postChatSelector) : $(context).closest(postChatSelector);
    var scrollDelay = isIos() ? 350 : 0; // After opening the iOS keyboard (300ms)

    if (!$postChat.length) {
      return;
    }

    setTimeout(function () {
      var visualViewport = window.visualViewport ? window.visualViewport.height : $(window).height();
      $('html, body').scrollTop($postChat.offset().top + $postChat.outerHeight(true) - visualViewport);
    }, scrollDelay);
  }

  ;
  $.ceEvent('on', 'ce.commoninit', function (context) {
    scrollToLastPost(context);
  });
  $.ceEvent('on', 'ce.tab.show', function (tab_id, $tabs_elm) {
    if (tab_id !== 'vendor_communication') {
      return;
    }

    var vendorCommunicationTabNavTab = $('#vendor_communication', $tabs_elm);

    if (!vendorCommunicationTabNavTab.hasClass('active')) {
      return;
    }

    var $vendorCommunicationTab = $tabs_elm.parent().find('#content_vendor_communication');
    scrollToLastPost($vendorCommunicationTab);
  });
})(Tygh, Tygh.$);