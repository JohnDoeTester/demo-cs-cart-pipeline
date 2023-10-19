(function (_, $) {
  $.ceEvent('on', 'ce.commoninit', function (context) {
    var $longtap = $('[data-ca-longtap]', context);

    if (!$longtap.length) {
      return;
    }

    _mod(context);
  });

  var _doc = $(_.doc); // Bulk edit => Price

  /**
   * Init function, binds events
   */


  function _mod(context) {
    $('[data-ca-bulkedit-mod-changer]', _doc).on('change', _changer).on('input', _changer);
    $('[data-ca-bulkedit-mod-update]', _doc).on('click', _sender);
    $('[data-ca-bulkedit-mod-cancel]', _doc).on('click', _resetter);
    $('[data-ca-bulkedit-mod-price-filter-p],[data-ca-bulkedit-mod-price-filter-lp],[data-ca-bulkedit-mod-price-filter-is]', _doc).on('change', function () {
      $self = $(this);
      $('[data-ca-bulkedit-mod-changer]').trigger('change');
    });
  }
  /**
   * Calculate all new values and send to backend
   * @param {Event} event 
   */


  function _sender(event) {
    event.preventDefault();
    var $self = $(this),
        $form = $($self.data('caBulkeditModTargetForm')),
        $valuesNodes = $($self.data('caBulkeditModValues')),
        $selectedNodes = $form.find($self.data('caBulkeditModTargetFormActiveObjects')),
        dispatch = $self.data('caBulkeditModDispatch'),
        currentValues = []; // Calculating new values and store to 'currentValues'

    $selectedNodes.each(function (i, node) {
      var id = $(node).data('caId'),
          values = {};
      $valuesNodes.each(function (i, _node) {
        var $self = $(_node),
            eqFieldSel,
            filter;

        if (!$self.data('caName') || !$self.val().length) {
          return true;
        }

        eqFieldSel = $.sprintf($self.data('caBulkeditEqualField'), [id], '?');
        filter = _getFilter($($self.data('caBulkeditModFilter')));
        values[$self.data('caName')] = filter(+$(eqFieldSel).val(), +$self.val());
      });
      currentValues.push({
        id: id,
        values: values
      });
    });
    $.ceAjax('request', fn_url(''), {
      caching: false,
      method: 'POST',
      full_render: 'Y',
      result_ids: 'content_manage_products',
      data: {
        dispatch: dispatch,
        redirect_url: _.current_url,
        new_values: currentValues
      }
    });
  }
  /**
   * Resets fields in dropdown
   * @param {Event} event 
   */


  function _resetter(event) {
    event.preventDefault();
    $($(this).data('caBulkeditModResetChanger')).each(function (index, elm) {
      var $self = $(elm),
          $affected = $($self.data('caBulkeditModAffectOn'));
      $($affected.data('caBulkeditModAffectedWriteInto'), $affected).text('').toggleClass('active', false);
      $($affected.data('caBulkeditModAffectedOldValue'), $affected).text($affected.data('caBulkeditModDefaultValue')).toggleClass('active', false);
      $self.val(undefined);
    });
  }
  /**
   * Handle changing fields in dropdown
   * @param {Event} event 
   */


  function _changer(event) {
    var $self = $(this),
        $affectedNode = $($self.data('caBulkeditModAffectOn')),
        filter = _getFilter($($self.data('caBulkeditModFilter'))),
        oldValue = $affectedNode.data('caBulkeditModDefaultValue'),
        curValue = filter(+oldValue, +$self.val());

    if (+curValue === +oldValue) {
      _toggle('', false);
    } else {
      _toggle(curValue.toString(), true);
    }

    function _toggle(val, flag) {
      $($affectedNode.data('caBulkeditModAffectedWriteInto'), $affectedNode).text(val).toggleClass('active', flag);
      $($affectedNode.data('caBulkeditModAffectedOldValue'), $affectedNode).toggleClass('active', flag);
    }
  }
  /**
   * Return filter-function
   * @param {jQuery} $containsFilterName form element, that contains name of filter
   */


  function _getFilter($containsFilterName) {
    filterName = $containsFilterName.val();
    return _filters()[filterName];
  }
  /**
   * Returns filters
   */


  function _filters() {
    return {
      percent: function percent(oldValue, modValue) {
        return (oldValue + oldValue * (modValue / 100)).toFixed(2);
      },
      number: function number(oldValue, modValue) {
        return oldValue + modValue;
      }
    };
  } // Bulk edit => Price

})(Tygh, Tygh.$);