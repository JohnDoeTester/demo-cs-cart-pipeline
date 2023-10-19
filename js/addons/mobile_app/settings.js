(function (_, $) {
  $.ceEvent('on', 'ce.tab.show', function (tab_id, $tabs_elm) {
    if (_.current_dispatch !== 'addons.update' || $tabs_elm.data('caAddons') === 'tabsSettingNested') {
      return;
    } // Toggle settings save button


    var isChangeableSettingsTabActive = !$('#content_changeable_settings').hasClass('hidden');
    $('.cm-addons-save-changeable-settings').toggleClass('hidden', !isChangeableSettingsTabActive); // Toggle download config button

    var isPermanentSettingsTabActive = !$('#content_settings').hasClass('hidden');
    $('.cm-addons-download-config').toggleClass('hidden', !isPermanentSettingsTabActive);
  });
})(Tygh, Tygh.$);