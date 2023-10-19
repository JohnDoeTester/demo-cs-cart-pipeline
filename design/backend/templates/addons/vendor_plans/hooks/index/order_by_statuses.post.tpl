{if $plan_usage}
    <div class="dashboard-table dashboard-table-plan-usage">
        <h4>{__("vendor_plans.current_plan_usage")}</h4>
        <div class="table-responsive-wrapper">
            <table class="table table--relative table-responsive table-responsive-w-titles" width="100%">
                <tr>
                    <td data-th="&nbsp;">
                        {__("vendor_plans.plan_name")}:
                    </td>
                    <td data-th="&nbsp;">
                        <a href="{"companies.update?company_id={$runtime.company_id}&selected_section=plan"|fn_url}">
                            <strong>{$plan_data.plan}</strong>
                        </a>
                    </td>
                </tr>
                {foreach from=$plan_usage item=item}
                <tr>
                    <td width="30%" data-th="&nbsp;">
                        <strong>{$item.title}</strong><br />
                        {strip}
                            {if $item.is_price}
                                {include file="common/price.tpl" value=$item.current}/
                            {else}
                                {$item.current}&nbsp;/&nbsp;
                            {/if}
                            
                            {if !$item.limit} 
                                {__("vendor_plans.unlimited")}
                            {elseif $item.is_price}
                                {include file="common/price.tpl" value=$item.limit}
                            {else}
                                {$item.limit}
                            {/if}
                        {/strip}
                    </td>
                    <td width="70%" valign="middle" data-th="&nbsp;">
                        <div class="progress {if $item.current == $item.limit}progress-info{elseif $item.current > $item.limit}progress-danger{/if}">
                            <div class="bar" style="width: {$item.percentage}%;"></div>
                        </div>
                    </td>
                </tr>
                {/foreach}
            </table>
        </div>
    </div>
{/if}
