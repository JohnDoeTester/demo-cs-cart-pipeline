{include file="common/letter_header.tpl"}

{__("vendor_plans.revenue_exceeded_text", ['[vendor]' => $company.company, '[href]' => $company_url, '[plan]' => $plan.plan]) nofilter}

{include file="common/letter_footer.tpl"}
