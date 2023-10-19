(function (_, $) {
  var _doc = $(_.doc);

  $.ceEvent('on', 'ce.commoninit', function (context) {
    var $messageSend = $('[data-ca-bulkedit-message-send]', context);

    if (!$messageSend.length) {
      return;
    }

    _vendorCommunicationSendMessageInit(context);
  });

  function _vendorCommunicationSendMessageInit(context) {
    if (context.is(document)) {
      _doc.on('click', '[data-ca-bulkedit-message-send]', _vendorCommunicationSendMessage);
    }
  }
  /**
   * Send message
   * @param {Event} event
   */


  function _vendorCommunicationSendMessage(event) {
    event.preventDefault();
    var $self = $(this),
        $form = $($self.data('caBulkeditMessageTargetForm')),
        $selectedNodes = $form.find($self.data('caBulkeditMessageTargetFormActiveObjects')),
        message = $($self.data('caBulkeditMessageValue')).val(),
        communication_type = $($self.data('caBulkeditMessageType')).val(),
        dispatch = $self.data('caBulkeditMessageDispatch'),
        thread_ids = [];
    thread_ids = $selectedNodes.map(function (index, elm) {
      return $(elm).data('caId');
    }).get();
    $.ceAjax('request', fn_url(''), {
      caching: false,
      method: 'POST',
      full_render: 'Y',
      result_ids: 'pagination_contents',
      data: {
        dispatch: dispatch,
        redirect_url: _.current_url,
        thread_ids: thread_ids,
        message: message,
        communication_type: communication_type
      }
    });
  }
})(Tygh, Tygh.$);