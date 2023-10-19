(function (_, $) {
  $(_.doc).on('click', '[data-ca-approve-disapprove="disapprove"], [data-ca-approve-disapprove="approve"]', function (e) {
    approveDisapprove($(this));
  });

  function approveDisapprove($button) {
    var $container = $button.closest('[data-ca-approve-disapprove="container"]');
    var $approveReason = $container.find('[data-ca-approve-disapprove="approve_reason"]');
    var $disapproveReason = $container.find('[data-ca-approve-disapprove="disapprove_reason"]');
    var sendData = $button.data('caApproveDisapproveData');
    sendData.show_notifications = 0;

    if ($approveReason.length) {
      sendData[$approveReason.attr('name')] = $approveReason.val();
    }

    if ($disapproveReason.length) {
      sendData[$disapproveReason.attr('name')] = $disapproveReason.val();
    }

    $.ceAjax('request', fn_url($container.data('caApproveDisapproveDispatch')), {
      method: 'post',
      data: sendData,
      callback: function callback(response) {
        $.redirect(_.current_url);
      }
    });
  }
})(Tygh, Tygh.$);