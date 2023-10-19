(function (_, $) {
  $.ceEvent('on', 'ce.commoninit', function ($context) {
    var $switcher = $context.find('.js-storefront-switcher');
    var $storefrontPicker = $switcher.find('.cm-object-picker');

    if (!$switcher.length) {
      return;
    } // Automatically open a Object picker in a dropdown.


    $context.find('[data-ca-dropdown-object-picker-autoopen]').each(function (index, target) {
      $(target).on('click touch', function () {
        var $dropdown = $(this).parent('.dropdown');
        $dropdown.find($(this).data('caDropdownObjectPickerAutoopen')).filter('.cm-object-picker').each(function () {
          var $self = $(this);

          if ($self.data('caObjectPicker')) {
            var intervalIndex = 0;
            var dropdownChecker = setInterval(function () {
              if ($dropdown.hasClass('open')) {
                clearInterval(dropdownChecker);
                $self.ceObjectPicker('openDropdown');
              } // If the dropdown does not open in 10 * 100 ms, then delete the interval.


              if (intervalIndex > 10) {
                clearInterval(dropdownChecker);
              }

              intervalIndex++;
            }, 100);
          } else {
            $self.on('ce:object_picker:inited.dropdown', function () {
              setTimeout(function () {
                $self.ceObjectPicker('openDropdown');
              }, 0);
              $self.off('ce:object_picker:inited.dropdown');
            });
          }
        });
      });
    });
    $storefrontPicker.on('ce:object_picker:change', function (event, objectPicker, selected) {
      var url = fn_query_remove(_.current_url, [$switcher.data('caSwitcherParamName'), 'meta_redirect_url']),
          data = selected ? selected.data : {},
          storefront_id = data[$switcher.data('caSwitcherDataName')] || 0;
      $.redirect($.attachToUrl(url, $switcher.data('caSwitcherParamName') + '=' + storefront_id));
    });
    $storefrontPicker.on('ce:object_picker:dropdown_closed', function (event, objectPicker, selected) {
      var _this = this;

      setTimeout(function () {
        var $dropdownButton = $(_this).closest('.dropdown').filter('.open').find('[data-toggle="dropdown"]');
        $dropdownButton.dropdown('toggle');
      }, 200);
    });
  });
})(Tygh, Tygh.$);