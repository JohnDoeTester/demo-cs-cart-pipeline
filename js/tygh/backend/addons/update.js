(function (_, $) {
  /**
   * Toggles save buttons on settings and subscription tabs of addon details page
   */
  $.ceEvent('on', 'ce.tab.show', function (tab_id, $tabs_elm) {
    if (_.current_dispatch !== 'addons.update' || $tabs_elm.data('caAddons') === 'tabsSettingNested') {
      return;
    } // Toggle settings save button


    var isSettingsTabActive = !$('#content_settings').hasClass('hidden');
    $('.cm-addons-save-settings').toggleClass('hidden', !isSettingsTabActive); // Toggle subscription save button

    var isSubscriptionTabActive = !$('#content_subscription').hasClass('hidden');
    $('.cm-addons-save-subscription').toggleClass('hidden', !isSubscriptionTabActive);
  });
})(Tygh, Tygh.$);