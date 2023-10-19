(function (_, $) {
  function bindEvents(context) {
    $('#sw_api_container', context).on('change', onChangeApiAccessSwitcher);
    $('#refresh_api_key', context).on('click touch', onClickRefreshApiKeyBtn);
  }

  function onChangeApiAccessSwitcher() {
    var api_key_container_id = $(this).data('caApiKeyContainerId'),
        api_key_container = $('#' + api_key_container_id);

    if ($(this).prop('checked')) {
      api_key_container.removeClass('hidden');
      api_key_container.find('.js-new-api-key').prop('disabled', false);

      if ($(this).data('caShowApiKeyWarning')) {
        api_key_container.find('.help-block').removeClass('hidden');
      }
    } else {
      api_key_container.addClass('hidden');
      api_key_container.find('.js-new-api-key').prop('disabled', true);
    }
  }

  function onClickRefreshApiKeyBtn() {
    var key_holder_id = $(this).attr('target'),
        key_holder = $('#' + key_holder_id);
    $.ceAjax('request', fn_url('profiles.generate_api_key'), {
      method: 'post',
      caching: false,
      callback: function callback(res) {
        if ('new_api_key' in res) {
          key_holder.val(res.new_api_key).prop('disabled', false).prop('readonly', true).addClass('js-new-api-key');
          key_holder.siblings('.help-block').removeClass('hidden');
        }
      }
    });
  }

  $.ceEvent('on', 'ce.commoninit', function (context) {
    bindEvents(context);
  });
})(Tygh, Tygh.$);