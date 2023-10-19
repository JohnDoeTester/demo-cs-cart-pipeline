{if $product.master_product_id || !$product.company_id}
    {$obj_id = $product.product_id scope=parent}
{/if}