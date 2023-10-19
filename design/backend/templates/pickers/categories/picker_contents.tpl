{if $language_direction == "rtl"}
    {$direction = "right"}
{else}
    {$direction = "left"}
{/if}

{$form_id = "cat_form_{0|rand:1024}"}

{if !$smarty.request.extra}
<script>
(function(_, $) {
    _.tr('text_items_updated', '{__("text_items_updated")|escape:"javascript"}');
    var display_type = '{$smarty.request.display|escape:javascript nofilter}';
    var selectedCategories = {};
    var isTristateCheckbox = '{$smarty.request.is_tristate_checkbox}';

    $.ceEvent('on', 'ce.formpost_categories_form', function(frm, elm) {
        var categories = {};

        if ($('input.cm-item:checked', frm).length > 0) {
            $('input.cm-item:checked', frm).each( function() {
                var id = $(this).val();
                if (display_type != 'radio') {
                    categories[id] = {
                        category: $('#category_' + id).text(),
                        path_items: ''
                    };
                    var parent = $(this).closest('.table-tree').parent().prev('.table-tree');
                    while (parent.length > 0) {
                        var path_id = $('.cm-item', parent).first().val();
                        if (path_id) {
                            var path_name = $('#category_' + path_id).text();
                            categories[id]['path_items'] =
                                '<a class="ty-breadcrumbs__a" target="_blank" href="{"categories.update&category_id="|fn_url}'+path_id+'">'+path_name+'</a> / ' +
                                    categories[id]['path_items'];
                        }
                        parent = parent.parent().prev('.table-tree');
                    }
                }
                else {
                    categories[id] = $('#category_' + id).text()
                }
            });

            if (display_type != 'radio') {
                {literal}
                $.cePicker('add_js_item', frm.data('caResultId'), categories, 'c', {
                    '{category_id}': '%id',
                    '{category}': '%item.category',
                    '{path_items}': '%item.path_items'
                });
                {/literal}
            } else {
                {literal}
                $.cePicker('add_js_item', frm.data('caResultId'), categories, 'c', {
                    '{category_id}': '%id',
                    '{category}': '%item'
                });
                {/literal}
            }
        }

        if (!isTristateCheckbox) {
            //delete unselected categories
            var removableCategories = {};
            for (var catId in selectedCategories) {
                if (!(catId in categories) && $('#content_{$smarty.request.data_id} #input_cat_' + catId).length) {
                    removableCategories[catId] = selectedCategories[catId];
                }
            }

            if (display_type != 'radio') {
                {literal}
                    $.cePicker('delete_js_items', frm.data('caResultId'), removableCategories, 'c');
                {/literal}
            }
        } else {
            // for bulkedit category picker
            var isAddedCategories = $('#content_{$smarty.request.data_id} input[type="checkbox"]:checked').length > 0;
            for (var catId in selectedCategories) {
                var $checkbox = $('#content_{$smarty.request.data_id} #input_cat_' + catId);

                if ($checkbox.length) {
                    selectedCategories[catId] = {
                        checked: $checkbox.prop('checked'),
                        readonly: $checkbox.prop('readonly'),
                        indeterminate: $checkbox.prop('indeterminate')
                    }
                }
            }

            $.ceEvent('trigger', 'ce.formpost_categories_form_update', [frm.data('caResultId'), selectedCategories, isAddedCategories]);
        }


        if (display_type != 'radio') {
            $.ceNotification('show', {
                type: 'N',
                title: _.tr('notice'),
                message: _.tr('text_items_updated'),
                message_state: 'I'
            });
        }

        return false;
    });

    $('#{$form_id}').on('click', '.cm-click-and-close', function (e) {
        // skip, if event path contains 'hide'-button
        let flag = false;
        $(e.originalEvent.path).each((i, elm) => {
            flag = flag || $(elm).is('[data-ca-categories-hide-target]');
        });
        if (flag) {
            return;
        }

        // skip, if content hidden or not loaded
        if ($(this).hasClass('cm-click-and-close-forced')) {
            let {
                caTargetCombinationContainer,
                caTargetCombinationExpander,
                caTargetCombinationFetchUrl,
                caTargetCombinationFetchId
            } = $(this).data();

            if (caTargetCombinationContainer) {
                // if content is not loaded
                if (!$(caTargetCombinationContainer).children().length) {
                    $.ceAjax(
                        'request',
                        caTargetCombinationFetchUrl,
                        { result_ids: caTargetCombinationFetchId }
                    );
                    return;
                } else {
                    // if content loaded, but container with content is hidden
                    if (!$(caTargetCombinationContainer).is(':visible')) {
                        return;
                    }
                }
            }
        }

        // process, if got metakeys or got forced flag
        if ((e.metaKey || e.ctrlKey) || $(this).hasClass('cm-click-and-close-forced')) {
            if ($(e.target).closest('[data-ca-categories-expand-target], [data-ca-categories-hide-target]').length) {
                return;
            }
            let { caTargetCheckbox } = $(this).data();

            if (caTargetCheckbox && !$(caTargetCheckbox).is(e.target)) {
                let _target = $(caTargetCheckbox);
                _target.prop('checked', !_target.prop('checked'));
            }

            setTimeout(() => $('#{$form_id} .cm-process-items.cm-dialog-closer').click(), 100);

            if (caTargetCheckbox && !$(caTargetCheckbox).is(e.target)) {
                e.preventDefault();
                return false;
            }
        }
    });

    $.ceEvent('on', 'ce.commoninit', function (context) {
        if (display_type !== 'radio' && context && $(context).find('.category-tree-wrapper').length) {
            selectCategories();

            if (isTristateCheckbox) {
                $(context).find('.cm-item').addClass('cm-tristate tristate-checkbox-cursor');
            }
        }
    });

    $.ceEvent('on', 'ce.dialog.before_open', function () {
        if (display_type !== 'radio') {
            selectCategories();
        }
    });

    function selectCategories () {
        var categoriesPickerValue;

        if (isTristateCheckbox) {
            // for bulkedit category picker
            var $container = $('.object-categories-add--bulk-edit');

            if ($container.length) {
                var categoriesPickerValue = $container.find(' .select2__category-status-checkbox:checked, .select2__category-status-checkbox:indeterminate').map(function () {
                    var $self = $(this);

                    return {
                        id: $self.data('caCategoryId'),
                        checked: $self.prop('checked'),
                        readonly: $self.prop('readonly'),
                        indeterminate: $self.prop('indeterminate')
                    }
                }).get();
            }
        } else if ($('[data-ca-object-picker-extended-picker-id="{$smarty.request.data_id}"]').length) {
            categoriesPickerValue = $('[data-ca-object-picker-extended-picker-id="{$smarty.request.data_id}"]').val();
        } else if ($('[data-ca-picker-id="{$smarty.request.data_id}"]').length) {
            // FIXME: Fix when object selector will replaced with object picker
            categoriesPickerValue = $('[data-ca-picker-id="{$smarty.request.data_id}"]').val();
        } else {
            categoriesPickerValue = $('#{$smarty.request.data_id} .cm-picker-value').val();
        }

        if (categoriesPickerValue && categoriesPickerValue.length > 0) {
            categories = Array.isArray(categoriesPickerValue) ? categoriesPickerValue : categoriesPickerValue.split(',');

            $('#content_{$smarty.request.data_id} [name="categories_form"] .cm-item').prop('checked', false);

            // for bulkedit category picker
            if (isTristateCheckbox) {
                $('#content_{$smarty.request.data_id} [name="categories_form"] .cm-item').prop('readonly', false);
                $('#content_{$smarty.request.data_id} [name="categories_form"] .cm-item').prop('indeterminate', false);

                for (var i in categories) {
                    var $checkbox = $('#content_{$smarty.request.data_id} #input_cat_' + categories[i].id);

                    $checkbox.prop('checked', categories[i].checked)
                             .prop('readonly', categories[i].readonly)
                             .prop('indeterminate', categories[i].indeterminate);

                    selectedCategories[categories[i].id] = {};
                }
            } else {
                for (var i in categories) {
                    var $checkbox = $('#content_{$smarty.request.data_id} #input_cat_' + categories[i]);

                    $checkbox.prop('checked', true);
                    selectedCategories[categories[i]] = categories[i];
                }
            }
        }

    }

}(Tygh, Tygh.$));
</script>
{/if}

