<div class="control-group setting-wide">
    <label for="elm_commission_includes" class="control-label">{__("addons.paypal_adaptive.commission_includes")}:</label>
    <div class="controls">
        <label class="radio inline" for="elm_commission_order">
            <input type="radio" name="ppa_settings[collect_payouts]" id="elm_commission_order" {if $ppa_settings.collect_payouts|default:"N" == "N"}checked="checked"{/if} value="N" />
            {__("addons.paypal_adaptive.order_commission")}
        </label>
        <label class="radio inline" for="elm_commission_order_and_payouts">
            <input type="radio" name="ppa_settings[collect_payouts]" id="elm_commission_order_and_payouts" {if $ppa_settings.collect_payouts|default:"N" == "Y"}checked="checked"{/if} value="Y" />
            {__("addons.paypal_adaptive.order_commission_and_payouts")}
        </label>
    </div>
</div>
<div id="paypal_adaptive_logo_uploader" class="control-group setting-wide paypal_adaptive ">
    <label class="control-label" for="elm_paypal_adaptive_logo">
        {__("addons.paypal_adaptive.logo")}:
        <p class="muted description">{__("ttc_addons.paypal_adaptive.logo")}</p>
    </label>
    <div class="controls">
        {include file="common/attach_images.tpl" image_name="paypal_adaptive_logo" image_object_type="paypal_adaptive_logo" image_pair=$ppa_settings.main_pair no_thumbnail=true}
    </div>
</div>