{if $product_data.variation_features}
    {include file="addons/product_variations/views/product_variations/components/variation_features.tpl"
        variation_features=$product_data.variation_features
        features_split=true
        features_inline=true
        features_secondary=true
    }
{/if}
