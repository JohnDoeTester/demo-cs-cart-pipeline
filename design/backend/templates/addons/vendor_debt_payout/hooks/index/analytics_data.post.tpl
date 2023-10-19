{strip}
{*
    $time_from
    $time_to
*}
    {capture name="refill_balance"}
        <form id="vendor_debt_payout_refill_balance" name="refill_balance" method="post" action="{"debt.refill_balance"|fn_url}" target="_blank">
            {$amount = ""}
            {if $current_balance < 0}
                {$amount = $current_balance|abs}
            {/if}
            <div id="vendor_debt_payout_refill_amount" class="control-group hidden cm-refill-balance-block">
                <label class="control-label cm-refill-balance-label" for="elm_vendor_debt_payout_refill_balance">
                    {__("vendor_debt_payout.dashboard.analytics_card.enter_an_amount")}:
                </label>
                <div class="controls">
                    {include file="common/price.tpl"
                        input_id="elm_vendor_debt_payout_refill_balance"
                        input_name="refill_amount"
                        view="input"
                        class="input-full cm-refill-balance-amount"
                        value=$amount
                    }
                </div>
                {include file="addons/vendor_debt_payout/views/vendor_debt_payout/components/refill_balance_button.tpl"}
            </div>
            <a id="on_vendor_debt_payout_refill_amount" class="btn btn-primary cm-combination">
                {__("vendor_debt_payout.dashboard.analytics_card.refill_balance")}
            </a>
        </form>
    {/capture}
    {foreach $analytics_data as $analytics_column_key => $analytics_column}
        {foreach $analytics_column as $analytics_card_key => $analytics_card}
            {if $analytics_card.id === "analytics_card_vendor_current_balance"}
                {$analytics_data[$analytics_column_key][$analytics_card_key].content[] = $smarty.capture.refill_balance}
                {$analytics_data[$analytics_column_key][$analytics_card_key].scripts[] = "js/addons/vendor_debt_payout/func.js"}
            {/if}

            {if $analytics_card.id === "analytics_card_vendors_with_sales"}
                {$analytics_data[$analytics_column_key][$analytics_card_key].resource_list.content[] = [
                    id => "vendor_debt_payout_analytics_card_vendors_with_sales_suspended_vendors",
                    name => __("vendor_debt_payout.dashboard.analytics_card.suspended_vendors"),
                    href => "companies.manage?time_from={$time_from}&time_to={$time_to}&get_suspended=Y",
                    value => $dashboard_vendors_activity.suspended_vendors,
                    value_href => "companies.manage?time_from={$time_from}&time_to={$time_to}&get_suspended=Y"
                ]}
            {/if}
        {/foreach}
    {/foreach}
    {$analytics_data = $analytics_data scope=parent}
{/strip}