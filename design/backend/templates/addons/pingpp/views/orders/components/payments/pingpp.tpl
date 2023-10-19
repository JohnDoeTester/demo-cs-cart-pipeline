{$payment_info = $cart.payment_id|fn_get_payment_method_data}
<ul class="pingpp-channels-list unstyled">
    {$checked = !$cart.payment_info.channel}
    {foreach $payment_info.processor_params.channels as $channel => $definition}
        {if $definition.is_enabled == "Y"}
            <li class="pingpp-channels-list-item" id="pingpp_channel_{$channel}">
                <label>
                    <input type="radio"
                           name="payment_info[channel]"
                           value="{$channel}"
                           {if $checked || $channel == $cart.payment_info.channel}checked="checked"{/if}
                    >{__("pingpp.channel.`$channel`")}
                </label>
            </li>
            {$checked = false}
        {/if}
    {/foreach}
</ul>