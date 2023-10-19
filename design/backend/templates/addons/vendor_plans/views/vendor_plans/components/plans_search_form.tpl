<div class="sidebar-row">
<h6>{__("admin_search_title")}</h6>

<form name="companies_search_form" action="{""|fn_url}" method="get" class="{$form_meta}">

    <div class="sidebar-field">
        <label for="elm_name">{__("name")}</label>
        <input type="text" name="plan" id="elm_name" value="{$search.plan}" />
    </div>

    <div class="sidebar-field">
        <label for="status" class="control-label">{__("status")}</label>
        <select name="status" id="status">
            <option value="">--</option>
            <option value="A" {if $search.status == "A"}selected="selected"{/if}>{__("active")}</option>
            <option value="H" {if $search.status == "H"}selected="selected"{/if}>{__("hidden")}</option>
            <option value="D" {if $search.status == "D"}selected="selected"{/if}>{__("disabled")}</option>
        </select>
    </div>

    <div class="sidebar-field">
        <label for="price_from">{__("price")}&nbsp;({$currencies.$primary_currency.symbol nofilter})</label>
        <input type="text" class="input-small" name="price_from" id="price_from" value="{$search.price_from}" size="3" /> - <input type="text" class="input-small" name="price_to" value="{$search.price_to}" size="3" />
    </div>

    {include file="buttons/search.tpl" but_name="dispatch[{$dispatch}]"}
</form>
