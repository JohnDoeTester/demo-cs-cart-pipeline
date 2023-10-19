{if $vendor_usergroups}
    <div id="content_plan_privileges_{$id}" data-ca-vendor-privileges="vendorPlansPrivileges">
        {if $id}
            {include file="addons/vendor_plans/views/vendor_plans/components/update_for_plan_usergroup_notification.tpl"
                plan_id = $id
                affected_vendors_count = $plan.companies_count
            }
        {/if}
        <div class="control-group">
            <label class="control-label">{__("vendor_plans.usergroups_update_for_plan")}:</label>
            <div class="controls">
                {include file="addons/vendor_privileges/addons/vendor_plans/components/select_vendor_plans_usergroups.tpl"
                    id="vendor_privileges_vendor_plans_usergroup"
                    name="plan_data[usergroups]"
                    usergroups=$vendor_usergroups
                    usergroup_ids=$plan.usergroups
                    input_extra=""
                    list_mode=true
                }
            </div>
        </div>
    </div>
{/if}
