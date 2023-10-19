{strip}
{*
    Column 1. Item 1. Sales
*}
{if !empty($orders_stat.orders_total) || !empty($graphs)}
    {include file="common/price.tpl"
        value=$orders_stat.orders_total.totally_paid
        assign=totally_paid
    }
    {capture name="date_range_selected_date"}
        {if $settings.Appearance.calendar_date_format === "month_first"}
            {$time_from|date_format:"%b %d, %Y"} — {$time_to|date_format:"%b %d, %Y"}
        {else}
            {$time_from|date_format:"%d %b %Y"} — {$time_to|date_format:"%d %b %Y"}
        {/if}
    {/capture}

    {$data_1_1_sales = [
        id => "analytics_card_sales",
        preheader => $smarty.capture.date_range_selected_date,
        is_selected_date => true,
        title => __("dashboard.analytics_card.sales"),
        title_button => [
            name => __("dashboard.view_reports"),
            href => "sales_reports.view"
        ],
        number => $totally_paid,
        number_dynamics => $orders_stat.diff.sales,
        graph => []
    ]}

    {if !empty($graphs)}
        {* Add graph data *}
        {$data_1_1_sales.graph.content = []}
        {foreach from=$graphs item="graph" key="chart" name="graphs"}
            {if $chart === "dashboard_statistics_sales_chart"}
                {foreach from=$graph item="data" key="date" name="graph"}
                    {$data_1_1_sales.graph.content[] = [
                        date => $date,
                        prev => $data.prev,
                        cur => $data.cur
                    ]}
                {/foreach}
            {/if}
        {/foreach}
    {/if}
    {$data_1_1_sales = $data_1_1_sales scope=parent}
{/if}
{/strip}