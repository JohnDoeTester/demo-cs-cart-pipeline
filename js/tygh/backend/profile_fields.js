(function (_, $) {
  $.ceEvent('on', 'ce.commoninit', function (context) {
    var $profile_fields_wrapper = context.find('#section_fields_C');

    if ($profile_fields_wrapper.length === 0) {
      return;
    }

    function fn_keep_one_group_of_visibility_checkboxes_checked(first_name, second_name) {
      var $first_field_row = $profile_fields_wrapper.find('[data-ca-profile-fields-row="' + first_name + '"]'),
          $second_field_row = $profile_fields_wrapper.find('[data-ca-profile-fields-row="' + second_name + '"]');
      $first_field_row.add($second_field_row).on('change', function (e) {
        var $unchecked_checkbox = $(e.target);

        if (!$unchecked_checkbox.is(':checkbox') || $unchecked_checkbox.is(':checked') || $unchecked_checkbox.hasClass('cm-skipp-check-checkbox')) {
          return;
        }

        var field_name = $(e.currentTarget).is($first_field_row) ? second_name : first_name,
            area = $unchecked_checkbox.data('caProfileFieldsArea');
        fn_check_visibility_checkboxes(field_name, area);
      });
      /**
       * @param field_name Field name
       * @param area       Show/require field area (profile/checkout)
       */

      function fn_check_visibility_checkboxes(field_name, area) {
        var $row = $profile_fields_wrapper.find('[data-ca-profile-fields-row="' + field_name + '"]'),
            $group = $row.find('[data-ca-profile-fields-area-group="' + area + '"]'),
            $checkboxes = $group.find('input:checkbox');

        if ($checkboxes.hasClass('cm-skipp-check-checkbox')) {
          return;
        }

        $checkboxes.prop('checked', true).prop('disabled', false);
      }
    }

    fn_keep_one_group_of_visibility_checkboxes_checked('email', 'phone');
  });
})(Tygh, Tygh.$);