{$show_premoderation_reason = !$runtime.company_id
    && $product_data.status === "Addons\VendorDataPremoderation\ProductStatuses::REQUIRES_APPROVAL"|enum}

{if $show_premoderation_reason && fn_check_permissions("premoderation", "m_approve", "admin")}
    {$status = $obj.status|default:""}
    {$items_status = $items_status|default:($status|fn_get_product_statuses:$hidden)}
    {$non_editable = $non_editable_status|default:false}

    {hook name="products:update_product_status_container"}
        <div data-ca-product-status-container="true">
            <div class="control-group">
                <label class="control-label cm-required">{__("status")}:</label>
                <div class="controls">
                    <input
                        type="hidden"
                        name="product_data[status]"
                        class="product__status-switcher"
                        id="elm_product_status_0_{"Addons\VendorDataPremoderation\ProductStatuses::DISAPPROVED"|enum}"
                        value={"Addons\VendorDataPremoderation\ProductStatuses::DISAPPROVED"|enum}
                        data-ca-product-status-switcher="true"
                        disabled
                    >
                    <div class="btn-group" id="product_status_{$id}_select">
                        {$current_url = $config.current_url|escape:"url"}
                        {btn type="text"
                            id="premoderation_approve"
                            text=__("vendor_data_premoderation.approve_product_btn")
                            href="premoderation.m_approve?product_ids[]={$product_data.product_id}&redirect_url={$current_url}"
                            icon="icon-thumbs-up"
                            icon_first=true
                            class="btn"
                            method="POST"
                        }
                        {btn type="text"
                            id="premoderation_disapprove"
                            text=__("vendor_data_premoderation.disapprove_product_btn")
                            icon="icon-thumbs-down"
                            icon_first=true
                            class="btn"
                            data=["data-ca-premoderation-disapprove"=>"true"]
                        }
                    </div>
                </div>
            </div>

            <div
                class="control-group {if !$show_premoderation_reason || !$product_data.premoderation_reason}hidden{/if}"
                data-ca-product-disapproval-reason-section="false">
                <label for="elm_disapproval_reason"
                    class="control-label"
                >
                    {__("vendor_data_premoderation.disapproval_reason")}:
                </label>
                <div class="controls">
                    {if !$runtime.company_id && $show_premoderation_reason &&
                        fn_check_permissions("premoderation", "m_approve", "admin")
                    }
                        <textarea class="input-large 
                            {if $show_premoderation_reason}hidden{/if}"
                            rows="5"
                            name="product_data[premoderation_reason]"
                            placeholder="{__("vendor_data_premoderation.enter_disapproval_reason")}"
                            disabled="disabled"
                            data-ca-product-disapproval-reason="true"
                        ></textarea>
                    {/if}
                    {if $show_premoderation_reason}
                        <p data-ca-product-disapproval-reason-text="true">{$product_data.premoderation_reason}</p>
                    {/if}
                </div>
            </div>
        </div>

    {/hook}
{/if}
