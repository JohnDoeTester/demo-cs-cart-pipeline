// Bulk edit
(function (_, $) {
  var _doc = $(document);

  $.ceEvent('on', 'ce.commoninit', _bulkEditInit);
  $.ceEvent('on', 'ce.tap.toggle', function (selected, $container) {
    if (!$container) {
      return;
    }

    (selected ? $('.bulkedit-toggler', $container) : $('.bulkedit-disabler', $container)).trigger('click');
  });
  $.ceEvent('on', 'ce.select_template_selection', function (object, list_elm, $container) {
    if (!$container.hasClass('cm-bulk-edit-object-categories-add') || !object.data) {
      return;
    }

    $(list_elm).toggleClass('no-bold', true);
  });
  $.ceEvent('on', 'ce.object_picker.selection_updated', function (self) {
    var $elm = self.$elem;

    if (!$elm.hasClass('cm-bulk-edit-object-categories-add')) {
      return;
    }

    if (!$elm.data('caObjectPickerHasRemovableItems')) {
      $elm.data('select2').$container.find('.select2-selection__choice__remove').each(function () {
        $(this).remove();
      });
    }

    $elm.data('select2').$container.find('.select2-selection__choice:first').addClass('no-bold');

    if (!$elm.hasClass('cm-bulk-edit-object-categories-add')) {
      return;
    }

    var categories_items = $elm.data('caCategoryItems') || {},
        item_ids = $elm.data('caSelectedItemIds') || [];
    updatedCategories = $elm.data('caUpdatedCategories');
    $elm.data('select2').$container.find('.select2__category-status-checkbox').each(function () {
      var $checkbox = $(this),
          category_id = $checkbox.data('caCategoryId');

      if (updatedCategories && !$.isEmptyObject(updatedCategories[category_id])) {
        $checkbox.prop('checked', updatedCategories[category_id].checked).prop('readonly', updatedCategories[category_id].readonly).prop('indeterminate', updatedCategories[category_id].indeterminate);
      } else {
        var checked = typeof categories_items[category_id] === 'undefined' || item_ids.length === categories_items[category_id].length;

        if (checked) {
          $checkbox.prop('defaultChecked', true).prop('checked', true);
        } else {
          $checkbox.prop('defaultChecked', false).prop('checked', false).prop('indeterminate', true).prop('readOnly', true);
        }
      }
    });
  });
  /**
   * Init function, binds events
   */

  function _bulkEditInit(context) {
    $('[data-ca-bulkedit-disabled]').each(function () {
      $(this).closest('[data-ca-longtap]').removeAttr('data-ca-longtap');
    });

    if (!$(context).find('[data-ca-bulkedit-expanded-object="true"]').length) {
      return;
    } // FIXME: Remove this code when multiple context menus on the page are fixed


    if ($('.bulkedit-dropdown--legacy').length && $('.bulkedit-dropdown--legacy li:not(.bulkedit-action--legacy)').length) {
      $('.bulkedit-dropdown--legacy').removeClass('hide');
    }

    if ($('.bulkedit-disabled').length) {
      $('.longtap-selection .bulkedit-toggler').attr('disabled', true);
      $('.longtap-selection .cm-item.hide').removeClass('hide');
      $('.longtap-selection tr').removeAttr('data-ca-longtap-action');
      $('.longtap-selection tr').removeAttr('data-ca-longtap-target');
      $('.bulkedit-buttons-disabled .bulkedit-dropdown--legacy.hide').removeClass('hide');
      $('.bulkedit-buttons-disabled .bulkedit-action--legacy').removeClass('hide');
      return;
    }

    _doc.on('click', '.bulkedit-toggler', toggleBulkEditPanel);

    _doc.on('click', '.bulkedit-disabler', toggleBulkEditPanel);

    _doc.on('click', '.bulkedit-toggler', setDispatchParameterBulkEditBtn);

    _cat(context);
  }
  /**
   * Toggling bulk edit panel
   * @param {Event} event 
   */


  function toggleBulkEditPanel(event) {
    var $self = $(this),
        $container = $self.closest('[data-ca-longtap]'),
        $enable = $($self.data('caBulkeditEnable'), $container),
        $disable = $($self.data('caBulkeditDisable'), $container);

    if ($(this).data('caBulkeditEnable') === '[data-ca-bulkedit-expanded-object=true]') {
      $enable.removeClass('hidden');
      $disable.addClass('bulk-edit__thead--collapse');
    } else {
      $enable.removeClass('bulk-edit__thead--collapse');
      $disable.addClass('hidden');
    }

    $('[name="check_all"]', $container).prop('checked', false);
  }
  /**
   * Add selected ids as a parameter in dispatch
   * @param {Event} event 
   */


  function setDispatchParameterBulkEditBtn(event) {
    var $self = $(this),
        $container = $self.closest('[data-ca-longtap]'),
        parametrElmName = $self.data('caBulkeditDispatchParameter'),
        ids = [];

    if (!parametrElmName) {
      return;
    }

    $('[name="' + parametrElmName + '"]:checked').each(function () {
      ids.push($(this).val());
    });
    $container.find('.bulk-edit [data-ca-pass-selected-object-ids-as]').each(function () {
      var dispatch = $(this).data('caDispatch');

      if (dispatch && ids.length > 0) {
        dispatch = dispatch.replace(']', '&' + $(this).data('caPassSelectedObjectIdsAs') + '={' + ids + '}');
        $(this).attr('data-ca-dispatch', dispatch);
      }
    });
  } // Bulk edit => Categories


  function _cat(context) {
    if (context.is(document)) {
      _doc.on('click', '.bulk-edit__btn-content--category', function () {
        var $self = $(this),
            $container = $(this).closest('[data-ca-longtap]');

        if ($($self.data('toggle')).hasClass('open')) {
          _updateCategoriesDropdown($container);
        } else {
          $container.find('.bulk-edit--overlay').remove();
          $($self.data('toggle')).toggleClass('open', false);
        }
      });

      $(_.doc).on('click', '[data-ca-bulkedit-mod-cat-cancel]', _resetter);
      $(_.doc).on('click', '[data-ca-bulkedit-mod-cat-update]', _applyNewCategories);
    }
  }
  /**
   * Update categories lists in dropdown (from backend)
   */


  function _updateCategoriesDropdown($container) {
    var $applyBtn = $('[data-ca-bulkedit-mod-cat-update]', $container),
        $form = $($applyBtn.data('caBulkeditModTargetForm')),
        $selectedNodes = $form.find($applyBtn.data('caBulkeditModTargetFormActiveObjects')),
        $selecbox = $('#bulk_edit_categories_list_content', $container).find('.cm-bulk-edit-object-categories-add'),
        categories_items = {},
        category_ids = [],
        item_ids = [],
        categories = [];
    $selecbox.val(null).empty().trigger('change');
    $selectedNodes.each(function (i, node) {
      var item_category_ids = $(node).data('caCategoryIds');
      var item_id = $(node).data('caId');

      for (var j in item_category_ids) {
        var category_id = item_category_ids[j];
        categories_items[category_id] = categories_items[category_id] || [];

        if (categories_items[category_id].indexOf(item_id) === -1) {
          categories_items[category_id].push(item_id);
        }

        if (category_ids.indexOf(category_id) === -1) {
          category_ids.push(category_id);
          categories.push({
            id: category_id
          });
        }
      }

      item_ids.push(item_id);
    });
    $selecbox.data('caCategoryItems', categories_items);
    $selecbox.data('caSelectedItemIds', item_ids);
    $selecbox.ceObjectPicker('addObjects', categories);
  }
  /**
   * Resets fields in dropdown
   * @param {Event} event 
   */


  function _resetter(event) {
    var $container = $(this).closest('[data-ca-longtap]');

    _updateCategoriesDropdown($container);

    event.preventDefault();
  }

  function _applyNewCategories(event) {
    event.preventDefault();
    var $container = $(this).closest('[data-ca-longtap]'),
        categoriesMap = {
      A: [],
      D: []
    },
        $self = $(this),
        itemIds = [],
        checkboxes = $('.cm-tristate', '.bulk-edit--reset-dropdown-menu'),
        selectedItems = $('.cm-longtap-target.selected', $container);
    canAllCatBeDeleted = $self.data('caBulkeditModCanAllCategoriesBeDeleted'), dispatch = $self.data('caBulkeditModDispatch'), objectType = $self.data('caBulkeditModObjectType') ? $self.data('caBulkeditModObjectType') : 'items', resultIds = $self.data('caBulkeditModResultIds') ? $self.data('caBulkeditModResultIds') : ''; // calculate categories statuses map

    $.each(checkboxes, function (i, elm) {
      var jelm = $(elm);

      if (elm.indeterminate) {
        return;
      }

      if (elm.checked) {
        categoriesMap.A.push(jelm.data('caCategoryId'));
      } else {
        categoriesMap.D.push(jelm.data('caCategoryId'));
      }
    });

    if (!canAllCatBeDeleted && categoriesMap.D.length == checkboxes.length) {
      alert(_.tr('unable_to_delete_all_categories'));
      return;
    } // calculate current selected items


    $.each(selectedItems, function (i, elm) {
      itemIds.push($(elm).data('caId'));
    });
    var data = {};
    data['dispatch'] = dispatch;
    data['redirect_url'] = _.current_url;
    data['categories_map'] = categoriesMap;
    data["".concat(objectType, "_ids")] = itemIds;
    $.ceAjax('request', fn_url(''), {
      caching: false,
      method: 'POST',
      full_render: 'Y',
      result_ids: resultIds,
      data: data,
      callback: function callback() {
        $.each(selectedItems, function (i, elm) {
          var new_category_ids = [];
          var $elm = $(elm);
          var category_ids = $elm.data('caCategoryIds');
          category_ids = category_ids.concat(categoriesMap.A);

          for (var j in category_ids) {
            category_ids[j] = parseInt(category_ids[j]);

            if (categoriesMap.D.indexOf(category_ids[j]) === -1) {
              new_category_ids.push(category_ids[j]);
            }
          }

          $elm.data('caCategoryIds', new_category_ids);
        });

        _updateCategoriesDropdown($container);
      }
    });
  } // Bulk edit => Categories

})(Tygh, Tygh.$); // Bulk edit => Custom tristate checkbox


