{if $plan}
    <br /><br />
    {__("vendor_plans.vendor_approved_text", ['[plan]' => $plan->plan])}

    <br /><br />
    {include file="addons/vendor_plans/companies/common/plan_details.tpl"}

{/if}
