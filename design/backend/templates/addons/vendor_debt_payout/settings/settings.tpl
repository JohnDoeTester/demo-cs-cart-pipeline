{include file = "common/subheader.tpl"
    title = __("vendor_debt_payout.actions_on_suspended")
    target = "#collapsable_addon_option_vendor_debt_payout_actions_on_suspended"
}
<div id="collapsable_addon_option_vendor_debt_payout_actions_on_suspended" class="in collapse">
    <fieldset>
        <div class="control-group setting-wide">
            <label class="control-label" for="elm_hide_products">
                {__("vendor_debt_payout.hide_products")}:
            </label>
            <div class="controls">
                <input type="hidden" name="addon_data[options][{$addon_setting_ids.hide_products}]" value="N" />
                <input type="checkbox" id="elm_hide_products" name="addon_data[options][{$addon_setting_ids.hide_products}]" value="Y" {if $addons.vendor_debt_payout.hide_products == "YesNo::YES"|enum}checked="checked"{/if} />
            </div>
        </div>
        <div class="control-group setting-wide">
            <label class="control-label" for="elm_block_admin_panel">
                {__("vendor_debt_payout.block_admin_panel")}:
            </label>
            <div class="controls">
                <input type="hidden" name="addon_data[options][{$addon_setting_ids.block_admin_panel}]" value="N" />
                <input type="checkbox" id="elm_block_admin_panel" name="addon_data[options][{$addon_setting_ids.block_admin_panel}]" value="Y" {if $addons.vendor_debt_payout.block_admin_panel == "YesNo::YES"|enum}checked="checked"{/if} />
            </div>
        </div>
        <div class="control-group setting-wide">
            <label class="control-label" for="elm_disable_suspended_vendors">
                {__("vendor_debt_payout.disable_suspended_vendors")}:
            </label>
            <div class="controls">
                <input type="hidden" name="addon_data[options][{$addon_setting_ids.disable_suspended_vendors}]" value="N" />
                <input
                        type="checkbox"
                        id="elm_disable_suspended_vendors"
                        name="addon_data[options][{$addon_setting_ids.disable_suspended_vendors}]"
                        value="Y"
                        {if $addons.vendor_debt_payout.disable_suspended_vendors == "YesNo::YES"|enum}checked="checked"{/if}
                />
            </div>
        </div>

        <div id="container_disable_vendors_settings" {if $addons.vendor_debt_payout.disable_suspended_vendors !== "YesNo::YES"|enum}class="hidden"{/if}>
            <div class="control-group setting-wide">
                <label class="control-label" for="elm_days_before_disable">
                    {__("vendor_debt_payout.days_before_disable")}:
                </label>
                <div class="controls">
                    <input type="text"
                           class="input-small cm-numeric"
                           data-m-dec="0"
                           data-a-sign=" {__("vendor_debt_payout.day_or_days")}"
                           data-p-sign="s"
                           id="elm_days_before_disable"
                           name="addon_data[options][{$addon_setting_ids.days_before_disable}]"
                           value="{$addons.vendor_debt_payout.days_before_disable}"
                    >
                </div>
            </div>
        </div>
    </fieldset>
</div>

