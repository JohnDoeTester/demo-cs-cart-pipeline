{strip}
{*
    Column 2. Item 1. Orders
*}
{if !empty($orders_stat.orders)}
    {$user_can_view_orders = "orders.manage"|fn_check_view_permissions:'GET'}
    {capture name="date_range_selected_date"}
        {if $settings.Appearance.calendar_date_format === "month_first"}
            {$time_from|date_format:"%b %d, %Y"} — {$time_to|date_format:"%b %d, %Y"}
        {else}
            {$time_from|date_format:"%d %b %Y"} — {$time_to|date_format:"%d %b %Y"}
        {/if}
    {/capture}
    
    {if $orders_stat_prev_orders_count > 0}
        {$orders_stat_diff_orders_percent = number_format((($orders_stat.prev_orders|count * 100) /  $orders_stat.orders|count), 0)}
    {else}
        {$orders_stat_diff_orders_percent = "&infin;"}
    {/if}

    {$data_2_1_orders = [
        id => "analytics_card_orders",
        preheader => $smarty.capture.date_range_selected_date,
        is_selected_date => true,
        title => __("dashboard.analytics_card.orders"),
        title_button => [
            name => __("dashboard.analytics_card.view_orders"),
            href => ($user_can_view_orders)
                ? "orders.manage?is_search=Y&storefront_id=`$storefront_id`&period=C&time_from=`$time_from`&time_to=`$time_to`"
                : "orders.manage"
        ],
        number => $orders_stat.orders|count,
        number_dynamics => $orders_stat_diff_orders_percent
    ]}
    {$data_2_1_orders = $data_2_1_orders scope=parent}
{/if}
{/strip}