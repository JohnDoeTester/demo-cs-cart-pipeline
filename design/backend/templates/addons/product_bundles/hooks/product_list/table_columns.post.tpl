<input type="hidden" id="price_{$product.product_id}" value="{$product.base_price}" />
{foreach $product.product_options as $option_id => $option}
    {foreach $option.variants as $variant_id => $variant}
        {if $variant.modifier != 0}
            {if $variant.modifier_type == "A"}
                {$op_modifier = $variant.modifier}
            {else}
                {math equation="(price / 100) * modifier" price=$product.base_price modifier=$variant.modifier assign="op_modifier"}
            {/if}
            <input type="hidden" id="product_bundle_option_modifier_{$option_id}_{$variant_id}" value="{$op_modifier}" />
        {/if}
    {/foreach}
{/foreach}