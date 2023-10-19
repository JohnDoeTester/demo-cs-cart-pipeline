{if $product.master_product_id || !$product.company_id}
    {$form_close="form_close_`$product.best_product_offer_id`"}
    {$smarty.capture.$form_close nofilter}
{/if}
{if $addons.master_products.allow_buy_default_common_product === "YesNo::YES"|enum && $product.best_product_offer_id}
    {$shipping_estimation_product_id = $product.best_product_offer_id scope=parent}
{/if}
