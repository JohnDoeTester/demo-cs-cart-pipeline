{include file="views/profiles/components/profiles_scripts.tpl"}

<div class="litecheckout__group" id="litecheckout_use_billing_address_form">
    <div class="litecheckout__item" style="width: 100%;">
        {$use_billing_address = $user_data.ship_to_another|default:false}

        <div class="ty-profile-field__switch ty-address-switch clearfix litecheckout__address-switch">
            <div class="ty-profile-field__switch-label"><label for="sw_litecheckout_step_billing_address_suffix_no">{__("text_billing_address_is_different_from_shipping")}</label></div>
            <div class="ty-profile-field__switch-actions">
                <input
                    type="hidden"
                    value="0"
                    name="ship_to_another"
                    data-ca-lite-checkout-field="ship_to_another"
                    data-ca-lite-checkout-auto-save-on-change="true"
                >
                <input
                    type="hidden"
                    value="0"
                    name="user_data[ship_to_another]"
                    data-ca-lite-checkout-field="user_data.ship_to_another"
                    data-ca-lite-checkout-auto-save-on-change="true"
                >
                <input
                    id="sw_litecheckout_step_billing_address_suffix_no"
                    type="checkbox"
                    value="1"
                    name="user_data[ship_to_another]"
                    data-ca-lite-checkout-field="user_data.ship_to_another"
                    data-ca-lite-checkout-auto-save-on-change="true"
                    {if $use_billing_address}checked="checked"{/if}
                    class="checkbox cm-switch-availability cm-switch-visibility"
                >
            </div>
        </div>
    </div>
<!--litecheckout_use_billing_address_form--></div>

<div class="litecheckout__container" id="litecheckout_billing_address_wrapper">
    <div class="{if !$use_billing_address}hidden{/if}" id="litecheckout_step_billing_address">
        <div class="litecheckout__group">
            <input
                type="hidden"
                value="1"
                name="ship_to_another"
                data-ca-lite-checkout-field="ship_to_another"
                data-ca-lite-checkout-auto-save-on-change="true"
                {if !$use_billing_address}disabled="disabled"{/if}
            >
            {if $use_billing_address}
                {$profile_fields_data = $user_data}
            {else}
                {$profile_fields_data = $current_user_data|default:$user_data}
            {/if}
            {include
                file="views/checkout/components/profile_fields.tpl"
                profile_fields=$profile_fields
                disable_all_fields=!$use_billing_address
                user_data=$profile_fields_data
                section="ProfileFieldSections::BILLING_ADDRESS"|enum
                exclude=["customer_notes"]
            }
        </div>
    <!--litecheckout_step_billing_address--></div>
<!--litecheckout_billing_address_wrapper--></div>
