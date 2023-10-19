{include file="common/letter_header.tpl"}

{__("vendor_plans.plan_has_been_changed_text", ['[plan]' => $plan->plan])}

<br /><br />
{include file="addons/vendor_plans/companies/common/plan_details.tpl"}

{include file="common/letter_footer.tpl"}
