{assign var="show_latest_orders" value="orders"|fn_check_permissions:'manage':'admin'}
{assign var="show_orders" value="sales_reports"|fn_check_permissions:'reports':'admin'}
{assign var="show_inventory" value="products"|fn_check_permissions:'manage':'admin'}
{assign var="show_users" value="profiles"|fn_check_permissions:'manage':'admin'}

{assign var="user_can_view_orders" value="orders.manage"|fn_check_view_permissions:'GET'}

<script>
    (function(_, $) {

        _.drawChart = function(is_day) {
            if (typeof google == "undefined") {
                return false;
            }

            function get_data(div) {
                var id = $(div).attr('id');
                var dataTable = new google.visualization.DataTable();
                if (is_day) {
                    dataTable.addColumn('timeofday', 'Date');
                } else {
                    dataTable.addColumn('date', 'Date');
                }
                dataTable.addColumn('number', '{__("previous_period")}');
                dataTable.addColumn('number', '{__("current_period")}');
                dataTable.addRows(_.chart_data[id]);

                var dataView = new google.visualization.DataView(dataTable);
                dataView.setColumns([0, 1, 2]);

                var date_formatter = new google.visualization.DateFormat({
                    pattern: "{__("current_previous_period", ["[date]" => "MMM d, yyyy"])|escape:"javascript"}"
                });
                date_formatter.format(dataTable, 0);

                return dataView;
            }

            var chartwidth = $('.dashboard-statistics-chart').width();

            var options = {
                chartArea: {
                    left: 7,
                    top: 10,
                    width: chartwidth,
                    height: 208
                },
                colors: ['#ff9494','#33c49b'],
                tooltip: {
                    showColorCode: true
                },
                lineWidth: 1,
                hAxis: {
                    baselineColor: '#eaeef0',
                    textStyle: {
                        color: '#a3b2bf',
                        fontSize: 11
                    },
                    gridlines: {
                        count: 6,
                        color: '#f0f5f7',
                    }
                },
                legend: {
                    position: 'none'
                },
                pointSize: 6,
                vAxis: {
                    minValue: 0,
                    baselineColor: '#eaeef0',
                    textPosition: 'in',
                    textStyle: {
                        color: '#a3b2bf',
                        fontSize: 11
                    },
                    gridlines: {
                        count: 10,
                        color: '#eaeef0',
                    }
                }
            };
            if (!is_day) {
                options.hAxis.format = 'MMM d';
            }

            $('.dashboard-statistics-chart:visible').each(function(i, div) {
                var dataView = get_data(div);
                var chart = new google.visualization.AreaChart(div);
                chart.draw(dataView, options);
            });

            $('#statistics_tabs .tabs li').on('click', function() {
                $('.dashboard-statistics-chart:visible').each(function(i, div) {
                    var dataView = get_data(div);
                    var chart = new google.visualization.AreaChart(div);
                    chart.draw(dataView, options);
                });
            });
        };

        $(document).ready(function() {
            $.getScript('//www.google.com/jsapi', function() {
                setTimeout(function() { // do not remove it - otherwise it will be slow in ff
                    google.load('visualization', '1.0', {
                        packages: ['corechart'],
                        callback: function() {
                            _.drawChart({$is_day});
                        }
                    });
                }, 0);
            });

        });

        $(window).resize(function() {
            if(this.resizeTO) clearTimeout(this.resizeTO);
            this.resizeTO = setTimeout(function() {
                $(this).trigger('resizeEnd');
            }, 1);
        });

        //redraw graph when window resize is completed
        $(window).on('resizeEnd', function() {
            chartwidth = $('.dashboard-statistics-chart').width();
            _.drawChart({$is_day});
        });
    }(Tygh, Tygh.$));
</script>

