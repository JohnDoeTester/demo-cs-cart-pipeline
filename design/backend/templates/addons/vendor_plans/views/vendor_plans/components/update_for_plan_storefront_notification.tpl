{*
    int $plan_id                Vendor plan identifier
    int $affected_vendors_count Amount of vendors that are affected by the vendor plan change
*}
<div class="hidden alert alert-block" data-ca-vendor-plans="updatePlanStorefrontVendorsNotification">

    <h4>
        {__("vendor_plans.update_for_plan.title")}
    </h4>

    <div>
        <div>
            {__("vendor_plans.update_for_plan.general_message",
                [$affected_vendors_count, "[search_url]" => "companies.manage?plan_id={$plan_id}"|fn_url])
                nofilter
            }
        </div>

        <div>
            <div data-ca-vendor-plans="updatePlanStorefrontVendorsAddNotification">
                <label class="checkbox">
                    <input type="checkbox" name="plan_data[add_vendors_to_new_storefronts]">
                    {__("vendor_plans.storefronts_update_for_plan.add_storefronts_message", [$affected_vendors_count])}
                </label>
            </div>

            <div data-ca-vendor-plans="updatePlanStorefrontVendorsRemoveNotification">
                <label class="checkbox">
                    <input type="checkbox" name="plan_data[remove_vendors_from_old_storefronts]">
                    {__("vendor_plans.storefronts_update_for_plan.remove_storefronts_message", [$affected_vendors_count])}
                </label>
            </div>
        </div>
    </div>
</div>
