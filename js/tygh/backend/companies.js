(function (_, $) {
  $(_.doc).on('switch-change', '.company-switch-storefront-status-button', function (e, data) {
    var $target = $(this),
        storefrontId = $target.data('caStorefrontId') ? $target.data('caStorefrontId') : $target.data('caCompanyId'),
        url = $target.data('caSubmitUrl'),
        status = !data.value ? $target.data('caClosedStatus') : $target.data('caOpenedStatus');

    var rollback = function rollback() {
      $target.bootstrapSwitch('toggleState', true);
    };

    if (confirm(fn_strip_tags(_.tr('text_are_you_sure_to_proceed')))) {
      var switchStorefrontStatusParams = {
        status: status,
        return_url: $target.data('caReturnUrl'),
        result_ids: $target.data('caResultIds')
      };

      if ($target.data('caStorefrontId')) {
        switchStorefrontStatusParams.storefront_id = storefrontId;
      } else {
        switchStorefrontStatusParams.company_id = storefrontId;
      }

      var promise = fn_switch_storefront_status(switchStorefrontStatusParams, url);
      promise.fail(rollback);
    } else {
      rollback();
    }
  });
  $.ceEvent('on', 'ce.commoninit', function (context) {
    var $elem = $(context).find('.company-switch-storefront-status-button');

    if ($elem.length !== 0 && !$elem.hasClass('has-switch')) {
      $elem.bootstrapSwitch();
    }
  });

  var fn_switch_storefront_status = function fn_switch_storefront_status(request_data, url) {
    url = url || 'companies.switch_storefront_status';
    var d = $.Deferred();

    if (request_data['result_ids'] == undefined) {
      request_data['result_ids'] = 'header_subnav,header_navbar,actions_panel,storefront_url_*';
    }

    request_data['full_render'] = true;
    $.ceAjax('request', fn_url(url), {
      method: 'post',
      data: request_data,
      callback: function callback(res) {
        res.result ? d.resolve(res) : d.reject(res);
      }
    });
    return d.promise();
  };
})(Tygh, Tygh.$);