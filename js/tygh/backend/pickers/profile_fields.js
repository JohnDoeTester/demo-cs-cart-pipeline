(function (_, $) {
  $.ceEvent('on', 'ce.commoninit', function ($context) {
    var $container = $context.find('[data-cm-sortable-profile-fields-picker-container="true"]'),
        data_id = $container.data('caDataId');

    if ($container.length === 0) {
      return;
    }

    var sortable_item_class = $container.data('caSortableItemClass');
    $container.sortable({
      tolerance: 'pointer',
      containment: $container,
      cursor: 'move',
      forceHelperSize: true,
      axis: 'y',
      items: '.' + sortable_item_class,
      update: fn_rebuild_profile_field_ids_order
    });

    function fn_rebuild_profile_field_ids_order() {
      var new_field_ids_order = $('#' + data_id).find('.' + sortable_item_class).toArray().map(function (row) {
        var field_id = $(row).find('input[name="field_id"]').val();
        return parseInt(field_id);
      }).filter(function (field_id) {
        return field_id > 0;
      }).join(',');
      $('#pf_' + data_id + '_ids').val(new_field_ids_order);
    }
  });
})(Tygh, Tygh.$);