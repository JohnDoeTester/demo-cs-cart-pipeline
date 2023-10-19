{if $field.field_type == $smarty.const.PROFILE_FIELD_TYPE_VENDOR_PLAN}
<div class="ty-control-group ty-profile-field__item ty-{$field.class}{if !$vendor_plans} hidden{/if}">
    {if $pref_field_name != $field.description || $field.required == "Y"}
        <label for="{$id_prefix}elm_{$field.field_id}" class="ty-control-group__title cm-profile-field">{$field.description}</label>
    {/if}

    {$default_plan = $company_data.plan_id}
    {if !$default_plan}
        {$default_plan = $smarty.request.plan_id}
    {/if}

    <select {if $field.autocomplete_type}x-autocompletetype="{$field.autocomplete_type}"{/if} id="{$id_prefix}elm_{$field.field_id}" class="ty-profile-field__select {if !$skip_field}{$_class}{else}cm-skip-avail-switch{/if}" name="{$data_name}[{$data_id}]" {if !$skip_field}{$disabled_param nofilter}{/if}>
        {foreach $field.plans as $plan}
            <option value="{$plan.plan_id}"{if (!$default_plan && $plan.is_default) || $plan.plan_id == $default_plan} selected="selected"{/if}>{$plan.plan} ({include file="common/price.tpl" value=$plan.price})</option>
        {/foreach}
    </select>
</div>
{/if}