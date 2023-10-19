{*
    $product array Product data
*}

<a href="{"products.view?product_id=`$product.product_id`"|fn_url}" title="{$product.product}">
    {$product.product|truncate:60:"...":true}
</a>
