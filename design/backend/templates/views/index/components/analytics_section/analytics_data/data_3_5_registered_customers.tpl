{strip}
{*
    Column 3. Item 6. Registered customers (For administration only)
*}
{if !empty($general_stats.customers)}
    {$data_3_5_registered_customers = [
        id => "analytics_card_registered_customers",
        title => __("dashboard.analytics_card.registered_customers"),
        title_button => [
            name => __("dashboard.analytics_card.view_customers"),
            href => "profiles.manage?user_type={"UserTypes::CUSTOMER"|enum}"
        ],
        number => $general_stats.customers.registered_customers|number_format
    ]}
    {$data_3_5_registered_customers = $data_3_5_registered_customers scope=parent}
{/if}
{/strip}