(function ($, _) {
  $('#elm_disable_suspended_vendors').change(function () {
    if ($(this).prop('checked')) {
      $('#container_disable_vendors_settings').removeClass('hidden');
      $('#container_disable_vendors_notify_settings').removeClass('hidden');
    } else {
      $('#container_disable_vendors_settings').addClass('hidden');
      $('#container_disable_vendors_notify_settings').addClass('hidden');
    }
  });
})(Tygh.$, Tygh);