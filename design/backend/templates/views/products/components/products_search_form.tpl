{if $in_popup}
    <div class="adv-search">
    <div class="group">
{else}
    <div class="sidebar-row">
    <h6>{__("admin_search_title")}</h6>
{/if}

{if $page_part}
    {$_page_part="#`$page_part`"}
{/if}

<form action="{""|fn_url}{$_page_part}" name="{$product_search_form_prefix}search_form" method="get" class="cm-disable-empty-all {$form_meta}" id="search_form">
<input type="hidden" name="type" value="{$search_type|default:"simple"}" autofocus="autofocus" />
{if $smarty.request.redirect_url}
    <input type="hidden" name="redirect_url" value="{$smarty.request.redirect_url}" />
{/if}
{if $selected_section != ""}
    <input type="hidden" id="selected_section" name="selected_section" value="{$selected_section}" />
{/if}
<input type="hidden" name="pcode_from_q" value="Y" />

{if $put_request_vars}
    {array_to_fields data=$smarty.request skip=["callback"] escape_all=true}
{/if}

{$extra nofilter}

{capture name="simple_search"}
    {hook name="products:simple_search"}
    <div class="sidebar-field">
        <label>{__("find_results_with")}</label>
        <input type="text" name="q" size="20" value="{$search.q}" />
    </div>

    <div class="sidebar-field">
        <label>{__("price")}&nbsp;({$currencies.$primary_currency.symbol nofilter})</label>
        <input type="text" name="price_from" size="1" value="{$search.price_from}" onfocus="this.select();" class="input-small" /> - <input type="text" size="1" name="price_to" value="{$search.price_to}" onfocus="this.select();" class="input-small" />
    </div>

    <div class="sidebar-field">
        <label>{__("search_in_category")}</label>
        {if $search.cid|is_array}
            {$s_cid = (($search.cid|count) === 1) ? ($search.cid|reset) : (0)}
        {else}
            {$s_cid = ($search.cid)}
        {/if}

        {if "categories"|fn_show_picker:$smarty.const.CATEGORY_THRESHOLD}
            <div class="controls">
            {include file="views/categories/components/picker/picker.tpl"
                input_name="cid"
                show_advanced=true
                multiple=false
                show_empty_variant=true
                item_ids=[$s_cid]
                empty_variant_text=__("all_categories")
                dropdown_css_class="object-picker__dropdown--categories"
                object_picker_advanced_btn_class="cm-dialog-destroy-on-close"
            }
            </div>
        {else}
            {include file="views/categories/components/picker/picker.tpl"
                input_name="cid"
                show_advanced=true
                multiple=false
                show_empty_variant=true
                item_ids=[$s_cid]
                empty_variant_text=__("all_categories")
                dropdown_css_class="object-picker__dropdown--categories"
                object_picker_advanced_btn_class="cm-dialog-destroy-on-close"
            }
        {/if}
    </div>
    {/hook}
{/capture}

{capture name="advanced_search"}
{** Products advanced search form hook *}
{hook name="products:advanced_search"}
    <div class="group form-horizontal">
    <div class="control-group">
    <label>{__("search_in")}</label>
    <div class="table-wrapper">
        <table width="100%">
            <tr class="nowrap">
                <td><label for="pname" class="checkbox inline"><input type="checkbox" value="Y" {if $search.pname == "Y"}checked="checked"{/if} name="pname" id="pname" />{__("product_name")}</label></td>
                <td><label for="pshort" class="checkbox inline"><input type="checkbox" value="Y" {if $search.pshort == "Y"}checked="checked"{/if} name="pshort" id="pshort"  />{__("short_description")}</label></td>
                <td><label for="pfull" class="checkbox  inline"><input type="checkbox" value="Y" {if $search.pfull == "Y"}checked="checked"{/if} name="pfull" id="pfull" />{__("full_description")}</label></td>
                <td><label for="pkeywords" class="checkbox  inline"><input type="checkbox" value="Y" {if $search.pkeywords == "Y"}checked="checked"{/if} name="pkeywords" id="pkeywords"  />{__("keywords")}</label></td>
            </tr>
        </table>
    </div>
    </div>
</div>

