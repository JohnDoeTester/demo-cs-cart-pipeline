{if !$smarty.request.extra}
<script>
(function(_, $) {
    _.tr('text_items_added', '{__("text_items_added")|escape:"javascript"}');
    _.tr('text_can_not_add_file_type', '{__("can_not_add_file_type_profile_field")|escape:"javascript"}');

    $.ceEvent('on', 'ce.formpost_add_profile_fields', function(frm, elm) {
        var max_displayed_qty = {$smarty.request.max_displayed_qty|default:"0"};
        var details_url = '{"profile_fields.update?field_id="|fn_url}';
        var profile_fields = {};
        var profile_fields_count = 0;

        if ($('input.cm-item:checked', frm).length > 0) {
            $('input.cm-item:checked', frm).each( function() {
                var type = $(this).data('type');
                if (type !== '{"ProfileFieldTypes::FILE"|enum}') {
                    var id = $(this).val();
                    var item = $(this).parent().parent();
                    profile_fields[id] = {
                        description: item.find('td.cm-profile-field-description').text(),
                    };
                    profile_fields_count ++;
                } else {
                    $.ceNotification('show', {
                        type: 'W',
                        title: _.tr('warning'),
                        message: _.tr('text_can_not_add_file_type'),
                        message_state: 'I'
                    });
                }
            });

            if (profile_fields_count > 0) {
                {literal}
                $.cePicker('add_js_item', frm.data('caResultId'), profile_fields, 'pf_', {
                    '{field_id}': '%id',
                    '{description}': '%item.description',
                });
                {/literal}

                $.cePicker('check_items_qty', frm.data('caResultId'), details_url, max_displayed_qty);

                $.ceNotification('show', {
                    type: 'N',
                    title: _.tr('notice'),
                    message: _.tr('text_items_added'),
                    message_state: 'I'
                });
            }
        }

        return false;
    });
}(Tygh, Tygh.$));
</script>
{/if}

<form action="{$smarty.request.extra|to_relative_url|fn_url}" data-ca-result-id="{$smarty.request.data_id}" method="post" name="add_profile_fields">

{if $profile_fields}
<div class="table-responsive-wrapper">
    <table width="100%" class="table table--relative table-responsive">
    <tr>
        <th class="center" width="1%">
            {include file="common/check_items.tpl" class="mrg-check"}</th>
        <th width="10%">{__("id")}</th>
        <th width="15%">{__("name")}</th>
    </tr>
    {foreach $profile_fields as $field}
    <tr>
        <td class="center" width="1%" data-th="">
            <input type="checkbox" name="add_parameter[]" value="{$field.field_id}" data-type="{$field.field_type}" class="mrg-check cm-item" /></td>
        <td data-th="{__("id")}">
            <span>#{$field.field_id}</span></td>
        <td class="cm-profile-field-description" data-th="{__("description")}"><input type="hidden" name="origin_statuses[{$field.field_id}]" value="{$field.description}" />{$field.description}</td>
    </tr>
    {/foreach}
    </table>
</div>
{else}
    <p class="no-items">{__("no_data")}</p>
{/if}

<div class="buttons-container">
    {include file="buttons/add_close.tpl" but_text=__("add_profile_fields") but_close_text=__("add_profile_fields_and_close") is_js=$smarty.request.extra|fn_is_empty}
</div>

</form>
