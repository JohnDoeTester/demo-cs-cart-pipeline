<div class="statistic-list pull-right clearfix" id="balance_total">
    <div class="table-wrapper">
        <table width="100%">
            <thead>
            <tr>
                <th></th>
                <th width="15%" class="right"><h4>{__("totals")}</h4></th>
            </tr>
            </thead>
            {if isset($totals.income_carried_forward)}
                <tr>
                    <td class="shift-right">{__("vendor_payouts.income_carried_forward")}:</td>
                    <td class="shift-right"><span class="statistic-list-item__price">{include file="common/price.tpl" value=$totals.income_carried_forward}</span></td>
                </tr>
            {/if}
            {if isset($totals.income)}
                <tr>
                    <td class="shift-right"><h4>{__("vendor_payouts.income")}:</h4></td>
                    <td class="shift-right"><h4 class="statistic-list-item__price text-{if $totals.income > 0}success{else}error{/if}">{include file="common/price.tpl" value=$totals.income}</h4></td>
                </tr>
            {/if}
            {if isset($totals.balance_carried_forward)}
                <tr>
                    <td class="shift-right">{__("vendor_payouts.balance_carried_forward")}:</td>
                    <td class="shift-right"><span class="statistic-list-item__price">{include file="common/price.tpl" value=$totals.balance_carried_forward}</span></td>
                </tr>
            {/if}
            {if isset($totals.balance)}
                <tr>
                    <td class="shift-right"><h4>{__("vendor_payouts.balance")}:</h4></td>
                    <td class="shift-right"><h4 class="statistic-list-item__price text-{if $totals.balance > 0}success{else}error{/if}">{include file="common/price.tpl" value=$totals.balance}</h4></td>
                </tr>
            {/if}
        </table>
    </div>
<!--balance_total--></div>
