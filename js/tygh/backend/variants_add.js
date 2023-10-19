(function (_, $) {
  $.ceEvent('on', 'ce.commoninit', function (context) {
    var $btnAdd = $('[data-ca-variants-list="btnAdd"]', context);

    if (!$btnAdd.length) {
      return;
    }

    $btnAdd.each(function () {
      initVariantsAdd($(this));
    });
  });

  function initVariantsAdd($btnAdd) {
    $btnAdd.on('click', showAddVariants);
  }

  function showAddVariants(e) {
    var $btnAdd = $(e.currentTarget);
    var $variantsListContainer = $btnAdd.closest('[data-ca-variants-list="container"]');
    var $containerAdd = $('[data-ca-variants-list="containerAdd"]', $variantsListContainer);

    if ($btnAdd.data('caVariantsListIsShowAddVariants')) {
      $btnAdd.removeClass('variants-list__add--show');
      $containerAdd.addClass('hidden');
      setDefaultValues($containerAdd);
      $btnAdd.data('caVariantsListIsShowAddVariants', false);
    } else {
      $btnAdd.addClass('variants-list__add--show');
      $containerAdd.removeClass('hidden');
      $btnAdd.data('caVariantsListIsShowAddVariants', true);
    }
  }

  function setDefaultValues($containerAdd) {
    $('[name]', $containerAdd).each(function () {
      var self = $(this);

      if (self.is('input[type=checkbox], input[type=radio]')) {
        var default_checked = self.get(0).defaultChecked;
        self.prop('checked', default_checked ? true : false);
      } else if (self.is(':input') && self.prop('type') != 'hidden') {
        if (self.prop('name') != 'submit') {
          self.val(''); // reset select box

          if (self.prop('tagName').toLowerCase() == 'select') {
            self.prop('selectedIndex', '');
          }
        }
      }
    });
  }
})(Tygh, Tygh.$);