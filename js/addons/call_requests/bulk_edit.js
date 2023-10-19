(function (_, $) {
  var _doc = $(_.doc);

  $.ceEvent('on', 'ce.commoninit', function (context) {
    var $bulkeditResponsibleButtons = $('[data-ca-bulkedit-responsible-update]', context);

    if (!$bulkeditResponsibleButtons.length) {
      return;
    }

    _callRequestResponsiblesInit(context);
  }); // Bulk edit => responsible

  function _callRequestResponsiblesInit(context) {
    if (context.is(document)) {
      _doc.on('click', '[data-ca-bulkedit-responsible-update]', _setCallRequestResponsible);

      _doc.on('click', '[data-ca-bulkedit-responsible-cancel]', _resetter);
    }
  }
  /**
   * Update responsible
   * @param {Event} event
   */


  function _setCallRequestResponsible(event) {
    event.preventDefault();
    var $self = $(this),
        $form = $($self.data('caBulkeditResponsibleTargetForm')),
        $valuesNodes = $($self.data('caBulkeditResponsibleValues')),
        $selectedNodes = $form.find($self.data('caBulkeditResponsibleTargetFormActiveObjects')),
        dispatch = $self.data('caBulkeditResponsibleDispatch'),
        selectedResponsibleId = $valuesNodes.val(),
        selectedValues = [];
    selectedValues = $selectedNodes.map(function (index, elm) {
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
        request_ids: selectedValues,
        responsible_id: selectedResponsibleId
      }
    });
  }
  /**
   * Resets fields in dropdown
   * @param {Event} event
   */


  function _resetter(event) {
    event.preventDefault();
    $($(this).data('caBulkeditResponsibleResetChanger')).map(function (index, elm) {
      $(elm).val(0);
    });
  }
})(Tygh, Tygh.$);