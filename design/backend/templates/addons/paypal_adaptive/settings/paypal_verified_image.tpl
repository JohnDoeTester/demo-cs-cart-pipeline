<div class="control-group setting-wide" id="paypal_ver_image_uploader">
    <label class="control-label" for="elm_paypal_verification_image">{__("paypal_ver_image")}:</label>
    <div class="controls">
        {include file="common/attach_images.tpl" image_name="paypal_ver_image" image_object_type="paypal_ver_image" image_pair=$pp_adaptive_settings.main_pair no_thumbnail=true}
        <p class="muted description">{__("paypal_adaptive.tooltips.paypal_ver_image")}</p>
    </div>
</div>

