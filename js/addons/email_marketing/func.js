(function (_, $) {
  $.ceEvent('on', 'ce.commoninit', function (context) {
    var $userDataEmailField = $('.litecheckout__form [name="user_data[email]"]', context);

    if (!$userDataEmailField.length) {
      return;
    }

    if ($(context).is(document)) {
      $(_.doc).on('change', '.litecheckout__form [name="user_data[email]"]', function () {
        var userEmail = this.value;
        $.ceAjax('request', fn_url('checkout.checkout'), {
          result_ids: 'litecheckout_final_section',
          method: 'get',
          full_render: true,
          data: {
            user_email: userEmail
          }
        });
      });
    }
  });
})(Tygh, Tygh.$);