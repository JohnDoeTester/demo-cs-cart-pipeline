{if !$smarty.request.extra}
<script>
(function(_, $) {
    var display_type = '{$smarty.request.display|escape:javascript nofilter}';
    _.tr('text_items_added', '{__("text_items_added")|escape:"javascript"}');

    {literal}
    $.ceEvent('on', 'ce.formpost_storefronts_form', function(frm, elm) {
        var storefronts = {},
            storefrontCompanies = {};

        $.ceEvent('on', 'ce.picker_add_js_item', function(hook_data) {
            var storefrontId = hook_data.var_id;
            var newItemId = '#' + frm.data('caResultId') + '_' + storefrontId;
            $(newItemId).data({
                caStorefrontId: storefrontId,
                caStorefrontCompanyIds: storefrontCompanies[storefrontId]
            });

            $.ceEvent('trigger', 'ce.picker_add_js_item_after_storefronts', [hook_data]);
        });

        if ($('input.cm-item:checked', frm).length > 0) {
            $('input.cm-item:checked', frm).each( function() {
                var id = $(this).val();
                var $storefront = $(this).closest('.storefront');
                storefronts[id] = $('.storefront__name', $storefront).text();
                storefrontCompanies[id] = $storefront.data('caStorefrontCompanyIds');
            });

            $.cePicker('add_js_item', frm.data('caResultId'), storefronts, 'a', {
                '{storefront_id}': '%id',
                '{storefront}': '%item'
            });

            if (display_type !== 'radio') {
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
    {/literal}
}(Tygh, Tygh.$));
</script>
{/if}

{include file="views/storefronts/components/search_form.tpl"
    dispatch="storefronts.picker"
    class="cm-ajax"
    in_popup=true
    extra="<input type='hidden' name='result_ids' value='pagination_{$smarty.request.data_id}' /><input type='hidden' name='data_id' value='{$smarty.request.data_id}' /><input type='hidden' name='extra' value='{$smarty.request.extra}' />"
    select_mode = $smarty.request.select_mode
}

<form action="{$smarty.request.extra|to_relative_url|fn_url}"
      data-ca-result-id="{$smarty.request.data_id}"
      method="post"
      name="storefronts_form"
>

    {include file="common/pagination.tpl"
        div_id="pagination_`$smarty.request.data_id`"
    }

    {include_ext file="common/icon.tpl" class="icon-`$search.sort_order_rev`" assign=sort_active_icon}
    {include_ext file="common/icon.tpl" class="icon-dummy" assign=sort_dummy_icon}
    {include file="views/storefronts/components/list.tpl"
        storefronts = $storefronts
        search = $search
        sort_url = $config.current_url|fn_query_remove:"sort_by":"sort_order"
        sort_active_icon_class = $sort_active_icon
        sort_dummy_icon_class = $sort_dummy_icon
        return_url = fn_url($config.current_url)|escape:url
        is_readonly = true
        select_mode = $smarty.request.select_mode
        force_selector_display = true
        get_company_ids = true
    }

    {include file="common/pagination.tpl"
        div_id="pagination_`$smarty.request.data_id`"
    }

    {if $storefronts}
        <div class="buttons-container">
            {if $smarty.request.display == "radio"}
                {$but_close_text = __("choose")}
            {else}
                {$but_close_text = $button_names.but_close_text|default:__("add_storefronts_and_close")}
                {$but_text = $button_names.but_text|default:__("add_storefronts")}
            {/if}
            {include file="buttons/add_close.tpl"
                is_js=$smarty.request.extra|fn_is_empty
            }
        </div>
    {/if}
</form>
