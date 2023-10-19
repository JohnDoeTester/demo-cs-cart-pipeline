{if ($runtime.controller === "product_bundles" || $extra_mode === "product_bundles") && $product_info}
    <td>
        <input type="hidden" id="item_price_product_bundle_{$item.bundle_id}_{$delete_id}" value="{$product_info.price|default:0}" />
        {include file="common/price.tpl" value=$product_info.price}
    </td>
    <td>
        <select name="{$input_name}[modifier_type]" class="input-medium" id="item_modifier_type_product_bundle_{$item.bundle_id}_{$delete_id}">
            <option value="by_fixed" {if $product_info.modifier_type == "by_fixed"}selected="selected"{/if}>{__("absolute")}  ({$currencies.$primary_currency.symbol nofilter})</option>
            <option value="by_percentage" {if $product_info.modifier_type == "by_percentage"}selected="selected"{/if}>{__("percent")} (%)</option>
        </select>
    </td>
    <td>
        <input type="hidden" class="cm-bundle-{$item.bundle_id}" value="{$delete_id}" />
        <input type="text" name="{$input_name}[modifier]" id="item_modifier_product_bundle_{$item.bundle_id}_{$delete_id}" size="4" value="{$product_info.modifier|default:0}" class="input-mini">
    </td>
    <td>
        {include file="common/price.tpl" 
            value=$product_info.discounted_price|default:$product_info.price 
            span_id="item_discounted_price_product_bundle_`$item.bundle_id`_`$delete_id`_"
        }
    </td>
    <td>
        <input type="hidden" id="item_show_product_bundle_{$item.bundle_id}_product_bundle_id" name="{$input_name}[show_on_product_page]" value="{"YesNo::NO"|enum}">
        <input type="checkbox" id="item_show_product_bundle_{$item.bundle_id}_product_bundle_id" name="{$input_name}[show_on_product_page]" {if $product_info.show_on_product_page !== "YesNo::NO"|enum}checked="checked"{/if} value="{"YesNo::YES"|enum}">
    </td>

{elseif ($runtime.controller === "product_bundles" || $extra_mode === "product_bundles") && $clone}
    <td>
        <input type="text" class="hidden" id="item_price_product_bundle_{$item.bundle_id}_{$ldelim}product_bundle_id{$rdelim}" value="{$ldelim}price{$rdelim}">
        {include file="common/price.tpl" span_id="item_display_price_product_bundle_`$item.bundle_id`_`$ldelim`product_bundle_id`$rdelim`_"}
    </td>
    <td>
        <select name="{$input_name}[modifier_type]" class="input-medium" id="item_modifier_type_product_bundle_{$item.bundle_id}_{$ldelim}product_bundle_id{$rdelim}">
            <option value="by_fixed">{__("absolute")} ({$currencies.$primary_currency.symbol nofilter})</option>
            <option value="by_percentage">{__("percent")} (%)</option>
        </select>
    </td>
    <td>
        <input type="text" class="cm-bundle-{$item.bundle_id} hidden" value="{$ldelim}product_bundle_id{$rdelim}" />
        <input type="text" class="hidden" id="{$ldelim}product_bundle_id{$rdelim}" value="{$item.bundle_id}" />
        <input type="text" name="{$input_name}[modifier]" id="item_modifier_product_bundle_{$item.bundle_id}_{$ldelim}product_bundle_id{$rdelim}" size="4" value="0" class="input-mini">
    </td>
    <td>
        {include file="common/price.tpl" span_id="item_discounted_price_product_bundle_`$item.bundle_id`_`$ldelim`product_bundle_id`$rdelim`_"}
    </td>
    <td>
        <input type="checkbox" id="item_show_product_bundle_{$item.bundle_id}_{$ldelim}product_bundle_id{$rdelim}" name="{$input_name}[show_on_product_page]" value="{"YesNo::YES"|enum}" checked="checked">
    </td>
{/if}