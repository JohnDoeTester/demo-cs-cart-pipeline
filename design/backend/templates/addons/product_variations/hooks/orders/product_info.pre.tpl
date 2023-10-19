{if $cp.variation_features}
    {include file="addons/product_variations/views/product_variations/components/variation_features.tpl"
        variation_features=$cp.variation_features
        features_secondary=true
    }
{/if}