(function (_, $) {
  $(document).on('click', '.cm-readonly', function (e) {
    e.preventDefault();
  });
  $.ceEvent('on', 'ce.commoninit', function (context) {
    $('[data-set-indeterminate="true"]', $(context)).prop('indeterminate', true);
  });
  $.ceEvent('on', 'ce.formpost_categories_form_update', function (root_id, updatedCategories, isAddedCategories) {
    var $selectbox = $("[data-ca-picker-id=\"".concat(root_id, "\"], [data-ca-object-picker-extended-picker-id=\"").concat(root_id, "\"]"));

    if (!$selectbox.length) {
      return;
    }

    if (isAddedCategories) {
      $selectbox.data('caUpdatedCategories', updatedCategories);
    } else {
      var selectorContainer = $selectbox.data('caDropdownParent');

      for (var catId in updatedCategories) {
        var $checkbox = $("".concat(selectorContainer, " [data-ca-category-id=\"").concat(catId, "\"]"));
        $checkbox.prop('checked', updatedCategories[catId].checked).prop('readonly', updatedCategories[catId].readonly).prop('indeterminate', updatedCategories[catId].indeterminate);
      }
    }
  });
  $(document).on('mouseup', '.cm-tristate', function (e) {
    e.preventDefault();
    var scope = this;
    setTimeout(function () {
      _onclick.call(scope);
    }, 1);
  });

  function _onclick() {
    if ($(this).data('caTristateJustClick')) {
      return;
    }

    var elm = $(this).get(0);
    if (elm.readOnly) elm.checked = elm.readOnly = false;else if (!elm.checked) elm.readOnly = elm.indeterminate = true;
  }
})(Tygh, Tygh.$); // Bulk edit => Custom dropdown


