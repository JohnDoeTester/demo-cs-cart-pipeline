{*
    $product array Product data
*}

<a href="{"products.update?product_id=`$product.product_id`"|fn_url}" title="{$product.product}">
    <small>
        {$product.product|truncate:50:"...":true}
    </small>
</a>
