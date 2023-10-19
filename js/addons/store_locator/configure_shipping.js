(function (_, $) {
  var showStoreLocatorConfigureTab = false;
  $.ceEvent('on', 'ce.shippings.service_changed', function ($serviceSelector, $selectedService, tabReload) {
    if ($selectedService.data('caShippingModule') === 'store_locator') {
      showStoreLocatorConfigureTab = showStoreLocatorConfigureTab || $('[data-ca-store-locator-show-configure-tab]').length > 0;

      if (!showStoreLocatorConfigureTab) {
        $('#configure').hide();
        $('#content_configure').remove();
        tabReload.isRequired = false;
      }
    }
  });
})(Tygh, Tygh.$);