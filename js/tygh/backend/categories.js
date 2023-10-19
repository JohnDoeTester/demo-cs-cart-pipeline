(function (_, $) {
  $.ceEvent('on', 'ce.object_picker.inited', function (objectPicker) {
    var $elm = $(objectPicker.$elem);

    if (!$elm.hasClass('object-picker__select--categories')) {
      return;
    }

    var oldDropdownPositionCallback = $elm.data('select2').dropdown._positionDropdown;

    $elm.data('select2').dropdown._positionDropdown = function () {
      oldDropdownPositionCallback.apply(this, arguments);

      if (this.$dropdown.hasClass('select2-dropdown--above')) {
        this.$dropdownContainer.css({
          top: this.$container.offset().top + this.$container.outerHeight(false) - this.$dropdown.outerHeight(false) - this.$container.find('.select2-search').outerHeight()
        });
      }
    };
  });
  $.ceEvent('on', 'ce.object_picker.init_template_selection_item', function (objectPicker, object, template, list_elm) {
    var $elem = objectPicker.$elem;

    if (!$elem.hasClass('object-picker__select--categories') || !object.data) {
      return;
    }

    if ($elem.data('caItemRemovable') === undefined) {
      $elem.data('caItemRemovable', true);
    }

    if (object.data.disabled) {
      $(list_elm).addClass('select2-drag--disabled');
    }

    if (object.data.disabled || !$elem.data('caItemRemovable')) {
      $(list_elm).find('.select2-selection__choice__remove').remove();
    }
  });
})(Tygh, Tygh.$);