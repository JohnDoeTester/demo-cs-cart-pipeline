{capture name="mainbox"}
<form action="{""|fn_url}" method="post" name="add_global_options" class="">

{if "ULTIMATE"|fn_allowed_for && $runtime.company_id}
    {assign var="company_id" value=$runtime.company_id}
{/if}

{include file="pickers/products/picker.tpl" data_id="added_products" input_name="apply_options[product_ids]" no_item_text=__("text_no_items_defined", ["[items]" => __("products")]) type="links" company_id=$company_id placement="right"}

{include file="common/subheader.tpl" title=__("select_options")}
<input type="hidden" name="apply_options[link]" value="Y" />
{foreach from=$product_options item="po"}
    <label class="checkbox">
        {$checked = $po.option_id|in_array:$option_ids}
        <input type="checkbox" value="{$po.option_id}" name="apply_options[options][]" {if $checked}checked="checked"{/if} />
        {$po.option_name} / {$po.internal_option_name}
        {include file="views/companies/components/company_name.tpl" object=$po}
    </label>
{/foreach}

{include file="buttons/button.tpl" but_id="add_global_options_submit" but_text=__("apply") but_name="dispatch[product_options.apply]" but_role="submit" but_meta="hidden"}

</form>
{/capture}

{capture name="buttons"}
<div class="btn-group">
    {include file="buttons/button.tpl" but_role="icon" but_text=__("apply") but_external_click_id="add_global_options_submit" but_meta="btn btn-primary cm-external-click"}
</div>
{/capture}

{include file="common/mainbox.tpl" title=__("apply_to_products") content=$smarty.capture.mainbox buttons=$smarty.capture.buttons select_languages=$select_languages}