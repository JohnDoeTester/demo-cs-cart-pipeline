{include file="common/letter_header.tpl"}

{capture name="price"}
    {include file="common/price.tpl" value=$plan->price}
{/capture}
{__("vendor_plans.plan_payment_text", ['[plan]' => $plan->plan, '[price]' => $smarty.capture.price, '[href]' => 'companies.balance'|fn_url:'V':'http']) nofilter}

<br /><br />
{include file="addons/vendor_plans/companies/common/plan_details.tpl"}

{include file="common/letter_footer.tpl"}
