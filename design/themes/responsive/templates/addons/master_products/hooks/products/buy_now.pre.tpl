{if ($product.master_product_id || !$product.company_id) && $addons.master_products.allow_buy_default_common_product === "YesNo::YES"|enum && !$product.is_vendor_products_list_item}
    {$compare_product_id = $product.best_product_offer_id scope=parent}
{/if}