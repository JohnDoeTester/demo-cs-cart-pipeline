(function (_, $) {
  $.ceEvent('on', 'ce.commoninit', function (context) {
    var $pingppChannelSettings = $('[data-ca-pingpp-channel][id^=sw_settings_]', context);

    if (!$pingppChannelSettings.length) {
      return;
    }

    var elm_wx = $('[data-ca-pingpp-channel=wx_wap],[data-ca-pingpp-channel=wx_pub],[data-ca-pingpp-channel=wx_lite]');
    elm_wx.change(function (e) {
      var is_required = false;
      elm_wx.each(function (i, elm) {
        is_required = is_required || $(this).is(':checked');
      });
      $('.pingpp-wx-required').toggleClass('cm-required', is_required);
    });
    $pingppChannelSettings.change(function (e) {
      var checkbox = this;
      var section_id = $(this).attr('id').replace('sw_', '');
      $('label', '#' + section_id).each(function (i, lbl) {
        var control_id = $(this).attr('for');

        if ($('input#' + control_id).length) {
          $(this).toggleClass('cm-required', $(checkbox).is(':checked'));
        }
      });
    });
  });
})(Tygh, Tygh.$);