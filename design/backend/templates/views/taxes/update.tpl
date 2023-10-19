{if $tax}
    {assign var="id" value=$tax.tax_id}
{else}
    {assign var="id" value=0}
{/if}

{capture name="mainbox"}

{capture name="tabsbox"}

<form action="{""|fn_url}" enctype="multipart/form-data" method="post" name="tax_form" class="form-horizontal form-edit {if ""|fn_check_form_permissions} cm-hide-inputs{/if}">
<input type="hidden" name="tax_id" value="{$id}" />
<input type="hidden" name="destination_id" value="{$destination_id}" />
<input type="hidden" name="selected_section" value="{$smarty.request.selected_section}" />

<div id="content_general">
<fieldset>
    {hook name="taxes:general_content"}
    <div class="control-group">
        <label for="elm_tax" class="control-label cm-required">{__("name")}:</label>
        <div class="controls">
            <input type="text" name="tax_data[tax]" id="elm_tax" size="30" value="{$tax.tax}" class="input-text-large main-input" />
        </div>
    </div>
    
    <div class="control-group">
        <label class="control-label" for="elm_regnumber">{__("regnumber")}:</label>
        <div class="controls">
            <input type="text" name="tax_data[regnumber]" id="elm_regnumber" size="30" value="{$tax.regnumber}" class="input-text" />
            <p class="muted description">{__("tt_views_taxes_update_regnumber")}</p>
        </div>
    </div>
    
    <div class="control-group">
        <label class="control-label" for="elm_priority">{__("priority")}:</label>
        <div class="controls">
            <input type="text" name="tax_data[priority]" id="elm_priority" size="5" value="{$tax.priority}" class="input-text" />
        </div>
    </div>
    
    <div class="control-group">
        <label for="elm_address_type" class="control-label cm-required">{__("rates_depend_on")}:</label>
        <div class="controls">
        <select name="tax_data[address_type]" id="elm_address_type">
            <option value="S" {if $tax.address_type == "S"}selected="selected"{/if}>{__("shipping_address")}</option>
            <option value="B" {if $tax.address_type == "B"}selected="selected"{/if}>{__("billing_address")}</option>
        </select>
        </div>
    </div>
    
    {include file="common/select_status.tpl" input_name="tax_data[status]" id="elm_tax_data" obj=$tax}
    
    <div class="control-group">
        <label class="control-label" for="elm_price_includes_tax">{__("price_includes_tax")}:</label>
        <div class="controls">
            <input type="hidden" name="tax_data[price_includes_tax]" value="N" />
            <input type="checkbox" name="tax_data[price_includes_tax]" id="elm_price_includes_tax" value="Y" {if $tax.price_includes_tax == "Y"}checked="checked"{/if} />
        </div>
    </div>
    {/hook}
</fieldset>
<!-- id="content_general" --></div>

<div id="content_tax_rates">

<div class="table-responsive-wrapper">
    <table class="table table-middle table--relative table-responsive">
    <thead>
    <tr>
        {hook name="taxes:rates_header"}
        <th>{__("rate_area")}</th>
        <th>{__("rate_value")}</th>
        <th>{__("type")}</th>
        {/hook}
    </tr>
    </thead>
    {foreach from=$destinations item=destination}
    {assign var="d_id" value=$destination.destination_id}
    <tr class="{if $destination.status === "ObjectStatuses::DISABLED"|enum}cm-row-status-d shipping-rate-area-d{/if}">
        {hook name="taxes:rates_item"}
        <td data-th="{__("rate_area")}">{$destination.destination}</td>
        <td data-th="{__("rate_value")}">
            <input type="hidden" name="tax_data[rates][{$d_id}][rate_id]" value="{$rates.$d_id.rate_id}" />
            <input type="text" name="tax_data[rates][{$d_id}][rate_value]" value="{$rates.$d_id.rate_value}" class="input-text" {if $destination.status === "ObjectStatuses::DISABLED"|enum}disabled{/if}/></td>
        <td data-th="{__("type")}">
            <select name="tax_data[rates][{$d_id}][rate_type]" {if $destination.status === "ObjectStatuses::DISABLED"|enum}disabled{/if}>
                <option value="F" {if $rates.$d_id.rate_type == "F"}selected="selected"{/if}>{__("absolute")} ({$currencies.$primary_currency.symbol nofilter})</option>
                <option value="P" {if $rates.$d_id.rate_type == "P"}selected="selected"{/if}>{__("percent")} (%)</option>
            </select>
        </td>
        {/hook}
    </tr>
    {/foreach}
    </table>
</div>
<!-- id="content_tax_rates" --></div>

{hook name="taxes:tabs_content"}
{/hook}

{capture name="buttons"}
    {include file="buttons/save_cancel.tpl" but_name="dispatch[taxes.update]" but_role="submit-link" but_target_form="tax_form" save=$id}
{/capture}

</form>

{hook name="taxes:tabs_extra"}
{/hook}

{/capture}
{include file="common/tabsbox.tpl" content=$smarty.capture.tabsbox track=true active_tab=$smarty.request.selected_section}

{/capture}

{include file="common/mainbox.tpl"
    title=($id) ? $tax.tax : __("new_tax")
    content=$smarty.capture.mainbox
    select_languages=true
    buttons=$smarty.capture.buttons
}
