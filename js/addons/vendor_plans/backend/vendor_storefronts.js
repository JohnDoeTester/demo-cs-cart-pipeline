(function (_, $) {
  function initVendorPlanPickerForStorefronts($vendorPlanPicker) {
    $vendorPlanPicker.on('ce:object_picker:init_template_selection_item', function (event, objectPicker, selected, template, $elem) {
      var $selectedOption = getSelectedOption($(this));

      if (!$selectedOption || !selected.data) {
        return;
      }

      setStorefrontsForPlan($selectedOption, selected.data.storefront_ids);
    });
    $vendorPlanPicker.on('ce:object_picker:object_selected', function (event, objectPicker, selected, template) {
      toggleStorefrontsUpdateNotificationVisibility($(this));
    });
  }

  function setStorefrontsForPlan($elem, storefrontIds) {
    $elem.data('caObjectPickerStorefronts', storefrontIds);
  }

  ;

  function toggleStorefrontsUpdateNotificationVisibility($planSelector) {
    var $notification = getUpdatePlanStorefrontVendorsNotification($planSelector);
    var newState = getNewStorefrontUpdateState($planSelector);
    $notification.toggleClass('hidden', !newState.hasUpdatedStorefronts);
    $('[data-ca-vendor-plans="updateVendorStorefrontVendorsAddNotification"]', $notification).toggleClass('hidden', !newState.hasAddedStorefronts);
    $('[data-ca-vendor-plans="updateVendorStorefrontVendorsRemoveNotification"]', $notification).toggleClass('hidden', !newState.hasRemovedStorefronts);
  }

  ;

  function getUpdatePlanStorefrontVendorsNotification($elem) {
    return $('[data-ca-vendor-plans="updateVendorStorefrontVendorsNotification"]', getContainer($elem));
  }

  ;

  function getSelectedOption($select) {
    return $select.find(':selected');
  }

  ;

  function getNewStorefrontUpdateState($planSelector) {
    var hasAddedStorefronts = false;
    var hasRemovedStorefronts = false;
    var $newPlan = getSelectedOption($planSelector);
    var newStorefronts = $newPlan.data('caObjectPickerStorefronts');
    var oldSelectedStorefronts = getOldSelectedStorefronts($planSelector);

    if (newStorefronts && oldSelectedStorefronts) {
      newStorefronts.forEach(function (storefrontId) {
        if (oldSelectedStorefronts.indexOf(storefrontId) === -1) {
          hasAddedStorefronts = true;
        }
      });
    }

    if (oldSelectedStorefronts) {
      oldSelectedStorefronts.forEach(function (storefrontId) {
        if (newStorefronts.indexOf(storefrontId) === -1) {
          hasRemovedStorefronts = true;
        }
      });
    }

    return {
      hasAddedStorefronts: hasAddedStorefronts,
      hasRemovedStorefronts: hasRemovedStorefronts,
      hasUpdatedStorefronts: hasAddedStorefronts || hasRemovedStorefronts
    };
  }

  ;

  function getOldSelectedStorefronts($elem) {
    return getContainer($elem).data('caSelectedStorefronts');
  }

  ;

  function getContainer($elem) {
    return $elem.closest('[data-ca-vendor-plans="companiesPlan"]');
  }

  $.ceEvent('on', 'ce.object_picker.inited', function (objectPicker) {
    if (objectPicker.options.objectType !== 'vendor-plan') {
      return;
    }

    initVendorPlanPickerForStorefronts(objectPicker.$elem);
  });
})(Tygh, Tygh.$);