{hook name="checkout:location_country"}
<div class="litecheckout__field litecheckout__field--auto">
    <select data-ca-lite-checkout-field="user_data.s_country"
            class="cm-country cm-location-shipping litecheckout__input litecheckout__input--selectable litecheckout__input--selectable--select"
            data-ca-lite-checkout-element="country"
            data-ca-lite-checkout-last-value="{$user_data.s_country}"
            required
            id="litecheckout_country"
    >
        <option disabled data-ca-rebuild-states="skip" {if !$user_data.s_country}selected{/if}>{__("select_country")}</option>
        {foreach $countries as $code => $country}
            <option value="{$code}"
                {if $user_data.s_country == $code}selected="selected"{/if}
            >{$country}</option>
        {/foreach}
    </select>

    <label class="litecheckout__label cm-required" for="litecheckout_country">{__("country")}: </label>
</div>
{/hook}