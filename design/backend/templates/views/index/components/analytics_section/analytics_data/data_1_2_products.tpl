{strip}
{*
    Column 1. Item 2. Products
*}
{if !empty($general_stats.products)}
    {$data_1_2_products = [
        id => "analytics_card_products",
        title => __("dashboard.analytics_card.active_products"),
        title_button => [
            name => __("dashboard.analytics_card.view_products"),
            href => "products.manage&status={"ObjectStatuses::ACTIVE"|enum}"
        ],
        number => $general_stats.products.total_products|number_format,
        resource_list => [
            id => "analytics_card_products_resource_list",
            title => __("dashboard.analytics_card.products_details"),
            content => [
                [
                    id => "analytics_card_products_out_of_stock",
                    name => __("dashboard.analytics_card.out_of_stock"),
                    href => "products.manage?amount_from=&amount_to=0&tracking[0]={"ProductTracking::TRACK"|enum}",
                    value => $general_stats.products.out_of_stock_products|number_format,
                    value_href => "products.manage?amount_from=&amount_to=0&tracking[0]={"ProductTracking::TRACK"|enum}"
                ]
            ]
        ]
    ]}

    {*
        Post hooks:
        - design/backend/templates/addons/vendor_data_premoderation/hooks/index/analytics_data.post.tpl
    *}
    {$data_1_2_products = $data_1_2_products scope=parent}
{/if}
{/strip}