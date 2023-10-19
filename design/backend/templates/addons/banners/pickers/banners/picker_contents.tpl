{if !$smarty.request.extra}
<script>
(function(_, $) {
    _.tr('text_items_added', '{__("text_items_added")|escape:"javascript"}');

    $.ceEvent('on', 'ce.formpost_banners_form', function(frm, elm) {

        var banners = {};

        if ($('input.cm-item:checked', frm).length > 0) {
            $('input.cm-item:checked', frm).each( function() {
                var id = $(this).val();
                banners[id] = $('#banner_' + id).text();
            });

            {literal}
            $.cePicker('add_js_item', frm.data('caResultId'), banners, 'b', {
                '{banner_id}': '%id',
                '{banner}': '%item'
            });
            {/literal}

            $.ceNotification('show', {
                type: 'N',
                title: _.tr('notice'),
                message: _.tr('text_items_added'),
                message_state: 'I'
            });
        }

        return false;
    });

}(Tygh, Tygh.$));
</script>
{/if}
</head>

{include file="addons/banners/views/banners/components/banners_search_form.tpl" dispatch="banners.picker" extra="<input type=\"hidden\" name=\"result_ids\" value=\"pagination_`$smarty.request.data_id`\">" put_request_vars=true form_meta="cm-ajax" in_popup=true}

<form action="{$smarty.request.extra|to_relative_url|fn_url}" data-ca-result-id="{$smarty.request.data_id}" method="post" name="banners_form" enctype="multipart/form-data">

{include file="addons/banners/views/banners/components/banners_list.tpl" banners=$banners form_name="banners_form"}

{if $banners}
<div class="buttons-container">
    {include file="buttons/add_close.tpl" but_text=__("add_banners") but_close_text=__("add_banners_and_close") is_js=$smarty.request.extra|fn_is_empty}
</div>
{/if}

</form>
