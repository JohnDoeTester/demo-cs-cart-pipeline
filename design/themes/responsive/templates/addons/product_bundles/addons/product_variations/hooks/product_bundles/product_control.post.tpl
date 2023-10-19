{*
    Import
    ---
    $bundle
    $bundle_product_key
    $bundle_product

    Global
    ---
    $return_current_url
*}

{if $bundle_product.any_variation === "YesNo::YES"|enum
    && ($bundle_product.parent_variation_product
        || ($bundle_product.product_data.variation_features_variants)
    )
}
    <a id="opener_product_bundle_features_{$bundle.bundle_id}_{$bundle_product_key}"
        class="cm-dialog-opener cm-dialog-auto-size ty-product-bundles-product-item__control-link"
        href="{"product_bundles.get_feature_variants?bundle_id=`$bundle.bundle_id`&product_id=`$bundle_product.product_id`&bundle_product_key=`$bundle_product_key`&return_url=`$config.current_url|escape:url`"|fn_url}"
        data-ca-target-id="content_product_bundle_features_{$bundle.bundle_id}_{$bundle_product_key}"
        data-ca-dialog-title="{__("product_bundles.specify_features")}"
        rel="nofollow"
    >
        {__("product_bundles.specify_features")}
    </a>
{/if}
