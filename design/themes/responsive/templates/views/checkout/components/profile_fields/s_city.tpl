{$city = $user_data.s_city}
{$state_descr = $user_data.s_state_descr}
{$state = $user_data.s_state}
{$zipcode = $user_data.s_zipcode}
{$country = $user_data.s_country}

{hook name="checkout:location_city"}
<div class="litecheckout__field litecheckout__field--fill cm-field-container"
    data-ca-error-message-target-method="append">
    <input type="text"
           data-ca-lite-checkout-field="user_data.s_city"
           id="litecheckout_city"
           data-ca-lite-checkout-element="city"
           data-ca-lite-checkout-last-value="{$city}"
           placeholder=" "
           value="{$city}"
           class="litecheckout__input"
    />
    <label class="litecheckout__label cm-required" for="litecheckout_city">{__("city")} </label>
</div>
{/hook}