<div class="group form-horizontal">
{if $filter_items}
    <div class="control-group">

        <a href="#" class="search-link cm-combination open cm-save-state" id="sw_filter">
        <span id="on_filter" class="icon-caret-right cm-save-state {if $smarty.cookies.filter}hidden{/if}"> </span>
        <span id="off_filter" class="icon-caret-down cm-save-state {if !$smarty.cookies.filter}hidden{/if}"></span>
        {__("search_by_product_filters")}</a>

        <div id="filter"{if !$smarty.cookies.filter} class="hidden"{/if}>
            {include file="views/products/components/advanced_search_form.tpl" filter_features=$filter_items prefix="filter_" data_name="filter_variants"}
        </div>
    </div>
{/if}
</div>

{if $feature_items}
<div class="group form-horizontal">
    <div class="control-group">

        <a class="search-link cm-combination nowrap open cm-save-state" id="sw_feature"><span id="on_feature" class="cm-combination cm-save-state {if $smarty.cookies.feature}hidden{/if}"><span class="icon-caret-right"></span></span><span id="off_feature" class="cm-combination cm-save-state {if !$smarty.cookies.feature}hidden{/if}"><span class="icon-caret-down"></span></span>{__("search_by_product_features")}</a>

        <div id="feature"{if !$smarty.cookies.feature} class="hidden"{/if}>
            {include file="views/products/components/advanced_search_form.tpl" filter_features=$feature_items prefix="feature_" data_name="feature_variants"}
        </div>
    </div>
</div>
{elseif $feature_items_too_many}
<div class="group form-horizontal">
    {__("error_features_too_many_variants")}
</div>
{/if}

<div class="row-fluid">
<div class="group span6">
    <div class="form-horizontal">
        <div class="control-group">
            <label for="pcode" class="control-label">{__("search_by_sku")}</label>
            <div class="controls">
                <input type="text" name="pcode" id="pcode" value="{$search.pcode}" onfocus="this.select();"/>
            </div>
        </div>
        <div class="control-group">
            <label class="control-label" for="popularity_from">{__("popularity")}</label>
            <div class="controls">
                <input type="text" name="popularity_from" id="popularity_from" value="{$search.popularity_from}" onfocus="this.select();" class="input-mini" /> - <input type="text" name="popularity_to" value="{$search.popularity_to}" onfocus="this.select();" class="input-mini" />
                <p class="muted description">{__("ttc_popularity")}</p>
            </div>
        </div>
        <div class="control-group">
            <label class="control-label" for="subcats">{__("subcategories")}</label>
            <div class="controls">
                <input type="hidden" name="subcats" value="N" />
                <input type="checkbox" value="Y"{if $search.subcats == "Y" || !$search.subcats} checked="checked"{/if} name="subcats"  id="subcats" />
            </div>
        </div>
    </div>
</div>

<div class="group span6 form-horizontal">
    <div class="control-group">
        <label class="control-label" for="shipping_freight_from">{__("shipping_freight")}&nbsp;({$currencies.$primary_currency.symbol nofilter})</label>
        <div class="controls">
            <input type="text" name="shipping_freight_from" id="shipping_freight_from" value="{$search.shipping_freight_from}" onfocus="this.select();" class="input-mini" /> - <input type="text" name="shipping_freight_to" value="{$search.shipping_freight_to}" onfocus="this.select();" class="input-mini" />
        </div>
    </div>

    <div class="control-group">
        <label class="control-label" for="weight_from">{__("weight")}&nbsp;({$settings.General.weight_symbol})</label>
        <div class="controls">
            <input type="text" name="weight_from" id="weight_from" value="{$search.weight_from}" onfocus="this.select();" class="input-mini" /> - <input type="text" name="weight_to" value="{$search.weight_to}" onfocus="this.select();" class="input-mini" />
        </div>
    </div>

    {$have_amount_filter=0}
    {foreach $filter_items as $ff}
        {if $ff.field_type eq "A"}
            {$have_amount_filter=1}
        {/if}
    {/foreach}
    {if !$have_amount_filter}
    <div class="control-group">
        <label class="control-label" for="amount_from">{__("quantity")}:</label>
        <div class="controls">
            <input type="text" name="amount_from" id="amount_from" value="{$search.amount_from}" onfocus="this.select();" class="input-mini" /> - <input type="text" name="amount_to" value="{$search.amount_to}" onfocus="this.select();" class="input-mini" />
        </div>
    </div>
    {/if}

    {hook name="companies:products_advanced_search"}
    {if $picker_selected_company|default:""|fn_string_not_empty}
        <input type="hidden" name="company_id" value="{$picker_selected_company}" />
    {else}
        {include file="common/select_vendor.tpl"}
    {/if}
    {/hook}

