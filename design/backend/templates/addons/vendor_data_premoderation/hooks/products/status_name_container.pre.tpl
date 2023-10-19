{$approved_status = $product.status !== "Addons\VendorDataPremoderation\ProductStatuses::DISAPPROVED"|enum
    && $product.status !== "Addons\VendorDataPremoderation\ProductStatuses::REQUIRES_APPROVAL"|enum
}{if $runtime.company_id && !$approved_status}
    {$non_editable = true scope=parent}
{/if}{if $product.premoderation_reason && !$approved_status}
    {$popup_additional_class = "dropdown--inline" scope=parent}

    {include file = "common/tooltip.tpl"
        tooltip = "{$product.premoderation_reason|nl2br nofilter}"
    }
{/if}