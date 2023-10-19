<div class="sidebar-field">
    <label for="elm_plan">{__("vendor_plans.plan")}</label>
    <div class="select2-wrapper">
        <select name="plan_id" id="elm_plan" class="cm-object-selector">
            <option value=""> -- </option>
            {foreach from=$vendor_plans item="plan"}
                <option value="{$plan.plan_id}"{if $plan.plan_id == $search.plan_id} selected="selected"{/if}>{$plan->plan} ({include file="common/price.tpl" value=$plan->price})</option>
            {/foreach}
        </select>
    </div>
</div>
