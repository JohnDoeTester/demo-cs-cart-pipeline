{$required = $required|default:"cm-required"}

<div class="control-group">
    <label for="dateofbirth" class="control-label {$required}">{__("date_of_birth")}:</label>
    <div class="controls">
        {include file="common/calendar.tpl" date_id="date_of_birth" date_name="payment_info[date_of_birth]" date_val=$cart.payment_info.date_of_birth|default:$user_data.birthday}
    </div>
</div>
<div class="control-group">
    <label for="last4ssn" class="control-label {$required}">{__("last4ssn")}:</label>
    <div class="controls">
        <input id="last4ssn" maxlength="4" size="35" type="text" name="payment_info[last4ssn]" value="{$cart.payment_info.last4ssn}" class="cm-autocomplete-off" />
    </div>
</div>
{include file="components/phone.tpl"
    id="phone_number"
    name="payment_info[phone]"
    value=$cart.payment_info.phone|default:$user_data.b_phone|default:$user_data.phone
    required=($required === "cm-required")
    class="cm-autocomplete-off"
    width="full"
}
<div class="control-group">
    <label for="passport_number" class="control-label ">{__("passport_number")}:</label>
    <div class="controls">
        <input id="passport_number" size="35" type="text" name="payment_info[passport_number]" value="{$cart.payment_info.passport_number}" class="cm-autocomplete-off" />
    </div>
</div>
<div class="control-group">
    <label for="drlicense_number" class="control-label ">{__("drlicense_number")}:</label>
    <div class="controls">
        <input id="drlicense_number" size="35" type="text" name="payment_info[drlicense_number]" value="{$cart.payment_info.drlicense_number}" class="cm-autocomplete-off" />
    </div>
</div>
<div class="control-group">
    <label for="routingcode" class="control-label {$required}">{__("routing_code")}:</label>
    <div class="controls">
        <input id="routingcode" maxlength="9" size="35" type="text" name="payment_info[routing_code]" value="{$cart.payment_info.routing_code}" class="cm-autocomplete-off" />
    </div>
</div>
<div class="control-group">
    <label for="accountnr" class="control-label {$required}">{__("account_number")}:</label>
    <div class="controls">
        <input id="accountnr" maxlength="20" size="35" type="text" name="payment_info[account_number]" value="{$cart.payment_info.account_number}" class="cm-autocomplete-off" />
    </div>
</div>
<div class="control-group">
    <label for="checknr" class="control-label {$required}">{__("check_number")}:</label>
    <div class="controls">
        <input id="checknr" maxlength="10" size="35" type="text" name="payment_info[check_number]" value="{$cart.payment_info.check_number}" class="cm-autocomplete-off" />
    </div>
</div>
<div class="control-group">
    <label for="p21agree" class="control-label {$required}">{__("p21agree")} (<a class="cm-tooltip" title="{__("p21agree_tooltip") nofilter}">?</a>):</label>
    <div class="controls">
        <input id="p21agree" maxlength="8" size="35" type="text" name="payment_info[mm_agree]" value="{$cart.payment_info.mm_agree}" class="cm
    -autocomplete-off" />
    </div>
</div>
