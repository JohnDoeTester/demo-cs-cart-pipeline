{strip}
{*
    Column 2. Item 3. Recent orders
*}
{if !empty($order_statuses)}
    {$data_2_3_recent_orders = [
        id => "analytics_card_recent_orders",
        title => __("dashboard.analytics_card.recent_orders"),
        title_button => [
            name => __("dashboard.analytics_card.view_orders"),
            href => "orders.manage"
        ],
        resource_list_tabs => []
    ]}
    {* Add orders data *}
    {$data_2_3_recent_orders.resource_list_tabs.content = []}
    {$data_2_3_recent_orders.resource_list_tabs.id = "analytics_card_recent_orders_tabs"}
    {* All orders *}
    {$data_2_3_recent_orders.resource_list_tabs.content[0].id = "analytics_card_recent_orders_tab_all"}
    {$data_2_3_recent_orders.resource_list_tabs.content[0].title = __("all")}
    {foreach $orders.all as $order}
        {include file="common/price.tpl"
            value=$order.total
            assign=order_total_price
        }
        {$tab_content = [
            id => $order.order_id,
            name => "{__("order")} #`$order.order_id`",
            value => $order_total_price,
            href => "orders.details?order_id=`$order.order_id`",
            description => "`$order.lastname` `$order.firstname`",
            small_text => $order.timestamp|date_format:"`$settings.Appearance.date_format`, `$settings.Appearance.time_format`",
            label_id => $order.status|lower,
            label_text => $order_statuses[$order.status].description,
            label_class => "o-status-`$order.status|lower`"
        ]}
        {if $order.user_id}
            {$tab_content.description_href = "profiles.update?user_id=`$order.user_id`"}
        {/if}
        {$data_2_3_recent_orders.resource_list_tabs.content[0].content[] = $tab_content}
    {/foreach}
    {* Orders by status *}
    {foreach $order_statuses as $status name=order_statuses_array}
        {$status_index = $smarty.foreach.order_statuses_array.iteration}
        {$data_2_3_recent_orders.resource_list_tabs.content[$status_index].id = "analytics_card_recent_orders_tab_`$status.status|lower`"}
        {$data_2_3_recent_orders.resource_list_tabs.content[$status_index].title = $status.description}
        {foreach $orders[$status.status] as $order name=orders_array}
            {include file="common/price.tpl"
                value=$order.total
                assign=order_total_price
            }
            {$tab_content = [
                id => $order.order_id,
                name => "{__("order")} #`$order.order_id`",
                value => $order_total_price,
                href => "orders.details?order_id=`$order.order_id`",
                description => "`$order.lastname` `$order.firstname`",
                small_text => $order.timestamp|date_format:"`$settings.Appearance.date_format`, `$settings.Appearance.time_format`",
                label_id => $order.status|lower,
                label_text => $order_statuses[$order.status].description,
                label_class => "o-status-`$order.status|lower`"
            ]}
            {if $order.user_id}
                {$tab_content.description_href = "profiles.update?user_id=`$order.user_id`"}
            {/if}
            {$data_2_3_recent_orders.resource_list_tabs.content[$status_index].content[] = $tab_content}
        {/foreach}
    {/foreach}
    {$data_2_3_recent_orders = $data_2_3_recent_orders scope=parent}
{/if}
{/strip}