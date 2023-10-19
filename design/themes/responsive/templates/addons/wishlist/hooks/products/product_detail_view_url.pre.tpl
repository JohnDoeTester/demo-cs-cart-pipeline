{if $product.combination}
    {$product_detail_view_url = fn_url(fn_link_attach($product_detail_view_url, "combination=`$product.combination`")) scope="parent"}
{/if}