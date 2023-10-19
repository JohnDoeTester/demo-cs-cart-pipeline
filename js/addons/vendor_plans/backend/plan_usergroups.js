(function (_, $) {
  /**
   * Checks whether an array includes all elements of another array.
   *
   * @param {Array} arr1
   * @param {Array} arr2
   *
   * @returns {boolean}
   */
  function includes(arr1, arr2) {
    var diff = $(arr1).not(arr2).get();
    return diff.length === 0;
  }

  function getUsergroupsUpdateState($form) {
    return $('input[id^="vendor_privileges_vendor_plans_usergroup_"]:checked', $form).map(function () {
      return this.value;
    }).get();
  }

  ;

  function getOldUsergroupsUpdateState($form) {
    return getUsergroupsUpdateState($form);
  }

  ;

  function getNewUsergroupsUpdateState($form, oldUsergroupsUpdateState) {
    var newUsergroupsUpdateState = getUsergroupsUpdateState($form);
    return {
      hasAddedUsergroups: !includes(newUsergroupsUpdateState, oldUsergroupsUpdateState),
      hasRemovedUsergroups: !includes(oldUsergroupsUpdateState, newUsergroupsUpdateState),
      hasUpdatedUsergroups: !includes(newUsergroupsUpdateState, oldUsergroupsUpdateState) || !includes(oldUsergroupsUpdateState, newUsergroupsUpdateState)
    };
  }

  function toggleUsergroupsUpdateNotificationVisibility($form, oldUsergroupsUpdateState) {
    var $vendorPlansPrivileges = $('[data-ca-vendor-privileges="vendorPlansPrivileges"]', $form);
    var $updatePlanUsergroupVendorsNotification = $('[data-ca-vendor-plans="updatePlanUsergroupVendorsNotification"]', $vendorPlansPrivileges);
    var newUsergroupsUpdateState = getNewUsergroupsUpdateState($form, oldUsergroupsUpdateState);
    $updatePlanUsergroupVendorsNotification.toggleClass('hidden', !newUsergroupsUpdateState.hasUpdatedUsergroups);
    $('[data-ca-vendor-plans="updatePlanUsergroupVendorsAddNotification"]', $updatePlanUsergroupVendorsNotification).toggleClass('hidden', !newUsergroupsUpdateState.hasAddedUsergroups);
    $('[data-ca-vendor-plans="updatePlanUsergroupVendorsRemoveNotification"]', $updatePlanUsergroupVendorsNotification).toggleClass('hidden', !newUsergroupsUpdateState.hasRemovedUsergroups);
  }

  ;
  $.ceEvent('on', 'ce.commoninit', function (context) {
    var $configForms = $('[data-ca-vendor-plans-is-update-form="true"]', context);

    if ($configForms.length === 0) {
      return;
    }

    $configForms.each(function (i, form) {
      var $form = $(form);
      var oldUsergroupsUpdateState = getOldUsergroupsUpdateState($form);
      $(_.doc).on('change', 'input[id^="vendor_privileges_vendor_plans_usergroup_"]', function () {
        toggleUsergroupsUpdateNotificationVisibility($form, oldUsergroupsUpdateState);
      });
    });
  });
})(Tygh, Tygh.$);