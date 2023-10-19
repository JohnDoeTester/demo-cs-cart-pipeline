(function (_, $) {
  $(_.doc).on('click', '.object-picker--categories .cm-object-picker-remove-multiple-objects', function () {
    var $container = $(this).closest('.object-picker--categories');
    $container.find('.cm-item:checked').each(function () {
      var value = $(this).val(),
          $objectPicker = $container.find('.object-picker__select--categories');
      $objectPicker.find('option[value="' + value + '"]').prop('selected', false);
      $objectPicker.trigger('change');
    });

    if ($container.find('.cm-item:checked').length === 0) {
      $container.find('.cm-check-items').prop('checked', false);
      $container.find('.cm-object-picker-remove-multiple-objects').css('visibility', 'hidden');
    }

    if ($container.find('.object-picker__selection-extended--categories').length === 0) {
      $container.find('.object-picker__check-all').addClass('hide-check-all');
    }
  });
  $(_.doc).on('click', '.object-picker--categories .cm-check-items, .object-picker--categories .cm-item', function () {
    var $container = $(this).closest('.object-picker--categories'),
        checked = $(this).prop('checked'),
        isNotSelected = $container.find('.cm-item:checked').length === 0,
        visibility = checked || !isNotSelected && $(this).hasClass('cm-item') ? 'visible' : 'hidden';
    $container.find('.cm-object-picker-remove-multiple-objects').css('visibility', visibility);
  });
  $.ceEvent('on', 'ce.object_picker.object_selected', function (object) {
    var $container = object.$elem.closest('.object-picker--categories');

    if ($container.find('.object-picker__categories-check-all').hasClass('hide-check-all')) {
      $container.find('.object-picker__categories-check-all').removeClass('hide-check-all');
    }
  });
})(Tygh, Tygh.$);