{include file="common/subheader.tpl" title=__("addons.paypal.connect_to_paypal")}
<div class="control-group">
    <label class="control-label">{__("addons.paypal.use_buttons_to_signup")}:</label>
    <div class="controls">
        <button
                type="submit"
                name="dispatch[payments.update.paypal_signup_live]"
                class="btn btn-connect-to-paypal cm-skip-validation"
                formtarget="PPFrame"
        >{__("addons.paypal.configure_live")}</button>

        <button
                type="submit"
                name="dispatch[payments.update.paypal_signup_test]"
                class="btn btn-connect-to-paypal cm-skip-validation"
                formtarget="PPFrame"
        >{__("addons.paypal.configure_test")}</button>
    </div>
</div>