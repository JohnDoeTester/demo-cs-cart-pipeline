{strip}
{hook name="index:analytics_data"}
{include file="views/index/components/analytics_section/analytics_data/data_1_1_sales.tpl"
    graphs=$graphs
    time_from=$time_from
    time_to=$time_to
}
{include file="views/index/components/analytics_section/analytics_data/data_1_2_products.tpl"
    general_stats=$general_stats
}
{include file="views/index/components/analytics_section/analytics_data/data_2_1_orders.tpl"
    graphs=$graphs
    time_from=$time_from
    time_to=$time_to
}
{include file="views/index/components/analytics_section/analytics_data/data_2_3_recent_orders.tpl"
    orders=$orders
    order_statuses=$order_statuses
}
{*
    $data_2_4_recent_call_requests
    ---
    design/backend/templates/addons/call_requests/hooks/index/analytics_data.post.tpl
*}
{include file="views/index/components/analytics_section/analytics_data/data_3_1_vendor_current_balance.tpl"
    current_balance=$current_balance
}
{*
    $data_3_2_vendor_plan
    ---
    design/backend/templates/addons/vendor_plans/hooks/index/analytics_data.post.tpl
*}
{include file="views/index/components/analytics_section/analytics_data/data_3_3_vendors_with_sales.tpl"
    dashboard_vendors_activity=$dashboard_vendors_activity
    time_from=$time_from
    time_to=$time_to
}
{include file="views/index/components/analytics_section/analytics_data/data_3_4_stores.tpl"
    general_stats=$general_stats
}
{include file="views/index/components/analytics_section/analytics_data/data_3_5_registered_customers.tpl"
    general_stats=$general_stats
}
{include file="views/index/components/analytics_section/analytics_data/data_3_6_activity.tpl"
    logs=$logs
}

{* Primary column *}
{if $data_1_1_sales}
    {$analytics_data.primary[] = $data_1_1_sales}
{/if}
{if $data_1_2_products}
    {$analytics_data.primary[] = $data_1_2_products}
{/if}

{* Secondary column *}
{if $data_2_1_orders}
    {$analytics_data.secondary[] = $data_2_1_orders}
{/if}
{if $data_2_3_recent_orders}
    {$analytics_data.secondary[] = $data_2_3_recent_orders}
{/if}

{* Tertiary column *}
{if $data_3_1_vendor_current_balance}
    {$analytics_data.tertiary[] = $data_3_1_vendor_current_balance}
{/if}
{if $data_3_3_vendors_with_sales}
    {$analytics_data.tertiary[] = $data_3_3_vendors_with_sales}
{/if}
{* Fixme: Placeholder for vendor plan *}
{$analytics_data.tertiary[] = []}
{if $data_3_4_stores}
    {$analytics_data.tertiary[] = $data_3_4_stores}
{/if}
{if $data_3_5_registered_customers}
    {$analytics_data.tertiary[] = $data_3_5_registered_customers}
{/if}
{if $data_3_6_activity}
    {$analytics_data.tertiary[] = $data_3_6_activity}
{/if}

{*
    Post hooks:
    - design/backend/templates/addons/vendor_data_premoderation/hooks/index/analytics_data.post.tpl
    - design/backend/templates/addons/vendor_debt_payout/hooks/index/analytics_data.post.tpl
    - design/backend/templates/addons/vendor_plans/hooks/index/analytics_data.post.tpl
*}
{/hook}

{if $analytics_data.tertiary|@count === 1 && empty($analytics_data.tertiary[0])}
    {$analytics_data = $analytics_data|unset_key:"tertiary"}
{/if}

{$analytics_data = $analytics_data scope=parent}
{/strip}