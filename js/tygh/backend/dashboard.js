(function (_, $) {
  $(_.doc).ready(function () {
    var dashboardUrl = 'index.index',
        $storefrontPicker = $('#storefront_id');

    if ($storefrontPicker.length) {
      dashboardUrl += '?' + $storefrontPicker.prop('name') + '=' + $storefrontPicker.val();
    }

    $.ceAjax('request', fn_url(dashboardUrl), {
      result_ids: 'dashboard_content,actions_panel',
      hidden: true
    });
  });
})(Tygh, Tygh.$);