{if $vendor_plans}
    {$show_plan_list = true}

    {foreach $profile_fields as $fields}
        {foreach $fields as $field}
            {if $field['field_name'] == "plan_id"}
                {$show_plan_list = false}
            {/if}
        {/foreach}
    {/foreach}

    {if $show_plan_list}
        <input type="hidden" name="company_data[plan_id]" id="company_plan" value="{$smarty.request.plan_id}">
    {/if}
{/if}
