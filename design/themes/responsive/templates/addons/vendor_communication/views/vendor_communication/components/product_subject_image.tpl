{*
    $product array Product data
*}

<a href="{"products.view?product_id=`$product.product_id`"|fn_url}">
    {include file="common/image.tpl" images=$product.main_pair.detailed image_width="60" image_height="60"}
</a>
