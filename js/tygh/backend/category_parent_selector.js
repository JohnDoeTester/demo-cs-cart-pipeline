(function (_, $) {
  $.ceEvent('on', 'ce.object_picker.inited', function (objectPicker) {
    if (objectPicker.options.objectType !== 'storefront') {
      return;
    }

    var $storefrontPicker = objectPicker.$elem;
    $storefrontPicker.on('ce:object_picker:change', function (e, $elem, selected) {
      if (!selected.id) {
        return;
      }

      $.ceAjax('request', _.current_url, {
        data: {
          category_data: {
            storefront_id: selected.id
          }
        },
        result_ids: 'parent_category_selector'
      });
    });
  });
})(Tygh, Tygh.$);