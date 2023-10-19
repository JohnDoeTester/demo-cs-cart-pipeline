{$lowers_allowed_balance_attributes = [
    "data-a-sign" => {$currencies.$primary_currency.symbol|strip_tags nofilter},
    "data-a-dec"  => ".",
    "data-a-sep"  => ","
]}
{if $currencies.$primary_currency.after == "Y"}
    {$lowers_allowed_balance_attributes["data-p-sign"] = "s"}
{/if}

{$grace_period_to_refill_balance_attributes = [
    "size"        => "4",
    "data-a-sign" => " {__("vendor_debt_payout.day_or_days")}",
    "data-m-dec"  => "0",
    "data-a-sep"  => ",",
    "data-p-sign" => "s"
]}

{$has_global_individual_settings_permission = (
    $auth.user_type === "UserTypes::ADMIN"|enum
    && $config.tweaks.allow_global_individual_settings
    && fn_check_permissions("addons", "update", "admin", "POST")
    && !"ULTIMATE:FREE"|fn_allowed_for
)}

{if $has_global_individual_settings_permission
    && (isset($addons.vendor_debt_payout.global_lowers_allowed_balance)
        || isset($addons.vendor_debt_payout.global_grace_period_to_refill_balance)
    )
}
    <div class="well well-small help-block">
        {__("vendor_debt_payout.global_individual_additional_settings", [
            "[addons_url]" => "addons.update&addon=vendor_debt_payout&selected_section=settings"|fn_url,
            "[icon]" => "<i class=\"icon-sitemap\"></i>"
        ]) nofilter}
    </div>
{/if}

{if !isset($addons.vendor_debt_payout.global_lowers_allowed_balance) || !isset($addons.vendor_debt_payout.global_grace_period_to_refill_balance)}

    <h4 class="subheader hand" data-toggle="collapse" data-target="#collapsable_addon_option_vendor_debt_payout_actions_on_suspended">
        {__("vendor_debt_payout.actions_on_suspended")}
        <span class="exicon-collapse"></span></h4>
    <div id="collapsable_addon_option_vendor_debt_payout_actions_on_suspended" class="in collapse">
        <fieldset>
            {if !isset($addons.vendor_debt_payout.global_lowers_allowed_balance)}
                <div class="control-group">
                    <label class="control-label" for="elm_lowers_allowed_balance_{$id}">{__("vendor_debt_payout.lowest_allowed_balance")}:</label>
                    <div class="controls">
                        {component
                            name="vendor_debt_payout.select_lowers_allowed_balance"
                            value=$plan.lowers_allowed_balance|default:"default"
                            custom_input_styles="cm-numeric"
                            custom_input_attributes=$lowers_allowed_balance_attributes
                        }{/component}
                    </div>
                </div>
            {/if}
            {if !isset($addons.vendor_debt_payout.global_grace_period_to_refill_balance)}
                <div class="control-group">
                    <label class="control-label" for="elm_lowers_allowed_balance_{$id}">{__("vendor_debt_payout.grace_period_to_refill_balance")}:</label>
                    <div class="controls">
                        {component
                            name="vendor_debt_payout.select_grace_period_to_refill_balance"
                            value=$plan.grace_period_to_refill_balance|default:"default"
                            custom_input_styles="cm-numeric"
                            custom_input_attributes=$grace_period_to_refill_balance_attributes
                        }{/component}
                    </div>
                </div>
            {/if}
            <div class="well well-small">{__("vendor_debt_payout.lowest_allowed_balance_info_text", ["[link]" => "addons.update&addon=vendor_debt_payout"|fn_url]) nofilter}</div>
        </fieldset>
    </div>
{/if}