{hook name="index:index"}

    {hook name="index:alert_block"}
        {if "MULTIVENDOR"|fn_allowed_for && $dashboard_alert}
            <div class="alert alert-block">
                <div class="debt-notification__text">
                    {$dashboard_alert nofilter}
                </div>
            </div>
        {/if}
    {/hook}

    {if
        $auth.user_type === "UserTypes::ADMIN"|enum
        && !$auth.helpdesk_user_id
        && fn_check_permissions("addons", "update", "admin")
    }
        {if "ULTIMATE:FREE"|fn_allowed_for && $is_activated_free !== "YesNo::YES"|enum}
            {if
                $auth.is_root === "YesNo::YES"|enum
                && !$auth.company_id
                && $settings.Upgrade_center.license_number
            }
                <div class="well well-small help-block">
                    {include file="buttons/helpdesk.tpl"
                        btn_class="pull-right cm-ajax"
                        btn_text=__("activate")
                        btn_href="helpdesk_connector.activate_license_mail_request"
                    }
                    <p>{__("helpdesk_account.activate_free_license_message")}</p>
                </div>
            {/if}
        {else}
            <div class="well well-small help-block">
                {include file="buttons/helpdesk.tpl" btn_class="pull-right"}
                <p>{__("helpdesk_account.signed_out_message")}</p>
            </div>
        {/if}
    {/if}

    <div class="dashboard row-fluid" id="dashboard">

        <div class="dashboard-cards span3">
            {hook name="index:finance_statistic"}
            {if "MULTIVENDOR"|fn_allowed_for}
                {if $runtime.company_id}
                    <div class="dashboard-card dashboard-card--balance">
                        <div class="dashboard-card-title">{__("vendor_payouts.current_balance_text")}</div>
                        <div class="dashboard-card-content">
                            <h3>
                                {hook name="index:finance_statistic_balance"}
                                    <a href="{"companies.balance"|fn_url}"
                                    >{include file="common/price.tpl" value=$current_balance}</a>
                                {/hook}
                            </h3>
                            &nbsp;
                        </div>
                    </div>
                {/if}
                {if isset($period_income)}
                    <div class="dashboard-card">
                        <div class="dashboard-card-title">{__("vendor_payouts.income")}</div>
                        <div class="dashboard-card-content">
                            <h3>
                                {include file="common/price.tpl" value=$period_income}
                            </h3>
                            &nbsp;
                        </div>
                    </div>
                {/if}
            {/if}
            {if !empty($orders_stat.orders)}
                <div class="dashboard-card">
                    <div class="dashboard-card-title">{__("orders")}</div>
                    <div class="dashboard-card-content">
                        <h3>
                            {if $user_can_view_orders}
                                <a href="{"orders.manage?is_search=Y&storefront_id=`$storefront_id`&period=C&time_from=`$time_from`&time_to=`$time_to`"|fn_url}">{$orders_stat.orders|count}</a>
                            {else}
                                {$orders_stat.orders|count}
                            {/if}
                        </h3>
                        {$orders_stat.prev_orders|count}, {if $orders_stat.diff.orders_count > 0}+{/if}{$orders_stat.diff.orders_count}
                    </div>
                </div>
            {/if}
            {if !empty($orders_stat.orders_total)}
                <div class="dashboard-card">
                    <div class="dashboard-card-title">{__("sales")}</div>
                    <div class="dashboard-card-content">
                        <h3>{include file="common/price.tpl" value=$orders_stat.orders_total.totally_paid}</h3>{include file="common/price.tpl" value=$orders_stat.prev_orders_total.totally_paid}, {if $orders_stat.orders_total.totally_paid > $orders_stat.prev_orders_total.totally_paid}+{/if}{$orders_stat.diff.sales nofilter}%
                    </div>
                </div>
            {/if}
            {if !empty($orders_stat.taxes)}
                <div class="dashboard-card">
                    <div class="dashboard-card-title">{__("taxes")}</div>
                    <div class="dashboard-card-content">
                        <h3>{include file="common/price.tpl" value=$orders_stat.taxes.subtotal}</h3>{include file="common/price.tpl" value=$orders_stat.taxes.prev_subtotal}, {if $orders_stat.taxes.subtotal > $orders_stat.taxes.prev_subtotal}+{/if}{$orders_stat.taxes.diff nofilter}%
                    </div>
                </div>
            {/if}
            {if !empty($orders_stat.abandoned_cart_total)}
                <div class="dashboard-card">
                    <div class="dashboard-card-title">{__("users_carts")}</div>
                    <div class="dashboard-card-content">
                        <h3>{$orders_stat.abandoned_cart_total|default:0}</h3>{$orders_stat.prev_abandoned_cart_total|default:0}, {if $orders_stat.abandoned_cart_total > $orders_stat.prev_abandoned_cart_total}+{/if}{$orders_stat.diff.abandoned_carts nofilter}%
                    </div>
                </div>
            {/if}
            {/hook}

            {hook name="index:products_statistic"}
            {if !empty($general_stats.products)}
                <div class="dashboard-card">
                    <div class="dashboard-card-title">{__("active_products")}</div>
                    <div class="dashboard-card-content">
                        <h3><a href="{"products.manage?status=A"|fn_url}">{$general_stats.products.total_products|number_format}</a></h3>
                    </div>
                </div>
                {if $settings.General.inventory_tracking !== "YesNo::NO"|enum}
                    <div class="dashboard-card">
                        <div class="dashboard-card-title">{__("out_of_stock_products")}</div>
                        <div class="dashboard-card-content">
                            <h3><a href="{"products.manage?amount_from=&amount_to=0&tracking[0]={"ProductTracking::TRACK"|enum}"|fn_url}">{$general_stats.products.out_of_stock_products|number_format}</a></h3>
                        </div>
                    </div>
                {/if}
            {/if}
            {/hook}

            {if !empty($general_stats.customers)}
                <div class="dashboard-card">
                    <div class="dashboard-card-title">{__("registered_customers")}</div>
                    <div class="dashboard-card-content">
                        <h3><a href="{"profiles.manage?user_type=C"|fn_url}">{$general_stats.customers.registered_customers|number_format}</a></h3>
                    </div>
                </div>
            {/if}
            {if !empty($general_stats.categories)}
                <div class="dashboard-card">
                    <div class="dashboard-card-title">{__("categories")}</div>
                    <div class="dashboard-card-content">
                        <h3><a href="{"categories.manage"|fn_url}">{$general_stats.categories.total_categories|number_format}</a></h3>
                    </div>
                </div>
            {/if}
            {if !empty($general_stats.companies) && $auth.user_type === "UserTypes::ADMIN"|enum && !$runtime.company_id}
                <div class="dashboard-card">
                    <div class="dashboard-card-title">{__("vendors")}</div>
                    <div class="dashboard-card-content">
                        <h3><a href="{"companies.manage"|fn_url}">{$general_stats.companies.total_companies|number_format}</a></h3>
                    </div>
                </div>
            {/if}
            {if !empty($general_stats.pages)}
                <div class="dashboard-card">
                    <div class="dashboard-card-title">{__("web_pages")}</div>
                    <div class="dashboard-card-content">
                        <h3><a href="{"pages.manage"|fn_url}">{$general_stats.pages.total_pages|number_format}</a></h3>
                    </div>
                </div>
            {/if}
        </div>

        <div class="dashboard-main-column span9">
            {if isset($dashboard_vendors_activity)}
                <div class="dashboard-row-top">
                    <div class="dashboard-table dashboard-vendors-activity">
                        <h4>{__("vendors_activity")}</h4>
                        <div id="dashboard_vendors_activity">
                            <div class="span6">
                                <table class="table">
                                    <tbody>
                                    <tr>
                                        <td class="dashboard-vendors-activity__label">
                                            {$url = "companies.manage?sales_from={$time_from}&sales_to={$time_to}&status=A"}
                                            <a href="{$url|fn_url}">
                                                {__("vendors_activity.vendors_with_sales")}
                                            </a>
                                        </td>
                                        <td class="dashboard-vendors-activity__value">
                                            {$dashboard_vendors_activity.vendors_with_sales}
                                        </td>
                                    </tr>
                                    <tr>
                                        <td class="dashboard-vendors-activity__label">
                                            <a href="{"companies.manage?created_from={$time_from}&created_to={$time_to}&status=A"|fn_url}">
                                                {__("vendors_activity.new_vendors")}
                                            </a>
                                        </td>
                                        <td class="dashboard-vendors-activity__value">
                                            {$dashboard_vendors_activity.new_vendors}
                                        </td>
                                    </tr>
                                    <tr>
                                        <td class="dashboard-vendors-activity__label">
                                            {$url = "companies.manage?extend[]=products&new_products_from={$time_from}&new_products_to={$time_to}&status=A"}
                                            <a href="{$url|fn_url}">
                                                {__("vendors_activity.vendors_with_new_products")}
                                            </a>
                                        </td>
                                        <td class="dashboard-vendors-activity__value">
                                            {$dashboard_vendors_activity.vendors_with_new_products}
                                        </td>
                                    </tr>
                                    </tbody>
                                </table>
                            </div>

                            <div class="span6">
                                <table class="table">
                                    <tbody>
                                        {hook name="index:vendors_activity"}
                                            <tr>
                                                <td class="dashboard-vendors-activity__label">
                                                    <a href="{"companies.manage?not_login_from={$time_from}&not_login_to={$time_to}&status=A"|fn_url}">
                                                        {__("vendors_activity.not_logged_in_vendors")}
                                                    </a>
                                                </td>
                                                <td class="dashboard-vendors-activity__value">
                                                    {$dashboard_vendors_activity.vendors_not_logged}
                                                </td>
                                            </tr>
                                            <tr>
                                                <td class="dashboard-vendors-activity__label">
                                                    {hook name="index:dashboard_new_products_link"}
                                                    {$url = "products.manage?time_from={$time_from}&time_to={$time_to}&period=C&status[]=A&company_status[]=A"}
                                                    {/hook}
                                                    <a href="{$url|fn_url}">
                                                        {__("vendors_activity.new_products")}
                                                    </a>
                                                </td>
                                                <td class="dashboard-vendors-activity__value">
                                                    {$dashboard_vendors_activity.new_products}
                                                </td>
                                            </tr>
                                        {/hook}
                                    </tbody>
                                </table>
                            </div>
                            <!--dashboard_vendors_activity--></div>
                    </div>
                </div>
            {/if}
            <div class="dashboard-row">
                {if !empty($graphs)}
                    <div class="dashboard-statistics">
                        <h4>
                            {__("statistics")}
                        </h4>
                        {capture name="chart_tabs"}
                            <div id="content_sales_chart">
                                <div id="dashboard_statistics_sales_chart" class="dashboard-statistics-chart spinner">
                                </div>
                            </div>
                            {hook name="index:chart_statistic"}
                            {/hook}
                        {/capture}

                        <div id="statistics_tabs">
                            {include file="common/tabsbox.tpl" content=$smarty.capture.chart_tabs}
                            <script>
                                Tygh.chart_data = {
                                    {foreach from=$graphs item="graph" key="chart" name="graphs"}
                                    '{$chart}': [
                                        {foreach from=$graph item="data" key="date" name="graph"}
                                        [{if $is_day}[{$date}, 0, 0, 0]{else}new Date({$date}){/if}, {$data.prev}, {$data.cur}]{if !$smarty.foreach.graph.last},{/if}
                                        {/foreach}
                                    ]{if !$smarty.foreach.graphs.last},{/if}
                                    {/foreach}
                                };
                                Tygh.drawChart({$is_day});
                            </script>
                            <!--statistics_tabs--></div>
                    </div>
                {/if}
                {if !empty($order_statuses)}
                    <div class="dashboard-recent-orders cm-j-tabs tabs">
                        <h4>{__("recent_orders")}</h4>
                        <ul class="nav nav-pills">
                            <li id="tab_recent_all" class="active cm-js"><a href="#status_all" data-toggle="tab">{__("all")}</a></li>
                            {foreach from=$order_statuses item="status"}
                                <li id="tab_recent_{$status.status}" class="cm-js"><a href="#status_{$status.status}" data-toggle="tab">{$status.description}</a></li>
                            {/foreach}
                        </ul>

                        <div class="cm-tabs-content">
                            <div class="tab-pane" id="content_tab_recent_all">
                                <div class="table-responsive-wrapper">
                                    <table class="table table-middle table--relative table-last-td-align-right table-responsive table-responsive-w-titles">
                                        <tbody>
                                        {foreach $orders.all as $order}
                                            <tr>
                                                <td data-th="&nbsp;">
                                                    <span class="label btn-info o-status-{$order.status|lower} label--text-wrap">{$order_statuses[$order.status].description}</span>
                                                </td>
                                                <td data-th="&nbsp;"><a href="{"orders.details?order_id=`$order.order_id`"|fn_url}">{__("order")} <bdi>#{$order.order_id}</bdi></a> {__("by")} {if $order.user_id}<a href="{"profiles.update?user_id=`$order.user_id`"|fn_url}">{/if}{$order.lastname} {$order.firstname}{if $order.user_id}</a>{/if}</td>
                                                <td data-th="&nbsp;"><span class="date">{$order.timestamp|date_format:"`$settings.Appearance.date_format`, `$settings.Appearance.time_format`"}</span></td>
                                                <td data-th="&nbsp;"><h4>{include file="common/price.tpl" value=$order.total}</h4></td>
                                            </tr>
                                            {foreachelse}
                                            <tr><td data-th="&nbsp;"><p class="no-items">{__("no_data")}</p></td></tr>
                                        {/foreach}
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                            {foreach $order_statuses as $status}
                                <div class="tab-pane" id="content_tab_recent_{$status.status}">
                                    <div class="table-responsive-wrapper">
                                        <table class="table table-middle table--relative table-last-td-align-right table-responsive table-responsive-w-titles">
                                            <tbody>
                                            {foreach $orders[$status.status] as $order}
                                                <tr>
                                                    <td data-th="&nbsp;">
                                                        <span class="label btn-info o-status-{$order.status|lower} label--text-wrap">{$order_statuses[$order.status].description}</span>
                                                    </td>
                                                    <td data-th="&nbsp;"><a href="{"orders.details?order_id=`$order.order_id`"|fn_url}">{__("order")} <bdi>#{$order.order_id}</bdi></a> {__("by")} {if $order.user_id}<a href="{"profiles.update?user_id=`$order.user_id`"|fn_url}">{/if}{$order.lastname} {$order.firstname}{if $order.user_id}</a>{/if}</td>
                                                    <td data-th="&nbsp;"><span class="date">{$order.timestamp|date_format:"`$settings.Appearance.date_format`, `$settings.Appearance.time_format`"}</span></td>
                                                    <td data-th="&nbsp;"><h4>{include file="common/price.tpl" value=$order.total}</h4></td>
                                                </tr>
                                                {foreachelse}
                                                <tr><td data-th="&nbsp;"><p class="no-items">{__("no_data")}</p></td></tr>
                                            {/foreach}
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            {/foreach}
                        </div>
                    </div>
                {/if}
            </div>

            <div class="dashboard-row-bottom">
                <div class="dashboard-tables">

                    {hook name="index:order_statistic"}
                    {/hook}

                    {hook name="index:order_by_statuses"}
                    {if $user_can_view_orders && $order_by_statuses}
                        <div class="dashboard-table dashboard-table-order-by-statuses">
                            <h4>{__("order_by_status")}</h4>
                            <div class="table-wrap" id="dashboard_order_by_status">
                                <table class="table">
                                    <thead>
                                    <tr>
                                        <th width="25%">{__("status")}</th>
                                        <th width="25%">{__("qty")}</th>
                                        <th width="25%">{__(total)}</th>
                                        <th width="25%">{__("shipping")}</th>
                                    </tr>
                                    </thead>
                                </table>
                                <div class="scrollable-table">
                                    <table class="table table-striped table--relative">
                                        <tbody>
                                        {foreach from=$order_by_statuses item="order_status"}
                                            {$url = "orders.manage?is_search=Y&storefront_id=`$storefront_id`&period=C&time_from=`$time_from`&time_to=`$time_to`&status[]=`$order_status.status`"|fn_url}
                                            <tr>
                                                <td width="25%"><a class="a--text-wrap" href="{$url}" title="{$order_status.status_name}">{$order_status.status_name}</a></td>
                                                <td width="25%">{$order_status.count}</td>
                                                <td width="25%">{include file="common/price.tpl" value=$order_status.total}</td>
                                                <td width="25%">{include file="common/price.tpl" value=$order_status.shipping}</td>
                                            </tr>
                                        {/foreach}
                                        </tbody>
                                    </table>
                                </div>
                                <!--dashboard_order_by_status--></div>
                        </div>
                    {/if}
                    {/hook}
                </div>

                {if $logs && "logs.manage"|fn_check_view_permissions:"GET"}
                    <div class="dashboard-activity">
                        <div class="pull-right"><a href="{"logs.manage"|fn_url}">{__('show_all')}</a></div>
                        <h4>{__("recent_activity")}</h4>
                        {function name="show_log_row" item=[]}
                            {if $item}
                                <div class="item">
                                    {hook name="index:recent_activity"}
                                    {$_type = "log_type_`$item.type`"}
                                    {$_action = "log_action_`$item.action`"}

                                    {__($_type)}{if $item.action}&nbsp;({__($_action)}){/if}:

                                    {if $item.type == "users" && "profiles.update?user_id=`$item.content.id`"|fn_url|fn_check_view_permissions:"GET"}
                                        {if $item.content.id}<a href="{"profiles.update?user_id=`$item.content.id`"|fn_url}">{/if}{$item.content.user}{if $item.content.id}</a>{/if}<br>

                                    {elseif $item.type == "orders" && "orders.details?order_id=`$item.content.id`"|fn_url|fn_check_view_permissions:"GET"}
                                        {$item.content.status}<br>
                                        <a href="{"orders.details?order_id=`$item.content.id`"|fn_url}">{__("order")}&nbsp;{$item.content.order}</a><br>
                                    {elseif $item.type == "products" && "products.update?product_id=`$item.content.id`"|fn_url|fn_check_view_permissions:"GET"}
                                        <a href="{"products.update?product_id=`$item.content.id`"|fn_url}">{$item.content.product}</a><br>

                                    {elseif $item.type == "categories" && "categories.update?category_id=`$item.content.id`"|fn_url|fn_check_view_permissions:"GET"}
                                        <a href="{"categories.update?category_id=`$item.content.id`"|fn_url}">{$item.content.category}</a><br>
                                    {/if}

                                    {hook name="index:recent_activity_item"}{/hook}

                                        <span class="date">{$item.timestamp|date_format:"`$settings.Appearance.date_format`, `$settings.Appearance.time_format`"}</span>
                                    {/hook}
                                </div>
                            {/if}
                        {/function}

                        <div class="dashboard-activity-list">
                            {foreach from=$logs item="item"}
                                {show_log_row item=$item}
                            {/foreach}
                        </div>
                    </div>
                {/if}
            </div>
        </div>
        <!--dashboard--></div>
{/hook}

{capture name="buttons"}
    {include file="common/daterange_picker.tpl"
        id="dashboard_date_picker"
        extra_class="nav__actions-dashboard-date-picker pull-right"
        data_url="index.index?storefront_id={$storefront_id}"|fn_url
        result_ids="dashboard"
        start_date=$time_from
        end_date=$time_to
    }
{/capture}
