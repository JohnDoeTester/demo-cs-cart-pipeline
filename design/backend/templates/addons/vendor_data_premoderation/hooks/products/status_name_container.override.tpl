{if !$runtime.company_id &&
    $product.status === "Addons\VendorDataPremoderation\ProductStatuses::REQUIRES_APPROVAL"|enum &&
    fn_check_permissions("premoderation", "m_approve", "admin")
}
    {hook name="products:status_name_container"}
        <div class="btn-group" id="product_status_{$id}_select">
            {$current_url = $config.current_url|escape:"url"}

            {btn type="text"
                id="premoderation_approve_{$product.product_id}"
                title=__("vendor_data_premoderation.approve_product", ["[product]" => $product.product])
                href="premoderation.m_approve?product_ids[]={$product.product_id}&redirect_url={$current_url}"
                icon="icon-thumbs-up"
                class="btn"
                method="POST"
            }

            {btn type="dialog"
                id="premoderation_disapprove_{$product.product_id}"
                title=__("vendor_data_premoderation.disapprove_product", ["[product]" => $product.product])
                href="premoderation.m_decline?product_ids[]={$product.product_id}&redirect_url={$current_url}"
                icon="icon-thumbs-down"
                class="btn"
                target_id="disapproval_reason_{$product.product_id}"
            }
        <!--product_status_{$id}_select--></div>
    {/hook}
{/if}