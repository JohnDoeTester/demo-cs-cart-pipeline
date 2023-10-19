<ul class="pingpp-channels-list">
    {foreach $payment_info.processor_params.channels as $channel => $definition}
        {if $definition.is_enabled == "Y"}
            <li class="pingpp-channels-list-item {foreach $definition.scopes as $scope}pingpp-scope-{$scope}{/foreach}"
                id="pingpp_channel_{$channel}">
                <label>
                    <input type="radio"
                           name="payment_info[channel]"
                           value="{$channel}"
                    >{__("pingpp.channel.`$channel`")}
                </label>
            </li>
        {/if}
    {/foreach}
</ul>