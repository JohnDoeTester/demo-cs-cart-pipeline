{if $product.master_product_id || !$product.company_id}
    {$form_close="form_close_`$product.best_product_offer_id`"}
{else}
    {$form_close="form_close_`$obj_id`"}
{/if}
{$smarty.capture.$form_close nofilter}