{if !$product.company_id}
    {$items_status = $items_status|unset_key:{"Addons\VendorDataPremoderation\ProductStatuses::DISAPPROVED"|enum} scope=parent}
{/if}