(function (_, $) {
  var _doc = $(_.doc);

  $.ceEvent('on', 'ce.commoninit', function (context) {
    var $pickupUpdate = $('[data-ca-bulkedit-pickup-update]', context);

    if (!$pickupUpdate.length) {
      return;
    }

    _callRequestPickupsInit(context);
  }); // Bulk edit => pickup

  function _callRequestPickupsInit(context) {
    if (context.is(document)) {
      _doc.on('click', '[data-ca-bulkedit-pickup-update]', _setCallRequestPickup);

      _doc.on('click', '[data-ca-bulkedit-pickup-cancel]', _resetter);
    }
  }
  /**
   * Update pickup
   * @param {Event} event
   */


  function _setCallRequestPickup(event) {
    event.preventDefault();
    var $self = $(this),
        $form = $($self.data('caBulkeditPickupTargetForm')),
        $valuesNodes = $($self.data('caBulkeditPickupValues')),
        $selectedNodes = $form.find($self.data('caBulkeditPickupTargetFormActiveObjects')),
        dispatch = $self.data('caBulkeditPickupDispatch'),
        selectedPickupId = $valuesNodes.val(),
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
        store_locator_ids: selectedValues,
        pickup_id: selectedPickupId
      }
    });
  }
  /**
   * Resets fields in dropdown
   * @param {Event} event
   */


  function _resetter(event) {
    event.preventDefault();
    $($(this).data('caBulkeditPickupResetChanger')).map(function (index, elm) {
      $(elm).val(0);
    });
  }
})(Tygh, Tygh.$);