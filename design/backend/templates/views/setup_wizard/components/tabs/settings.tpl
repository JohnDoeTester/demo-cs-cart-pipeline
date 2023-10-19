<div id="sw_settings_extra">
    <div class="sw-columns-block">
        <div class="form-horizontal">
            <div class="control-icon sw_notifications_icon"></div>
            <div class="control-group">
                <label class="control-label">{__("sw.admin_notifications")}:</label>
                <div class="controls">
                    {include file="buttons/button.tpl" but_href="notification_settings.manage?receiver_type=A" but_text=__("sw.configure") but_role="action" but_target="_blank"}
                </div>
            </div>
        </div>
        {if "MULTIVENDOR"|fn_allowed_for}
            <div class="form-horizontal">
                <div class="control-group">
                    <label class="control-label">{__("sw.vendor_notifications")}:</label>
                    <div class="controls">
                        {include file="buttons/button.tpl" but_href="notification_settings.manage?receiver_type=V" but_text=__("sw.configure") but_role="action" but_target="_blank"}
                    </div>
                </div>
            </div>
        {/if}
    </div>
<!--sw_settings_extra--></div>
