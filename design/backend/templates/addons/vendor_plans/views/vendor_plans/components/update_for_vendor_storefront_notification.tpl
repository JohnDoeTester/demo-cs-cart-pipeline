<div class="hidden alert alert-block" data-ca-vendor-plans="updateVendorStorefrontVendorsNotification">

    <h4>
        {__("vendor_plans.storefronts_update_for_vendor.title")}
    </h4>

    <div>
        <div>
            {__("vendor_plans.storefronts_update_for_vendor.general_message")}
        </div>

        <div>
            <div data-ca-vendor-plans="updateVendorStorefrontVendorsAddNotification">
                <label class="checkbox">
                    <input type="checkbox" name="company_data[add_vendor_to_new_storefronts]">
                    {__("vendor_plans.storefronts_update_for_vendor.add_storefronts_message")}
                </label>
            </div>

            <div data-ca-vendor-plans="updateVendorStorefrontVendorsRemoveNotification">
                <label class="checkbox">
                    <input type="checkbox" name="company_data[remove_vendor_from_old_storefronts]">
                    {__("vendor_plans.storefronts_update_for_vendor.remove_storefronts_message")}
                </label>
            </div>
        </div>
    </div>
</div>
