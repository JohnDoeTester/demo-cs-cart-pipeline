(function (_, $) {
  var _doc = $(_.doc);

  $.ceEvent('on', 'ce.commoninit', function (context) {
    var $longtap = $('[data-ca-longtap]', context);

    if (!$longtap.length) {
      return;
    }

    _featureGroupsInit(context);
  }); // Bulk edit => Group

  function _featureGroupsInit(context) {
    if (context.is(document)) {
      _doc.on('click', '[data-ca-bulkedit-group-update]', _setProductFeatureGroup);

      _doc.on('click', '[data-ca-bulkedit-group-cancel]', _resetter);
    }
  }
  /**
   * Update group features
   * @param {Event} event 
   */


  function _setProductFeatureGroup(event) {
    event.preventDefault();
    var $self = $(this),
        $form = $($self.data('caBulkeditGroupTargetForm')),
        $valuesNodes = $($self.data('caBulkeditGroupValues')),
        $selectedNodes = $form.find($self.data('caBulkeditGroupTargetFormActiveObjects')),
        $parametersNode = $valuesNodes.find('option:selected'),
        dispatch = $self.data('caBulkeditGroupDispatch'),
        selectedGroup = $valuesNodes.val(),
        selectedValues = [],
        displayOnProduct = $parametersNode.data('caDisplayOnProduct'),
        displayOnCatalog = $parametersNode.data('caDisplayOnCatalog'),
        displayOnHeader = $parametersNode.data('caDisplayOnHeader');
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
        feature_ids: selectedValues,
        selected_group: selectedGroup,
        display_on_product: displayOnProduct,
        display_on_catalog: displayOnCatalog,
        display_on_header: displayOnHeader
      }
    });
  }
  /**
   * Resets fields in dropdown
   * @param {Event} event 
   */


  function _resetter(event) {
    event.preventDefault();
    $($(this).data('caBulkeditGroupResetChanger')).map(function (index, elm) {
      $(elm).val(0);
    });
  }
})(Tygh, Tygh.$);

(function (_, $) {
  $(_.doc).on('click', '.bulk-edit--product-features .bulk-edit__btn--category', function () {
    var $self = $(this),
        isOpen = $self.find('.bulk-edit__content').hasClass('open');
    $featureWithGroup = $('.cm-longtap-target.selected[data-ca-feature-group="true"]'), isSelectedFeatureWithGroup = $featureWithGroup ? $featureWithGroup.length > 0 : false, isShowWarning = !(isOpen && isSelectedFeatureWithGroup), $warning = $self.find('.bulk-edit-inner__hint--warning');
    $warning.toggleClass('hidden', isShowWarning);
  });
})(Tygh, Tygh.$);