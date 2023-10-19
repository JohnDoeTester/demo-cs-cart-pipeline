{strip}
{*
    Column 3. Item 2. Products used on the plan (For vendor only)
*}
{*
    $plan_data
    $runtime.company_id
*}
{if $plan_usage}
    {$data_3_2_vendor_plan = [
        id => "vendor_plans_analytics_card_vendor_plan",
        title => __("vendor_plans.dashboard.analytics_card.vendor_plan"),
        title_button => [
            name => __("vendor_plans.dashboard.analytics_card.view_plan"),
            href => "companies.update?company_id={$runtime.company_id}&selected_section=plan"
        ],
        number => "`$plan_usage.products.percentage`%",
        bar_chart => [
            id => "vendor_plans_analytics_card_vendor_plan_bar_chart",
            title => __("vendor_plans.dashboard.analytics_card.vendor_plan_limits", ["[name]" => $plan_data.plan]),
            content => [
                [
                    id => "vendor_plans_analytics_card_vendor_plan_bar_chart_products",
                    name => $plan_usage.products.title,
                    href => "products.manage",
                    type => "primary",
                    value => $plan_usage.products.current,
                    total => $plan_usage.products.limit,
                    ratio => $plan_usage.products.percentage
                ],
                [
                    id => "vendor_plans_analytics_card_vendor_plan_bar_chart_revenue",
                    name => $plan_usage.revenue.title,
                    href => "companies.balance",
                    value => $plan_usage.revenue.current,
                    total => $plan_usage.revenue.limit,
                    ratio => $plan_usage.products.percentage
                ]
            ]
        ]
    ]}
    {foreach $data_3_2_vendor_plan.bar_chart.content as $content_key => $content}
        {if $content.id === "vendor_plans_analytics_card_vendor_plan_bar_chart_revenue"}
            {$data_3_2_vendor_plan.bar_chart.content[$content_key].is_price = true}
        {/if}
    {/foreach}
    {* Fixme: [2] *}
    {$analytics_data.tertiary[2] = $data_3_2_vendor_plan}
    {$analytics_data = $analytics_data scope=parent}    
{/if}
{/strip}