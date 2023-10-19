<div class="litecheckout__group">
    {if $cart.user_data.phone}
        {$phone = $cart.user_data.phone}
    {else}
        {if $cart.user_data.b_phone && !$cart.user_data.s_phone}
            {$phone = $cart.user_data.b_phone}
        {elseif !$cart.user_data.b_phone && $cart.user_data.s_phone}
            {$phone = $cart.user_data.s_phone}
        {elseif $cart.user_data.b_phone && $cart.user_data.s_phone}
            {if $settings.Checkout.address_position == "billing_first"}
                {$phone = $cart.user_data.b_phone}
            {else}
                {$phone = $cart.user_data.s_phone}
            {/if}
        {else}
            {$phone = ""}
        {/if}
    {/if}
    {include file="components/phone.tpl"
        checkout=true
        id="customer_phone"
        name="payment_info[customer_phone]"
        value=$phone
        label_text=$field.description
        class="cm-autocomplete-off"
        width="full"
    }
</div>
