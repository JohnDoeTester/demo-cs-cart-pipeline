{$approved_status = $product_data.status !== "Addons\VendorDataPremoderation\ProductStatuses::DISAPPROVED"|enum
    && $product_data.status !== "Addons\VendorDataPremoderation\ProductStatuses::REQUIRES_APPROVAL"|enum
}{if $runtime.company_id && !$approved_status}
    {$non_editable = true scope=parent}
{elseif !$runtime.company_id && !$approved_status}
    {$data_product_statuses = [
        "data-ca-product-statuses" => "true",
        "data-ca-product-statuses-disapproved" => "Addons\VendorDataPremoderation\ProductStatuses::DISAPPROVED"|enum,
        "data-ca-product-statuses-requires-approval" => "Addons\VendorDataPremoderation\ProductStatuses::REQUIRES_APPROVAL"|enum
    ] scope=parent}
{/if}{if $product_data.status === "Addons\VendorDataPremoderation\ProductStatuses::DISAPPROVED"|enum}
    {$product_status_style = "text-error" scope=parent}
{elseif $product_data.status === "Addons\VendorDataPremoderation\ProductStatuses::REQUIRES_APPROVAL"|enum}
    {$product_status_style = "text-warning" scope=parent}
{/if}