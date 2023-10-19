{if ($product.master_product_id || !$product.company_id) && $is_allow_add_common_products_to_cart_list}
    {$obj_id = $product.best_product_offer_id scope=parent}
{/if}