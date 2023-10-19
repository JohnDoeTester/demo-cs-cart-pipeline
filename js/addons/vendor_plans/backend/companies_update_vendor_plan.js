(function (_, $) {
  $.ceEvent('on', 'ce.vendor_update.vendor_plan_save', function (response) {
    if (!response.success) {
      return;
    }

    var $quickAddVendorPlanDialog = $('#companies_quick_add_vendor_plan'),
        $vendorPlanPickerElem = $('#vendor_plans_picker_elem');
    $quickAddVendorPlanDialog.ceInlineDialog('destroy');
    $vendorPlanPickerElem.ceObjectPicker('addObjects', [{
      id: response.vendor_plan_id
    }]);
  });
  $(_.doc).on('ce:object_picker:object_selected', '.cm-object-picker.object-picker__select--vendor-plan', function (event, objectPicker, selected) {
    if (!selected.isNew) {
      return;
    }

    $('#companies_quick_add_vendor_plan').ceInlineDialog('init', {
      data: {
        plan_data: {
          plan: selected.text
        }
      }
    });
  });
  $(_.doc).on('ce:inline_dialog:closed', '#companies_quick_add_vendor_plan', function (event, objectPicker, selected) {
    var $vendorPlanPickerElem = $('#vendor_plans_picker_elem');

    if ($vendorPlanPickerElem.length === 0) {
      return;
    }

    var $currentPlanId = $vendorPlanPickerElem.data('caCurrentPlanId');
    $vendorPlanPickerElem.ceObjectPicker('selectObjectId', [$currentPlanId]);
  });
})(Tygh, Tygh.$);