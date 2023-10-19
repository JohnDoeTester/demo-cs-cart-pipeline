{strip}
{*
    Column 3. Item 4. Vendors with sales (For administration only)
*}
{*
    $time_from
    $time_to
*}
{if isset($dashboard_vendors_activity)}
    {capture name="date_range_selected_date"}
        {if $settings.Appearance.calendar_date_format === "month_first"}
            {$time_from|date_format:"%b %d, %Y"} — {$time_to|date_format:"%b %d, %Y"}
        {else}
            {$time_from|date_format:"%d %b %Y"} — {$time_to|date_format:"%d %b %Y"}
        {/if}
    {/capture}
    {$data_3_3_vendors_with_sales = [
        id => "analytics_card_vendors_with_sales",
        preheader => $smarty.capture.date_range_selected_date,
        is_selected_date => true,
        title => __("dashboard.analytics_card.vendors_with_sales"),
        title_button => [
            name => __("dashboard.analytics_card.view_vendors"),
            href => "companies.manage?sales_from={$time_from}&sales_to={$time_to}&status={"VendorStatuses::ACTIVE"|enum}"
        ],
        number => $dashboard_vendors_activity.vendors_with_sales,
        resource_list => [
            id => "analytics_card_vendors_with_sales_list",
            title => __("dashboard.analytics_card.vendor_activity"),
            content => [
                [
                    id => "analytics_card_vendors_with_sales_new_vendors",
                    name => __("dashboard.analytics_card.new_vendors"),
                    href => "companies.manage?created_from={$time_from}&created_to={$time_to}&status={"VendorStatuses::ACTIVE"|enum}",
                    value => $dashboard_vendors_activity.new_vendors,
                    value_href => "companies.manage?created_from={$time_from}&created_to={$time_to}&status={"VendorStatuses::ACTIVE"|enum}"
                ],
                [
                    id => "analytics_card_vendors_with_sales_vendors_with_new_products",
                    name => __("dashboard.analytics_card.vendors_with_new_products"),
                    href => "companies.manage?extend[]=products&new_products_from={$time_from}&new_products_to={$time_to}&status={"VendorStatuses::ACTIVE"|enum}",
                    value => $dashboard_vendors_activity.vendors_with_new_products,
                    value_href => "companies.manage?extend[]=products&new_products_from={$time_from}&new_products_to={$time_to}&status={"VendorStatuses::ACTIVE"|enum}"
                ],
                [
                    id => "analytics_card_vendors_with_sales_vendors_not_logged",
                    name => __("dashboard.analytics_card.vendors_not_logged"),
                    href => "companies.manage?not_login_from={$time_from}&not_login_to={$time_to}&status={"VendorStatuses::ACTIVE"|enum}",
                    value => $dashboard_vendors_activity.vendors_not_logged,
                    value_href => "companies.manage?not_login_from={$time_from}&not_login_to={$time_to}&status={"VendorStatuses::ACTIVE"|enum}"
                ],
                [
                    id => "analytics_card_vendors_with_sales_new_products",
                    name => __("dashboard.analytics_card.new_products"),
                    href => "products.manage?time_from={$time_from}&time_to={$time_to}&period=C&status[]=A&company_status[]={"VendorStatuses::ACTIVE"|enum}",
                    value => $dashboard_vendors_activity.new_products,
                    value_href => "products.manage?time_from={$time_from}&time_to={$time_to}&period=C&status[]=A&company_status[]={"VendorStatuses::ACTIVE"|enum}"
                ]
            ]
        ]
    ]}
    {*
        Post hooks:
        - design/backend/templates/addons/vendor_debt_payout/hooks/index/analytics_data.post.tpl
    *}
    {$data_3_3_vendors_with_sales = $data_3_3_vendors_with_sales scope=parent}
{/if}
{/strip}