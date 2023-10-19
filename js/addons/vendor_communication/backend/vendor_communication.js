(function (_, $) {
  function scrollToLastPost(context) {
    var $message_list_bottom = $(context).find('.vendor-communication-post__bottom');
    var $vendorCommunicationTab = $message_list_bottom.closest('[data-ca-vendor-communication="tab"]');

    if (!$message_list_bottom.length || $vendorCommunicationTab.hasClass('hidden')) {
      return;
    }

    $.scrollToElm($message_list_bottom);
    $('[data-ca-vendor-communication="threadMessage"]').focus();
  }

  ;
  $.ceEvent('on', 'ce.commoninit', function (context) {
    scrollToLastPost(context);
  });
  $.ceEvent('on', 'ce.tab.show', function (tab_id, $tabs_elm) {
    if (tab_id !== 'vendor_communication_vendor_to_customer') {
      return;
    }

    var vendorCommunicationTabNavTab = $('#vendor_communication_vendor_to_customer', $tabs_elm);

    if (!vendorCommunicationTabNavTab.hasClass('active')) {
      return;
    }

    var $vendorCommunicationTab = $tabs_elm.parent().find('#content_vendor_communication_vendor_to_customer');
    scrollToLastPost($vendorCommunicationTab);
  });
})(Tygh, Tygh.$);