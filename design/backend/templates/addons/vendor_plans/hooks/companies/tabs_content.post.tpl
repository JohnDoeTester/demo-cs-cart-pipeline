<div id="content_plan"
    data-ca-vendor-plans="companiesPlan"
    data-ca-selected-storefronts="{$current_plan.storefront_ids|json_encode}"
    class="hidden"
>

    {if $id}
        {include file="addons/vendor_plans/views/vendor_plans/components/update_for_vendor_storefront_notification.tpl"}
    {/if}

    {if $runtime.company_id}
        <p>{__("vendor_plans.choose_your_plan")}</p>
        {include file="addons/vendor_plans/views/vendor_plans/components/plans_selector.tpl" plans=$vendor_plans current_plan_id=$company_data.plan_id name="company_data[plan_id]"}
    {else}
        {$allow_add_plan = fn_check_permissions("vendor_plans", "quick_add", "admin", "POST")}
        {$company_plan_id = $company_data.plan_id|default:$default_vendor_plan.plan_id}

        <div class="control-group">
            <label class="control-label" for="elm_company_plan">{__("vendor_plans.plan")}:</label>
            <div class="controls">
                {include file="addons/vendor_plans/views/vendor_plans/components/picker/picker.tpl"
                    item_ids=[$company_plan_id]
                    input_name="company_data[plan_id]"
                    picker_id="vendor_plans_picker"
                    allow_add=$allow_add_plan
                    current_plan_id=$company_plan_id
                }
            </div>
        </div>
        {if $allow_add_plan}
            {script src="js/addons/vendor_plans/backend/companies_update_vendor_plan.js"}

            <div class="control-toolbar__panel">
                <div id="companies_quick_add_vendor_plan"
                        data-ca-inline-dialog-action-context="vendor_update"
                        data-ca-inline-dialog-url="{"vendor_plans.quick_add"|fn_url}">
                </div>
            </div>
        {/if}
    {/if}

</div>
