{if $can_view_orders && $user_data.user_id && $user_type == "UserTypes::CUSTOMER"|enum}
    <div class="sidebar-row">
        <h6>{__("orders")}</h6>
        <ul class="unstyled">
            <li>{__("registration_date")} <span class="pull-right">{$user_data.timestamp|date_format:"`$settings.Appearance.date_format`"}</span></li>
            {if $orders_stats[$user_data.user_id].first_order_id}
                <li>{__("first_order")} <span><a class="pull-right" href="{"orders.details&order_id=`$orders_stats[$user_data.user_id].first_order_id`"|fn_url}">{$orders_stats[$user_data.user_id].first_order_timestamp|date_format:"`$settings.Appearance.date_format`"}</a></span></li>
            {/if}
            <li>{__("total_orders")} <span><a class="pull-right" href="{"orders.manage?is_search=Y&user_id=`$user_data.user_id`"|fn_url}">{$orders_stats[$user_data.user_id].total_orders|default: 0}</a></span></li>
            <li>{__("total_paid_orders")} <span><a class="pull-right" href="{"orders.manage?is_search=Y&user_id=`$user_data.user_id`&{http_build_query(["status" => $settled_statuses|array_values])}"|fn_url}">{$orders_stats[$user_data.user_id].total_settled_orders|default: 0}</a></span></li>
            <li>{__("total_spent_money")} <a class="pull-right" href="{"orders.manage?is_search=Y&user_id=`$user_data.user_id`&{http_build_query(["status" => $settled_statuses|array_values])}"|fn_url}">{$orders_stats[$user_data.user_id].total_spend|format_price:$currencies.$secondary_currency|default: 0 nofilter}</a></li>
            {if $orders_stats[$user_data.user_id].last_order_id}
                <li>{__("last_order")} <span><a class="pull-right" href="{"orders.details&order_id=`$orders_stats[$user_data.user_id].last_order_id`"|fn_url}">{$orders_stats[$user_data.user_id].last_order_timestamp|date_format:"`$settings.Appearance.date_format`"}</a></span></li>
            {/if}
            {if $user_data.last_login}
                <li>{__("last_login")} <span class="pull-right">{$user_data.last_login|date_format:"`$settings.Appearance.date_format`"}</span></li>
            {/if}
        </ul>
    </div>
{/if}