{if !$runtime.company_id && !$object.company_id && $object.product_id && !$clone}
    {$object.company_name = __("master_products.all_vendors_master_product") scope=parent}
{/if}