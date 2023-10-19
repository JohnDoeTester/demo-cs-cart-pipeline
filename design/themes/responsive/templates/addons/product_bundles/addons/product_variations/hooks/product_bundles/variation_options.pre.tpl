{*
    Import
    ---
    $bundle
    $bundle_product
    $variants

    Local
    ---
    $feature

    Export
    ---
    $variants
*}

{if $bundle_product.any_variation === "YesNo::YES"|enum
    && ($bundle_product.parent_variation_product
        || ($bundle_product.product_data.variation_features_variants)
    )
}
    {* Add the selected feature variants to the $variants array *}
    {foreach $bundle_product.product_data.variation_features as $feature}
        {if $feature.purpose !== "\Tygh\Addons\ProductVariations\Product\FeaturePurposes::CREATE_VARIATION_OF_CATALOG_ITEM"|constant}
            {continue}
        {/if}

        {$variants[] = $feature.variant}
    {/foreach}

    {* Export *}
    {$variants = $variants scope=parent}
{/if}