(function (_, $) {
  $(document).on('click', '.bulk-edit-toggle', function () {
    var $container = $(this).closest('[data-ca-longtap]');
    $($(this).data('toggle'), $container).toggleClass('open');
    var scope = this;
    $('.bulk-edit--overlay', $container).one('click', function () {
      $($(scope).data('toggle'), $container).toggleClass('open', false);
    });
  });
  $(document).on('click', '.cm-toggle', function (e) {
    var self = $(this);

    if (self.data('state') == 'show') {
      self.data('state', 'hide');
      self.html(self.data('hideText'));
      $(self.data('toggle')).toggleClass('hidden', false);
    } else {
      self.data('state', 'show');
      self.html(self.data('showText'));
      $(self.data('toggle')).toggleClass('hidden', true);
    }

    e.preventDefault();
  });
})(Tygh, Tygh.$); // Bulk edit =>  Edit selected


(function (_, $) {
  $(_.doc).on('click', '[data-ca-bulkedit-disable-save-button]', function () {
    _disableFormSubmitButton();
  });
  $.ceEvent('on', 'ce.dialogbeforeclose', function (d, e, u) {
    var isEditSelectedDialog = d.attr('id') === 'content_select_fields_to_edit';

    if (!isEditSelectedDialog) {
      return;
    }

    _disableFormSubmitButton();
  });

  function _disableFormSubmitButton() {
    var $saveButton = $('.bulkedit-disable-save-button'),
        $editSelectedButton = $('.bulkedit-disable-edit-button');

    if (!$saveButton.length && !$editSelectedButton.length) {
      return;
    }

    $saveButton.prop('disabled', !$saveButton.prop('disabled'));
    $editSelectedButton.prop('disabled', !$editSelectedButton.prop('disabled'));
  }
})(Tygh, Tygh.$);