{include file = "common/subheader.tpl"
    title = __("vendor_debt_payout.notifications")
    target = "#collapsable_addon_option_vendor_debt_payout_notifications"
}
<div id="collapsable_addon_option_vendor_debt_payout_notifications" class="in collapse">
    <fieldset>
        <div class="control-group setting-wide">
            <label class="control-label" for="elm_admin_notifications">
                {__("vendor_debt_payout.admin_weekly_digest_of_suspended_vendors")}:
            </label>
            <div class="controls">
                <p>{__("vendor_debt_payout.edit_notifications_link", ["[link]" => "email_templates.update?code=vendor_debt_payout_weekly_digest_of_debtors&area={"SiteArea::ADMIN_PANEL"|enum}&event_id=vendor_debt_payout.weekly_digest_of_debtors&receiver={"UserTypes::ADMIN"|enum}"|fn_url]) nofilter}</p>
            </div>
        </div>

        <div class="control-group setting-wide">
            <label class="control-label" for="elm_vendor_notifications">
                {__("vendor_debt_payout.vendor_notifications")}:
            </label>
            <div class="controls">
                <p>{__("vendor_debt_payout.edit_notifications_link", ["[link]" => "email_templates.update?code=vendor_debt_payout_vendor_days_before_suspended&area={"SiteArea::ADMIN_PANEL"|enum}&event_id=vendor_debt_payout.vendor_days_before_suspend&receiver={"UserTypes::VENDOR"|enum}"|fn_url]) nofilter}</p>
            </div>
        </div>

        <div class="control-group setting-wide">
            <label class="control-label" for="elm_admin_notifications">
                {__("vendor_debt_payout.admin_notifications")}:
            </label>
            <div class="controls">
                <p>{__("vendor_debt_payout.edit_notifications_link", ["[link]" => "email_templates.update?code=vendor_debt_payout_email_admin_notification_vendor_status_changed_to_suspended&area={"SiteArea::ADMIN_PANEL"|enum}&event_id=vendor_debt_payout.vendor_status_changed_to_suspended&receiver={"UserTypes::ADMIN"|enum}"|fn_url]) nofilter}</p>
            </div>
        </div>

        <div id="container_disable_vendors_notify_settings" {if $addons.vendor_debt_payout.disable_suspended_vendors !== "YesNo::YES"|enum}class="hidden"{/if}>
            <div class="control-group setting-wide">
                <label class="control-label" for="elm_admin_notifications_weekly">
                    {__("vendor_debt_payout.vendor_notifications_about_disable")}:
                </label>
                <div class="controls">
                    <p>{__("vendor_debt_payout.edit_notifications_link", ["[link]" => "email_templates.update?code=company_status_suspended_notification&area={"SiteArea::ADMIN_PANEL"|enum}&event_id=vendor_status_changed_suspended&receiver={"UserTypes::VENDOR"|enum}"|fn_url]) nofilter}</p>
                </div>
            </div>

            <div class="control-group setting-wide">
                <label class="control-label" for="elm_admin_notifications_weekly">
                    {__("vendor_debt_payout.admin_notifications_about_disable")}:
                </label>
                <div class="controls">
                    <p>{__("vendor_debt_payout.edit_notifications_link", ["[link]" => "email_templates.update?code=vendor_debt_payout_email_admin_notification_vendor_status_changed_to_disabled&area={"SiteArea::ADMIN_PANEL"|enum}&event_id=vendor_debt_payout.vendor_status_changed_to_disabled&receiver={"UserTypes::ADMIN"|enum}"|fn_url]) nofilter}</p>
                </div>
            </div>
        </div>
    </fieldset>
</div>

{include file = "common/subheader.tpl"
    title = __("vendor_debt_payout.catalog_items")
    target = "#collapsable_addon_option_vendor_debt_payout_catalog_items"
}
<div id="collapsable_addon_option_vendor_debt_payout_catalog_items" class="in collapse">
    <fieldset>
        <div class="well well-small help-block">
            {__("vendor_debt_payout.catalog_items.help")}
        </div>
        <div class="control-group setting-wide">
            <label for="elm_product" class="control-label">
                {__("vendor_debt_payout.product")}:
            </label>
            <div class="controls">
                <p>{__("vendor_debt_payout.edit_description", ["[link]" => fn_url("products.update?product_id={$payout_product_id}")]) nofilter}</p>
            </div>
        </div>

        <div class="control-group setting-wide">
            <label for="elm_product" class="control-label">
                {__("vendor_debt_payout.category")}:
            </label>
            <div class="controls">
                <p>{__("vendor_debt_payout.edit_description", ["[link]" => fn_url("categories.update?category_id={$payout_category_id}")]) nofilter}</p>
            </div>
        </div>
    </fieldset>
</div>
