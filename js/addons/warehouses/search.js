(function (_, $) {
  var loadedMaps = {};

  function fn_warehouses_toggle_view($enableContainer, $disableContainer) {
    if ($disableContainer.length === 0 || $enableContainer.length === 0) {
      return;
    }

    $enableContainer.removeClass('hidden');
    $disableContainer.addClass('hidden');
    $($enableContainer.data('caWarehousesViewSelector')).prop('checked', true);
    $($disableContainer.data('caWarehousesViewSelector')).prop('checked', false);
    var $mapStub = $('.ty-warehouses-stores-map__map:not(.cm-geo-map-container)', $enableContainer);

    if ($mapStub.length !== 0) {
      fn_warehouses_load_map($mapStub, $enableContainer);
    }
  }

  function fn_warehouses_load_map($mapStub, $enableContainer) {
    if (!$mapStub.length) {
      return;
    }

    $mapStub.addClass('cm-geo-map-container');
    $.ceEvent('trigger', 'ce.commoninit', [$enableContainer]);
  }

  function fn_warehouses_store_matches_criteria(storeData, criteria) {
    var regex = new RegExp(criteria.replace(/[-\/\\^$*+?.()|[\]{}]/g, '\\$&'), 'gui');
    return regex.test(storeData);
  }

  function fn_warehouses_group_is_empty($group) {
    return $('.ty-warehouses-stores-list__item:visible', $group).length === 0;
  }

  function fn_warehouses_stores_list_is_empty($storesList) {
    return $('.ty-warehouses-stores-list__item:visible', $storesList).length === 0;
  }

  function fn_warehouses_save_loaded_map($map) {
    loadedMaps[$map.prop('id')] = true;
  }

  function fn_warehouses_is_loaded_map($map) {
    return loadedMaps[$map.prop('id')] || false;
  }

  $.ceEvent('on', 'ce:geomap:init', function ($map) {
    setTimeout(function () {
      fn_warehouses_save_loaded_map($map);
    }, 700);
  });
  $(_.doc).on('input', '.ty-warehouses-stores-search__query', function () {
    var $input = $(this),
        $storesList = $($input.data('caWarehousesStoresListSelector')),
        $notFoundMessage = $($input.data('caWarehousesNotFoundSelector')),
        $stores = $('.ty-warehouses-stores-list__item', $storesList),
        $groups = $('.ty-warehouses-store__group', $storesList),
        $enableContainer = $($input.data('caWarehousesViewSelectorOn')),
        $disableContainer = $($input.data('caWarehousesViewSelectorOff'));
    fn_warehouses_toggle_view($enableContainer, $disableContainer);
    $stores.removeClass('hidden');
    $groups.removeClass('hidden');
    $notFoundMessage.addClass('hidden');
    $stores.each(function (i, store) {
      var $store = $(store),
          storeData = $('.ty-warehouses-store__name-wrapper', $store).text(),
          $group = $($store.data('caWarehousesStoreGroupSelector'));

      if (!fn_warehouses_store_matches_criteria(storeData, $input.val())) {
        $store.addClass('hidden');
      }

      if (fn_warehouses_group_is_empty($group)) {
        $group.addClass('hidden');
      }
    });

    if (fn_warehouses_stores_list_is_empty($storesList)) {
      $notFoundMessage.removeClass('hidden');
    }
  });
  $(_.doc).on('click', '.ty-warehouses-store__name', function () {
    var $control = $(this),
        $marker = $($control.data('caWarehousesMarkerSelector')),
        $map = $($control.data('caWarehousesMapSelector')),
        $enableContainer = $($control.data('caWarehousesViewSelectorOn')),
        $disableContainer = $($control.data('caWarehousesViewSelectorOff'));
    fn_warehouses_toggle_view($enableContainer, $disableContainer);
    var intervalId = setInterval(function () {
      if (fn_warehouses_is_loaded_map($map)) {
        $map.ceGeoMap('setCenter', $marker.data('caGeoMapMarkerLat'), $marker.data('caGeoMapMarkerLng'));
        clearInterval(intervalId);
      }
    }, 300);
  });
  $(_.doc).on('change', '.ty-warehouses-stores-search__view-selector', function () {
    var $control = $(this),
        $enableContainer = $($control.data('caWarehousesViewSelectorOn')),
        $disableContainer = $($control.data('caWarehousesViewSelectorOff'));
    fn_warehouses_toggle_view($enableContainer, $disableContainer);
  });
})(Tygh, Tygh.$);