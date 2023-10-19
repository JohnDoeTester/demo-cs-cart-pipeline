(function (_, $) {
  $.ceEvent('on', 'ce.commoninit', function (context) {
    $(context).find('.cm-privilege-set-access-level').on('change', function (e) {
      var $control_elm = $(e.target),
          usergroup_id = $control_elm.data('caPrivilegeUsergroupId'),
          control_level = $control_elm.data('caPrivilegeAccessLevel'),
          section_id = $control_elm.data('caPrivilegeSectionId'),
          group_id = $control_elm.data('caPrivilegeGroupId');

      if (group_id === 'global') {
        $('#content_privileges_' + usergroup_id).find('.cm-privilege-set-access-level').filter(function (index, control) {
          return $(control).data('caPrivilegeAccessLevel') === control_level && $(control).data('caPrivilegeUsergroupId') === usergroup_id && $(control).data('caPrivilegeGroupId') === 'section_global';
        }).prop('checked', true).trigger('change');
        return;
      }

      if (group_id === 'section_global') {
        $control_elm.closest('#' + section_id + '_contents').find('.cm-privilege-set-access-level').not($control_elm).filter(function (index, control) {
          return $(control).data('caPrivilegeAccessLevel') === control_level && $(control).data('caPrivilegeUsergroupId') === usergroup_id;
        }).prop('checked', true).trigger('change');
        return;
      }

      var $privileges_container = $('#usergroup_' + usergroup_id + '_privileges_list_' + section_id + '_' + group_id),
          $privileges = $privileges_container.find('input[type=checkbox]');

      if (control_level === 'custom') {
        fn_enable_custom_privileges_controls($privileges, $privileges_container);
        return;
      }

      $privileges.prop('checked', false).trigger('change');
      fn_disable_custom_privileges_controls($privileges, $privileges_container);

      if (control_level === 'full') {
        $privileges.prop('checked', true);
        return;
      }

      if (control_level === 'view') {
        $privileges.filter(function (index, checkbox) {
          return $(checkbox).data('caPrivilegeAccessType') === 'view';
        }).prop('checked', true).trigger('change');
      }
    });
    $(context).find('.privileges-custom-access').each(function (index, container) {
      var $container = $(container),
          $privileges = $container.find('input[type=checkbox]'),
          usergroup_id = $container.data('caPrivilegeUsergroupId'),
          section_id = $container.data('caPrivilegeSectionId'),
          group_id = $container.data('caPrivilegeGroupId');
      fn_get_access_level_by_selected_privileges($privileges, $container);
      var control_level = fn_get_access_level_by_selected_privileges($privileges);
      $('#usergroup_' + usergroup_id + '_privilege_' + section_id + '_' + group_id + '_access_level_' + control_level).prop('checked', true).prop('defaultChecked', true).trigger('change');
    });
    $(context).find('#elm_usergroup_type_0').on('change', function (e) {
      var $control_elm = $(e.target);
      $.ceAjax('request', fn_url('usergroups.get_privileges'), {
        result_ids: 'content_privileges_0',
        method: 'get',
        data: {
          type: $control_elm.val()
        }
      });
    });

    function fn_disable_custom_privileges_controls($privileges, $privileges_container) {
      $privileges_container.addClass('privileges-custom-access-disabled');
      $privileges.on('click touch', fn_make_checkbox_unclickable);
    }

    function fn_enable_custom_privileges_controls($privileges, $privileges_container) {
      $privileges.off('click touch', fn_make_checkbox_unclickable);
      $privileges_container.removeClass('privileges-custom-access-disabled');
    }

    function fn_get_access_level_by_selected_privileges($privileges) {
      var selected_privileges = $privileges.filter(function (index, checkbox) {
        return $(checkbox).prop('checked');
      });

      if (selected_privileges.length === 0) {
        return 'none';
      }

      var selected_view_privileges = selected_privileges.filter(function (index, checkbox) {
        return $(checkbox).data('caPrivilegeAccessType') === 'view';
      });

      if (selected_view_privileges.length === selected_privileges.length) {
        return 'view';
      }

      if ($privileges.length === selected_privileges.length) {
        return 'full';
      }

      return 'custom';
    }

    function fn_make_checkbox_unclickable(e) {
      e.preventDefault();
      e.stopPropagation();
      return false;
    }
  });
})(Tygh, Tygh.$);