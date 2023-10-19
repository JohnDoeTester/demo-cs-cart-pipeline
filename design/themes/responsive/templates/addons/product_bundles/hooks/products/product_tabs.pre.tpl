{*
    Import
    ---
    $product
*}

{if $addons.product_bundles.show_bundles === "before_product_description"}
    {script src="js/tygh/exceptions.js"}
    {component
        name="product_bundles.product_bundles"
        product_id=$product.product_id
        show_on_products_page=true
    }{/component}
{/if}
