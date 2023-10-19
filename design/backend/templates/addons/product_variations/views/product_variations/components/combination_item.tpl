<tr
    class="cm-variations-generator__parent-combination-{$combination.parent_combination_id} cm-variations-generator__combination-{$combination.combination_id} cm-variations-generator__combination-row"
    data-ca-combination-id="{$combination.combination_id}"
    data-ca-parent-combination-id="{$combination.parent_combination_id}"
>
    <td width="40">
        <input type="hidden" value="0" name="combinations_data[{$combination.combination_id}][active]" />
        <input type="checkbox"
            value="1"
            name="combinations_data[{$combination.combination_id}][active]"
            {if $combination.active}checked="checked" {/if}
            {if $combination.linked || $combinations_count === 1}disabled="disabled" {/if}
            class="cm-item cm-variations-generator__combination-activity-checbox"
        />
        {if $combination.linked || $combinations_count === 1}
            <input type="hidden" value="1" name="combinations_data[{$combination.combination_id}][active]" />
        {/if}
    </td>
    <td width="40">
        {if !$combination.parent_combination_id && $combination.has_children}
            <button alt="{__("expand_collapse_list")}" title="{__("expand_collapse_list")}" id="sw_product_variations_group_{$combination.combination_id}" aaaid="on_variations" class="cm-combinations cm-product-variations__collapse product-variations__collapse-btn product-variations__collapse-btn--collapsed" type="button">
                <span class="icon-caret-down" data-ca-switch-id="product_variations_group_{$combination.combination_id}"> </span>
                <span class="icon-caret-right hidden" data-ca-switch-id="product_variations_group_{$combination.combination_id}"> </span>
            </button>
        {else}
            &nbsp;
        {/if}
    </td>
    <td>
        {if $product_data.product_id == $combination.product_id}
            <strong>
        {/if}
        {foreach $combination.variant_names as $variant_name}
            {$variant_name}{if !$variant_name@last} • {/if}
        {/foreach}
        {if $product_data.product_id == $combination.product_id}
            </strong>
        {/if}
    </td>
    <td {if !$combination.parent_combination_id}data-th="{__("name")}"{/if}>
        {if !$combination.parent_combination_id}
            <input type="text" name="combinations_data[{$combination.combination_id}][product_name]" value="{$combination.product_name}" class="input-full input-hidden" />

            {if !$combination.exists || !$combination.linked}
                <input type="hidden" class="cm-variations-generator__combination-set-as-default-input" name="combinations_data[{$combination.combination_id}][set_as_default]" value="1"/>
            {/if}
        {elseif !$combination.exists && $combination.active && !$combination.parent_combination_exists}
            <input type="hidden" class="cm-variations-generator__combination-set-as-default-input" name="combinations_data[{$combination.combination_id}][set_as_default]" value="1" id="combination_item_{$combination.combination_id}_set_as_default_input" disabled="disabled" />
            <a hre="#" data-ca-input-selector="#combination_item_{$combination.combination_id}_set_as_default_input" class="hidden-tools cm-variations-generator__combination-set-default-link">{__("product_variations.mark_main_product")}</a>
        {/if}
    </td>

    <td width="13%" data-th="{__("sku")}">
        <input type="text" name="combinations_data[{$combination.combination_id}][product_code]" value="{$combination.product_code}" class="input-full input-hidden" />
    </td>

    <td width="13%" data-th="{__("price")}">
        <input type="text" name="combinations_data[{$combination.combination_id}][product_price]" value="{$combination.product_price|fn_format_price:$primary_currency:null:false}" class="input-full input-hidden"/>
    </td>
    <td width="9%" data-th="{__("quantity")}">
        <input type="text" name="combinations_data[{$combination.combination_id}][product_amount]" size="6" value="{$combination.product_amount}" class="input-full input-hidden cm-value-decimal" />
    </td>
</tr>