<form id="{$form_id}" action="{$smarty.request.extra|to_relative_url|fn_url}" data-ca-result-id="{$smarty.request.data_id}" method="post" name="categories_form">

<div class="items-container multi-level">
    {if $categories_tree}
        {include file="views/categories/components/categories_tree_simple.tpl"
            header=true
            checkbox_name=$smarty.request.checkbox_name|default:"categories_ids"
            parent_id=$category_id display=$smarty.request.display
            direction=$direction
            radio_class="hidden"
            is_tristate_checkbox=$smarty.request.is_tristate_checkbox|default:false
        }

        {if $smarty.request.display != "radio"}
            <br />
            <p class="text-center mobile-hide quick-select-protip">{__("tip.quick_select_and_close_category_selector")}</p>
        {/if}
    {else}
        <p class="no-items center">
            {__("no_categories_available")}
            {if "ULTIMATE"|fn_allowed_for}
                <a href="{"categories.manage"|fn_url}">{__("manage_categories")}.</a>
            {/if}
        </p>
    {/if}
</div>

<div class="buttons-container buttons-container--hidden-cancel">
    {if $smarty.request.display === "radio"}
        {$but_close_text = __("choose")}
        {include file="buttons/add_close.tpl" is_js=$smarty.request.extra|fn_is_empty}
    {else}
        {include file="buttons/save.tpl" but_role="submit" but_meta="cm-dialog-closer btn-primary cm-form-dialog-closer"}
    {/if}
</div>

</form>
