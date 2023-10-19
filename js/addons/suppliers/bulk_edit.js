(function (_, $) {
  var _doc = $(_.doc);

  $.ceEvent('on', 'ce.commoninit', function (context) {
    var $shippingsUpdate = $('[data-ca-bulkedit-shippings-update]', context);

    if (!$shippingsUpdate.length) {
      return;
    }

    _suppliersShippingsInit(context);
  }); // Bulk edit => shippings

  function _suppliersShippingsInit(context) {
    if (context.is(document)) {
      _doc.on('click', '[data-ca-bulkedit-shippings-update]', _setSuppliersShippings);

      _doc.on('click', '[data-ca-bulkedit-shippings-cancel]', _resetter);
    }
  }
  /**
   * Update shippings
   * @param {Event} event
   */


  function _setSuppliersShippings(event) {
    event.preventDefault();
    var $self = $(this),
        $form = $($self.data('caBulkeditShippingsTargetForm')),
        $valuesNodes = $($self.data('caBulkeditShippingsValues')),
        $selectedNodes = $form.find($self.data('caBulkeditShippingsTargetFormActiveObjects')),
        dispatch = $self.data('caBulkeditShippingsDispatch'),
        selectedShippingIds = $valuesNodes.map(function (index, elm) {
      return $(elm).val();
    }).get(),
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
        supplier_ids: selectedValues,
        shipping_ids: $.isEmptyObject(selectedShippingIds) ? null : selectedShippingIds
      }
    });
  }
  /**
   * Resets fields in dropdown
   * @param {Event} event
   */


  function _resetter(event) {
    event.preventDefault();
    $($(this).data('caBulkeditShippingsResetChanger')).map(function (index, elm) {
      $(elm).removeAttr('checked');
    });
  }
})(Tygh, Tygh.$);