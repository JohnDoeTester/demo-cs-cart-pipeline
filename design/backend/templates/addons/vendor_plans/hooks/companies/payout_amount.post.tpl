{if $payout.order_id
    && ($payout.commission != 0 || $payout.commission_amount != 0)
}
    {capture name="order_amount"}
        {include file="common/price.tpl" value=$payout.order_amount}
    {/capture}
    <br>
    <small class="muted">
        {__("vendor_plans.out_of_amount", [
            "[amount]" => $smarty.capture.order_amount
        ])}
    </small>
{/if}