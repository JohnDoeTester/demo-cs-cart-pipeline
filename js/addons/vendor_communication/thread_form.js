(function (_, $) {
  var form = $('.add_message_form');
  var elm = $(form).find('.vendor-communication-add-message__all-companies');
  elm.change(function () {
    $(form).find('.vendor-communication-add-message__company select').first().prop('disabled', $(this).prop('checked'));
  });
})(Tygh, Tygh.$);