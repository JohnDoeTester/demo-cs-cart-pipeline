(function (_, $) {
  var _doc = $(_.doc);

  $.ceEvent('on', 'ce.commoninit', function (context) {
    var $longtap = $('[data-ca-longtap]', context);

    if (!$longtap.length) {
      return;
    }

    _pageParentInit(context);
  }); // Bulk edit => Page parent

  function _pageParentInit(context) {
    if (context.is(document)) {
      _doc.on('click', '[data-ca-bulkedit-mod-parent-update]', _setPageParent);

      _doc.on('click', '[data-ca-bulkedit-mod-parent-cancel]', _resetFields);
    }
  }
  /**
   * Update page parent
   * @param {Event} event 
   */


  function _setPageParent(event) {
    event.preventDefault();
    var $self = $(this),
        $form = $($self.data('caBulkeditModTargetForm')),
        $valuesNodes = $($self.data('caBulkeditModValues')),
        $selectedNodes = $form.find($self.data('caBulkeditModTargetFormActiveObjects')),
        dispatch = $self.data('caBulkeditModDispatch'),
        selectedParent = $valuesNodes.val(),
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
        page_ids: selectedValues,
        selected_parent: selectedParent
      }
    });
  }
  /**
   * Resets fields in dropdown
   * @param {Event} event 
   */


  function _resetFields(event) {
    event.preventDefault();
    $($(this).data('caBulkeditModParentResetChanger')).map(function (index, elm) {
      $(elm).val(0);
    });
  }
})(Tygh, Tygh.$);