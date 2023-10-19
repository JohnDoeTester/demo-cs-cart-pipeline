<div class="ty-vendor-plans__title">
    <h1 class="ty-vendor-plans__title-header">{__("vendor_plans.choose_your_plan")}</h1>
    <p class="ty-vendor-plans__title-desc">{__("vendor_plans.select_plan_text")}</p>
</div>

<input type="hidden" name="{$name}" class="cm-vendor-plans-selector-value" />
{include file="addons/vendor_plans/views/companies/components/plans.tpl" plans=$vendor_plans}

{capture name="mainbox_title"}<span>{__("vendor_plans.choose_your_plan")}</span>{/capture}
