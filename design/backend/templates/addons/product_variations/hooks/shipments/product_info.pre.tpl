{if $oi.variation_features || $product.variation_features}

    {if $oi.variation_features}
        {* Shipment details *}
        {$variation_features = $oi.variation_features}
    {else $product.variation_features}
        {* New shipment *}
        {$variation_features = $product.variation_features}
    {/if}

    {include file="addons/product_variations/views/product_variations/components/variation_features.tpl"
        variation_features=$variation_features
        features_secondary=true
    }
{/if}
