{*
    int $plan_id                Vendor plan identifier
    int $affected_vendors_count Amount of vendors that are affected by the vendor plan change
*}
<div class="hidden alert alert-block" data-ca-vendor-plans="updatePlanUsergroupVendorsNotification">

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
            <div data-ca-vendor-plans="updatePlanUsergroupVendorsAddNotification">
                <label class="checkbox">
                    <input type="checkbox" name="plan_data[activate_added_usergroups]">
                    {__("vendor_plans.usergroups_update_for_plan.add_usergroups_message", [$affected_vendors_count])}
                </label>
            </div>

            <div data-ca-vendor-plans="updatePlanUsergroupVendorsRemoveNotification">
                <label class="checkbox">
                    <input type="checkbox" name="plan_data[disable_removed_usergroups]">
                    {__("vendor_plans.usergroups_update_for_plan.remove_usergroups_message", [$affected_vendors_count])}
                </label>
            </div>
        </div>
    </div>
</div>
