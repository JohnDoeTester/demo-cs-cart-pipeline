<input type="hidden" name="product_id" value="{$product.product_id}" />

{if $product.master_product_id && !$product.company_id}
    {$obj_id = $product.best_product_offer_id scope=parent}
{/if}