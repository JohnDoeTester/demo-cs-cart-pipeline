(function (_, $) {
  /**
   * Checks whether any item of an array is present in another array.
   *
   * @param {Array} arr1
   * @param {Array} arr2
   *
   * @returns {boolean}
   */
  function intersects(arr1, arr2) {
    for (var i in arr1) {
      if (arr2.indexOf(arr1[i]) !== -1) {
        return true;
      }
    }

    return false;
  }
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
  /**
   * Builds information about added and removed storefronts.
   *
   * @param {jQuery} $oldState Old storefronts state
   * @param {jQuery} $newState New storefronts state
   *
   * @returns {object} {storefrontId: int, companyIds: array, isChecked: bool}
   */


  function buildStorefrontsState($oldState, $newState) {
    var result = {};
    $oldState.each(function (i, storefront) {
      var $storefront = $(storefront),
          storefrontId = parseInt($storefront.data('caStorefrontId')),
          companyIds = $storefront.data('caStorefrontCompanyIds');

      if (!storefrontId) {
        return;
      }

      result[storefrontId] = {
        storefrontId: storefrontId,
        companyIds: companyIds,
        isChecked: false
      };
    });
    $newState.each(function (i, storefront) {
      var $storefront = $(storefront),
          storefrontId = parseInt($storefront.data('caStorefrontId')),
          companyIds = $storefront.data('caStorefrontCompanyIds');

      if (!storefrontId) {
        return;
      }

      result[storefrontId] = {
        storefrontId: storefrontId,
        companyIds: companyIds,
        isChecked: true
      };
    });
    return result;
  }

  function getStorefrontUpdateState($form, withClone) {
    return $('.storefront' + (withClone ? '' : ':not(.cm-clone)'), $form);
  }

  ;

  function getOldStorefrontUpdateState($form) {
    return getStorefrontUpdateState($form, false);
  }

  ;

  function getNewStorefrontUpdateState($form, oldStorefrontUpdateState, affectedVendors) {
    var hasAddedStorefronts = false;
    var hasRemovedStorefronts = false;
    var selectedStorefronts = $form.data('caVendorPlansSelectedStorefronts').map(function (i) {
      return parseInt(i);
    });
    var $storefrontsNewState = getStorefrontUpdateState($form, true);
    var storefronts = buildStorefrontsState(oldStorefrontUpdateState, $storefrontsNewState);
    $.each(storefronts, function (j, storefront) {
      var storefrontId = storefront.storefrontId;
      var storefrontVendors = storefront.companyIds;

      if (storefront.isChecked && selectedStorefronts.indexOf(storefrontId) === -1 && !includes(affectedVendors, storefrontVendors)) {
        hasAddedStorefronts = true;
        return;
      }

      if (!storefront.isChecked && selectedStorefronts.indexOf(storefrontId) !== -1 && intersects(affectedVendors, storefrontVendors)) {
        hasRemovedStorefronts = true;
      }
    });
    return {
      hasAddedStorefronts: hasAddedStorefronts,
      hasRemovedStorefronts: hasRemovedStorefronts,
      hasUpdatedStorefronts: hasAddedStorefronts || hasRemovedStorefronts
    };
  }

  ;

  function toggleStorefrontsUpdateNotificationVisibility($form, oldStorefrontUpdateState, affectedVendors) {
    var $vendorPlanStorefronts = $('[data-ca-vendor-plans="vendorPlanStorefronts"]', $form);
    var $updatePlanStorefrontVendorsNotification = $('[data-ca-vendor-plans="updatePlanStorefrontVendorsNotification"]', $vendorPlanStorefronts);
    var newStorefrontUpdateState = getNewStorefrontUpdateState($form, oldStorefrontUpdateState, affectedVendors);
    $updatePlanStorefrontVendorsNotification.toggleClass('hidden', !newStorefrontUpdateState.hasUpdatedStorefronts);
    $('[data-ca-vendor-plans="updatePlanStorefrontVendorsAddNotification"]', $updatePlanStorefrontVendorsNotification).toggleClass('hidden', !newStorefrontUpdateState.hasAddedStorefronts);
    $('[data-ca-vendor-plans="updatePlanStorefrontVendorsRemoveNotification"]', $updatePlanStorefrontVendorsNotification).toggleClass('hidden', !newStorefrontUpdateState.hasRemovedStorefronts);
  }

  $.ceEvent('on', 'ce.commoninit', function (context) {
    var $configForms = $('[data-ca-vendor-plans-is-update-form="true"]', context);

    if ($configForms.length === 0) {
      return;
    }

    $configForms.each(function (i, form) {
      var $form = $(form);
      var affectedVendors = $form.data('caVendorPlansAffectedVendors');

      if (!affectedVendors || !affectedVendors.length) {
        return;
      }

      var oldStorefrontUpdateState = getOldStorefrontUpdateState($form);
      $.ceEvent('on', 'ce.picker_add_js_item_after_storefronts', function (hook_data) {
        toggleStorefrontsUpdateNotificationVisibility($form, oldStorefrontUpdateState, affectedVendors);
      });
      $.ceEvent('on', 'ce.picker_delete_js_item', function (hook_data) {
        toggleStorefrontsUpdateNotificationVisibility($form, oldStorefrontUpdateState, affectedVendors);
      });
    });
  });
})(Tygh, Tygh.$);