{if $_product.product_id && $_product.variation_features}
    {* Buy together feature variations *}
    {$variation_features = $_product.variation_features}
{elseif !$_product.product_id && $product.variation_features}
    {* Feature variations *}
    {$variation_features = $product.variation_features}
{/if}

{if $variation_features}
    {include file="addons/product_variations/views/product_variations/components/variation_features.tpl"
        variation_features=$variation_features
        features_secondary=true
    }
{/if}
