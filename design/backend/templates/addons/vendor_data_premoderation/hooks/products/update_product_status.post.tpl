{if $product_data.premoderation_reason &&
    ($runtime.company_id && $product_data.status === "Addons\VendorDataPremoderation\ProductStatuses::REQUIRES_APPROVAL"|enum ||
    $product_data.status === "Addons\VendorDataPremoderation\ProductStatuses::DISAPPROVED"|enum)
}
    <div class="control-group">
        <label for="elm_disapproval_reason" class="control-label">
            {__("vendor_data_premoderation.disapproval_reason")}:
        </label>
        <div class="controls">
            <p>{$product_data.premoderation_reason}</p>
        </div>
    </div>
{/if}