</div>
</div>

<div class="row-fluid">
    <div class="group span6 form-horizontal">
        <div class="control-group">
            <label class="control-label" for="free_shipping">{__("free_shipping")}</label>
            <div class="controls">
            <select name="free_shipping" id="free_shipping">
                <option value="">--</option>
                <option value="Y" {if $search.free_shipping == "Y"}selected="selected"{/if}>{__("yes")}</option>
                <option value="N" {if $search.free_shipping == "N"}selected="selected"{/if}>{__("no")}</option>
            </select>
            </div>
        </div>

        <div class="control-group">
            <label for="status" class="control-label">{__("status")}</label>
            <div class="controls">
            <select name="status" id="status">
                <option value="">--</option>
                {foreach fn_get_all_product_statuses() as $status_id => $status_name}
                    <option value="{$status_id}"
                            {if $search.status === $status_id}
                                selected="selected"
                            {/if}
                    >{$status_name}</option>
                {/foreach}
            </select>
            </div>
        </div>
                {** Hook for additional fields in the products search form *}
        {hook name="products:search_form"}{/hook}
    </div>

    <div class="group span6 form-horizontal">
        {** The 'Search in orders' field hook *}
        {hook name="products:search_in_orders"}
        <div class="control-group">
            <label class="control-label" for="popularity_from">{__("purchased_in_orders")}</label>
            <div class="right">
                {include file="pickers/orders/picker.tpl" item_ids=$search.order_ids no_item_text=__("no_items") data_id="order_ids" input_name="order_ids" view_mode="simple"}
            </div>
        </div>
        {/hook}
        <div class="control-group">
            <label class="control-label" for="sort_by">{__("sort_by")}</label>
            <div class="controls">
            <select class="select-mini" name="sort_by" id="sort_by">
                <option {if $search.sort_by == "list_price"}selected="selected"{/if} value="list_price">{__("list_price")}</option>
                <option {if $search.sort_by == "product"}selected="selected"{/if} value="product">{__("name")}</option>
                <option {if $search.sort_by == "price"}selected="selected"{/if} value="price">{__("price")}</option>
                <option {if $search.sort_by == "code"}selected="selected"{/if} value="code">{__("sku")}</option>
                <option {if $search.sort_by == "amount"}selected="selected"{/if} value="amount">{__("quantity")}</option>
                <option {if $search.sort_by == "status"}selected="selected"{/if} value="status">{__("status")}</option>
                {hook name="products:select_search"}
                {/hook}
            </select> -
            <select class="select-mini" name="sort_order" id="sort_order">
                <option {if $search.sort_order_rev == "asc"}selected="selected"{/if} value="desc">{__("desc")}</option>
                <option {if $search.sort_order_rev == "desc"}selected="selected"{/if} value="asc">{__("asc")}</option>
            </select>
            </div>
        </div>
    </div>
</div>

<div class="group form-horizontal">
    <div class="control-group">
        <label class="control-label">{__("creation_date")}</label>
        <div class="controls">
            {include file="common/period_selector.tpl" period=$search.period form_name="{$product_search_form_prefix}search_form"}
        </div>
    </div>
</div>

<div class="row-fluid">
    <div class="group span6 form-horizontal">
        <div class="control-group">
            <label class="control-label" for="updated_in_hours">{__("updated_last")}</label>
            <div class="controls">
                <input type="text" name="updated_in_hours" id="updated_in_hours" value="{$search.updated_in_hours}" onfocus="this.select();" class="input-mini" />&nbsp;&nbsp;{__("hour_or_hours")}
            </div>
        </div>
    </div>
</div>

{/hook}
{/capture}

{include file="common/advanced_search.tpl" simple_search=$smarty.capture.simple_search advanced_search=$smarty.capture.advanced_search dispatch=$dispatch view_type="products" in_popup=$in_popup is_order_management=$is_order_management}

<!--search_form--></form>
{if $in_popup}
    </div></div>
{else}
    </div><hr>
{/if}
