(function (_, $) {
  $.ceEvent('on', 'ce.commoninit', function ($context) {
    var $switcher = $context.find('.js-company-switcher');
    var $companyPicker = $switcher.find('.cm-object-picker');

    if (!$switcher.length) {
      return;
    }

    $companyPicker.on('ce:object_picker:change', function (event, objectPicker, selected) {
      if (!selected) {
        return;
      }

      var data = selected ? selected.data : {},
          company_id = data[$switcher.data('caSwitcherDataName')] || 0,
          params = {};
      params[$switcher.data('caSwitcherParamName')] = company_id;
      $.performPostRequest(fn_url('profiles.login_as_vendor'), params, '_blank');
      objectPicker.setSelectedObjectIds(null);
    });
    $companyPicker.closest('.dropdown').on('mouseleave', function () {
      if ($companyPicker.data('caObjectPicker') && $companyPicker.data('caObjectPicker').isDropdownOpen()) {
        $companyPicker.ceObjectPicker('closeDropdown');
      }
    });
  });
})(Tygh, Tygh.$);