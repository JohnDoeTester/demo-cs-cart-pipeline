<div class="litecheckout__group">
    <div class="litecheckout__field litecheckout__field--small">
        <div>
            <label for="dateofbirth" class="ty-control-group__title cm-required">{__("date_of_birth")}</label>
            {include file="common/calendar.tpl" 
                is_changeable_range=false 
                date_id="date_of_birth" 
                date_name="payment_info[date_of_birth]" 
                date_val=$cart.payment_info.date_of_birth|default:$user_data.birthday
            }
        </div>
    </div>
    <div class="litecheckout__field litecheckout__field--large">
        <div>
            <label for="last4ssn" class="ty-control-group__title cm-required">{__("last4ssn")}</label>
            <input id="last4ssn" maxlength="4" size="35" type="text" name="payment_info[last4ssn]" value="{$cart.payment_info.last4ssn}" class="ty-input-text-full cm-autocomplete-off cm-focus" />
        </div>
    </div>
</div>
<div class="litecheckout__group">
    {include file="components/phone.tpl"
        checkout=true
        id="phone_number"
        name="payment_info[phone]"
        value=$cart.payment_info.phone|default:$user_data.b_phone|default:$user_data.phone
        control_group_class="litecheckout__field--small"
        class="cm-autocomplete-off"
        required=true
        width="full"
    }
    <div class="litecheckout__field litecheckout__field--small">
        <input id="passport_number" size="35" type="text" name="payment_info[passport_number]" value="{$cart.payment_info.passport_number}" class="ty-input-text cm-autocomplete-off litecheckout__input" placeholder=" " />
        <label for="passport_number" class="ty-control-group__title litecheckout__label">{__("passport_number")}</label>
    </div>
    <div class="litecheckout__field litecheckout__field--small">
        <input id="drlicense_number" size="35" type="text" name="payment_info[drlicense_number]" value="{$cart.payment_info.drlicense_number}" class="ty-input-text cm-autocomplete-off litecheckout__input" placeholder=" " />
        <label for="drlicense_number" class="ty-control-group__title litecheckout__label">{__("drlicense_number")}</label>
    </div>
    <div class="litecheckout__field litecheckout__field--small">
        <input id="routingcode" maxlength="9" size="35" type="text" name="payment_info[routing_code]" value="{$cart.payment_info.routing_code}" class="ty-input-text cm-autocomplete-off litecheckout__input" placeholder=" " />
        <label for="routingcode" class="ty-control-group__title cm-required litecheckout__label">{__("routing_code")}</label>
    </div>
    <div class="litecheckout__field litecheckout__field--small">
        <input id="accountnr" maxlength="20" size="35" type="text" name="payment_info[account_number]" value="{$cart.payment_info.account_number}" class="ty-input-text cm-autocomplete-off litecheckout__input" placeholder=" " />
        <label for="accountnr" class="ty-control-group__title cm-required litecheckout__label">{__("account_number")}</label>
    </div>
    <div class="litecheckout__field litecheckout__field--small">
        <input id="checknr" maxlength="10" size="35" type="text" name="payment_info[check_number]" value="{$cart.payment_info.check_number}" class="ty-input-text cm-autocomplete-off litecheckout__input" placeholder=" " />
        <label for="checknr" class="ty-control-group__title cm-required litecheckout__label">{__("check_number")}</label>
    </div>
</div>
<div class="litecheckout__group">
    <div class="litecheckout__field">
        <div>
            <label for="p21agree" class="ty-control-group__title cm-required">{__("p21agree")} (<a class="cm-tooltip" title="{__("p21agree_tooltip") nofilter}">?</a>)</label>
            <input id="p21agree" maxlength="8" size="35" type="text" name="payment_info[mm_agree]" value="{$cart.payment_info.mm_agree}" class="ty-input-text-full cm-autocomplete-off" />
        </div>
    </div>
</div>
