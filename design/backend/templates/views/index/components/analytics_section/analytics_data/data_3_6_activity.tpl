{strip}
{if $logs && "logs.manage"|fn_check_view_permissions:"GET"}
{*
    Column 3. Item 3. Activity
*}
    {$data_3_6_activity = [
        id => "analytics_card_activity",
        title => __("dashboard.analytics_card.activity"),
        title_button => [
            name => __("dashboard.view_all"),
            href => "logs.manage"
        ],
        resource_list => [
            id => "analytics_card_products_resource_list",
            content => []
        ]
    ]}

    {foreach $logs as $log_key => $item}
        {$_type = "log_type_`$item.type`"}
        {$_action = "log_action_`$item.action`"}

        {$item_name = __($_type)}
        {if $item.action}
            {$item_name = "`$item_name`&nbsp;({__($_action)})"}
        {/if}

        {if $item.type === "users" && "profiles.update?user_id=`$item.content.id`"|fn_url|fn_check_view_permissions:"GET"}
            {$item_description = $item.content.user}
            {if $item.content.id}
                {$item_description_href = "profiles.update?user_id=`$item.content.id`"}
            {/if}
        {elseif $item.type === "orders" && "orders.details?order_id=`$item.content.id`"|fn_url|fn_check_view_permissions:"GET"}
            {$item_name = "`$item_name` `$item.content.status`"}
            {$item_description = "{__("order")}&nbsp;`$item.content.order`"}
            {$item_description_href = "orders.details?order_id=`$item.content.id`"}
        {elseif $item.type === "products" && "products.update?product_id=`$item.content.id`"|fn_url|fn_check_view_permissions:"GET"}
            {$item_description = $item.content.product}
            {$item_description_href = "products.update?product_id=`$item.content.id`"}
        {elseif $item.type === "categories" && "categories.update?category_id=`$item.content.id`"|fn_url|fn_check_view_permissions:"GET"}
            {$item_description = $item.content.category}
            {$item_description_href = "categories.update?category_id=`$item.content.id`"}
        {/if}

        {$data_3_6_activity.resource_list.content[] = [
            id => "analytics_card_activity_`$log_key`",
            name => $item_name,
            href => $item_href,
            value => $item.timestamp|date_format:"`$settings.Appearance.date_format`, `$settings.Appearance.time_format`",
            description => $item_description,
            description_href => $item_description_href
        ]}
    {/foreach}

    {$data_3_6_activity = $data_3_6_activity scope=parent}
{/if}
{/strip}