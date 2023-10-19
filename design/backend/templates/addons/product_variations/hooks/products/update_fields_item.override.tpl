{if $product.variation_features && $field === "product"}
    <tr>
        <td valign="top" class="nowrap pad strong">{$v.$field}:&nbsp;</td>
        <td valign="top" class="pad nowrap">
            {if $product.parent_product_id}
                <div>{$product.product}</div>
                <input
                    type="hidden"
                    value="{$product.product}"
                    name="{$name}[{$product.product_id}][product]"
                />
            {else}
                <input 
                    type="text"
                    value="{$product.product}"
                    class="input-medium input--no-margin"
                    name="{$name}[{$product.product_id}][product]"
                />
            {/if}
            <div>
                {if $product.variation_features}
                    {include file="addons/product_variations/views/product_variations/components/variation_features.tpl"
                        variation_features=$product.variation_features
                        features_inline=true
                        features_mini=true
                        features_secondary=true
                    }
                {/if}
            </div>
        </td>
    </tr>
{/if}
{if !$product['type']->isFieldAvailable($field)}
    <!-- Overridden by the Product Variations add-on -->
{/if}
