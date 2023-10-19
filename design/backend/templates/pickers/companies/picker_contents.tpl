{if !$smarty.request.extra}
<script>
(function(_, $) {

    _.tr('text_items_added', '{__("text_items_added")|escape:"javascript"}');
    var display_type = '{$smarty.request.display|escape:javascript nofilter}';

    $.ceEvent('on', 'ce.formpost_companies_form', function(frm, elm) {
        var companies = {};

        if ($('input.cm-item:checked', frm).length > 0) {
            $('input.cm-item:checked', frm).each( function() {
                var id = $(this).val();
                companies[id] = $('#company_' + id).text();
            });

            {literal}
            $.cePicker('add_js_item', frm.data('caResultId'), companies, 'm', {
                '{company_id}': '%id',
                '{company}': '%item'
            });
            {/literal}

            if (display_type != 'radio') {
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

{include file="views/companies/components/companies_search_form.tpl" dispatch="companies.picker" extra="<input type=\"hidden\" name=\"result_ids\" value=\"pagination_`$smarty.request.data_id`\">" put_request_vars=true form_meta="cm-ajax" in_popup=true}

<form action="{$smarty.request.extra|to_relative_url|fn_url}" data-ca-result-id="{$smarty.request.data_id}" method="post" name="companies_form">

{include file="common/pagination.tpl" div_id="pagination_`$smarty.request.data_id`"}

<div class="table-responsive-wrapper">
    <table width="100%" class="table table-middle table--relative table-responsive">
    <thead>
    <tr>
        <th width="1%" class="center">
            {if $smarty.request.display != "radio"}
            {include file="common/check_items.tpl"}</th>
            {/if}
        <th>{__("id")}</th>
        <th>{__("name")}</th>
        {if !"ULTIMATE"|fn_allowed_for}
            <th>{__("email")}</th>
        {/if}
        <th>{__("registered")}</th>
        {if !"ULTIMATE"|fn_allowed_for}
            <th class="right">{__("status")}</th>
        {/if}
    </tr>
    </thead>
    {foreach from=$companies item=company}
    <tr>
        <td class="center" data-th="">
            {if $smarty.request.display == "radio"}
            <input type="radio" name="{$smarty.request.checkbox_name|default:"companies_ids"}" value="{$company.company_id}" class="radio" />
            {else}
            <input type="checkbox" name="{$smarty.request.checkbox_name|default:"companies_ids"}[{$company.company_id}]" value="{$company.company_id}" class="cm-item" />
            {/if}
        </td>
        <td data-th="{__("id")}"><a href="{"companies.update?company_id=`$company.company_id`"|fn_url}">&nbsp;<span>{$company.company_id}</span>&nbsp;</a></td>
        <td data-th="{__("name")}"><a id="company_{$company.company_id}" href="{"companies.update?company_id=`$company.company_id`"|fn_url}">{$company.company}</a></td>
        {if !"ULTIMATE"|fn_allowed_for}
            <td data-th="{__("email")}"><a href="mailto:{$company.email}">{$company.email}</a></td>
        {/if}
        <td data-th="{__("registered")}">{$company.timestamp|date_format:"`$settings.Appearance.date_format`, `$settings.Appearance.time_format`"}</td>
        {if !"ULTIMATE"|fn_allowed_for}
            <td class="right" data-th="{__("active")}">{if $company.status != "A"}{__("disable")}{else}{__("active")}{/if}</td>
        {/if}
    </tr>
    {foreachelse}
    <tr>
        {if !"ULTIMATE"|fn_allowed_for}
            <td colspan="6"><p class="no-items">{__("no_data")}</p></td>
        {else}
            <td colspan="4"><p class="no-items">{__("no_data")}</p></td>
        {/if}
    </tr>
    {/foreach}
    </table>
</div>

{include file="common/pagination.tpl" div_id="pagination_`$smarty.request.data_id`"}

<div class="buttons-container">
    {if $smarty.request.display == "radio"}
        {$but_close_text = $picker_but_close_text|default:__("choose")}
    {else}
        {$but_close_text = $picker_but_close_text|default:__("add_companies_and_close")}
        {$but_text = $picker_but_text|default:__("add_companies")}
    {/if}
    {include file="buttons/add_close.tpl" is_js=$smarty.request.extra|fn_is_empty}
</div>

</form>
