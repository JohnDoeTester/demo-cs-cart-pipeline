<button class="litecheckout__submit-btn {$but_meta}"
        type="submit"
        name="{$but_name}"
        {if $but_onclick}onclick="{$but_onclick nofilter}"{/if}
        {if $but_id}id="{$but_id}"{/if}
        {if $but_disabled}disabled{/if}
>
    {capture name="order_total"}
        {if $cart.payment_surcharge && !$take_surcharge_from_vendor}
            {$_total = $cart.total + $cart.payment_surcharge}
        {/if}

        {include file="common/price.tpl" value=$_total|default:$cart.total}
    {/capture}

    {if !$but_text}
        {$but_text = __("lite_checkout.place_an_order_for", ["[amount]" => $smarty.capture.order_total])}
    {/if}

    {$but_text nofilter}
{if $but_id}<!--{$but_id}-->{/if}</button>
