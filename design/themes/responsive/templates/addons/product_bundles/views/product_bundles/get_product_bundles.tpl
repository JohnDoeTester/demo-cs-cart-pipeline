{*
    Import
    ---
    $product
    $in_popup
*}

{script src="js/tygh/exceptions.js"}

<div class="ty-product-bundles-get-product-bundles
    {if $in_popup}
        ty-product-bundles-get-product-bundles--popup
    {/if}
    "
    {if $in_popup}
        data-ca-product-bundles="getProductBundlesPopupContent"
    {/if}
>
    {component
        name="product_bundles.product_bundles"
        bundle_id=$bundle.bundle_id
        show_header=false
        show_block_header=false
        enable_padding=false
    }{/component}
</div>
