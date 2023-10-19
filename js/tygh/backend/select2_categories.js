(function (_, $) {
  $.ceEvent('on', 'ce.commoninit', function (context) {
    var $elems = $('.cm-object-categories-add', context),
        category_ids = [];

    if (!$elems.length) {
      return;
    }

    $.each($elems, function () {
      var value = $(this).val();

      if (!value) {
        return;
      }

      if (!Array.isArray(value)) {
        value = [value];
      }

      category_ids = category_ids.concat(value);
    });

    if (category_ids.length) {
      fn_load_selected_categories(category_ids, $elems);
    }
  });
  $.ceEvent('on', 'ce.change_select_list', function (object, $container) {
    if ($container.hasClass('cm-object-categories-add') && object.data) {
      object.context = object.data.content;
    }
  });
  $.ceEvent('on', 'ce.select_template_selection', function (object, list_elm, $container) {
    if (!$container.hasClass('cm-object-categories-add') || !object.data) {
      return;
    }

    $(list_elm).data('optionId', object.id ? object.id : 0);

    if ($container.data('caItemRemovable') === undefined) {
      $container.data('caItemRemovable', true);
    }

    if (object.data.disabled) {
      $(list_elm).addClass('select2-drag--disabled');
    }

    if (object.data.disabled || !$container.data('caItemRemovable')) {
      $(list_elm).find('.select2-selection__choice__remove').remove();
    }

    object.context = object.data.content;
  });
  $.ceEvent('on', 'ce.select2.create_tag', function (elm, object, term) {
    if (object.type !== 'category') {
      return;
    }

    var text = $.trim(term);

    if (text === '') {
      return null;
    }

    var paramIndex = text.lastIndexOf('/');

    if (paramIndex !== -1) {
      text = text.slice(paramIndex + 1, text.length);
    }

    Object.assign(object, {
      text: term,
      content: {
        text: text,
        append: term === text ? '' : term
      }
    });
  }); // Hook add_js_items

  $.ceEvent('on', 'ce.picker_add_js_items', function (picker, items, data) {
    var $select2_selectbox = $('[data-ca-picker-id="' + data.root_id + '"]'),
        categories = [];

    if (!$select2_selectbox.hasClass('cm-object-categories-add')) {
      return;
    }

    $.map(items, function (data, category_id) {
      categories.push({
        category_id: category_id,
        category: data.category
      });
    });

    if (categories.length) {
      fn_add_categories(categories, $select2_selectbox);
    }
  });
  $.ceEvent('on', 'ce.picker_delete_js_items', function (picker, items, data) {
    var $select2_selectbox = $('[data-ca-picker-id="' + data.root_id + '"]');

    if (!$select2_selectbox.hasClass('cm-object-categories-add')) {
      return;
    }

    $.map(items, function (data, id) {
      var selected_ids = $select2_selectbox.val(),
          indexCategory = selected_ids.indexOf(id);

      if (indexCategory >= 0) {
        selected_ids.splice(indexCategory, 1);
      }

      $select2_selectbox.val(selected_ids).trigger('change');
    });
  });
  $.ceEvent('on', 'ce.select2_categories.add_categories', function (categories, $select2_selectbox) {
    fn_add_categories(categories, $select2_selectbox);
  });
  $.ceEvent('on', 'ce.select2.init', function ($elm) {
    if (!$elm.hasClass('cm-object-categories-add')) {
      return;
    }

    var old_position_dropdown = $elm.data('select2').dropdown._positionDropdown;

    $elm.data('select2').dropdown._positionDropdown = function () {
      old_position_dropdown.apply(this, arguments);

      if (this.$dropdown.hasClass('select2-dropdown--above')) {
        this.$dropdownContainer.css({
          top: this.$container.offset().top + this.$container.outerHeight(false) - this.$dropdown.outerHeight(false) - this.$container.find('.select2-search').outerHeight()
        });
      }
    };
  });

  function fn_add_categories(categories, $selectbox) {
    var category_ids = [];

    if (categories.length && $selectbox.length) {
      $.map(categories, function (category) {
        $.each($selectbox, function (key, elem) {
          var $elem = $(elem),
              selected_ids = $elem.val() || null;

          if (!Array.isArray(selected_ids)) {
            selected_ids = [selected_ids];
          }

          if (selected_ids.indexOf(category.category_id) === -1) {
            var option = new Option(category.category, category.category_id, true, true);
            $elem.append(option).trigger('change');
          }
        });
        category_ids.push(category.category_id);
      });
      fn_load_selected_categories(category_ids, $selectbox);
    }
  }

  function fn_load_selected_categories(category_ids, $selectbox) {
    var template_selectbox_map = {};
    $selectbox.each(function () {
      var $elem = $(this),
          template = $elem.data('caItemTemplate') || '';

      if (typeof template_selectbox_map[template] === 'undefined') {
        template_selectbox_map[template] = [];
      }

      template_selectbox_map[template].push($elem);
    });

    for (var template in template_selectbox_map) {
      $.ceAjax('request', fn_url('categories.get_categories_list'), {
        hidden: true,
        caching: true,
        data: {
          ids: category_ids,
          template: template
        },
        callback: function callback(response) {
          var category_map = {};

          if (typeof response.objects !== 'undefined') {
            $.each(response.objects, function (key, category) {
              category_map[category.id] = category;
            });
            $.each(template_selectbox_map[template], function (key, selectbox) {
              var $selectbox = $(selectbox),
                  selected_ids = $selectbox.val();

              if (!selected_ids) {
                return;
              }

              if (!Array.isArray(selected_ids)) {
                selected_ids = [selected_ids];
              }

              $.each(selected_ids, function (key, id) {
                var $option = $selectbox.find('option[value=' + id + ']');

                if (typeof category_map[id] !== 'undefined') {
                  var category = category_map[id],
                      disabled = category.data.disabled,
                      hide_disabled_items = $selectbox.data('caHideDisabledItems') || false;

                  if (disabled && hide_disabled_items) {
                    $option.remove();
                  } else {
                    $option.text(category.text);
                    $option.data('data', $.extend($option.data('data'), category));
                  }
                }
              });
              $selectbox.trigger('change');
            });
          }
        }
      });
    }
  }
})(Tygh, Tygh.$);