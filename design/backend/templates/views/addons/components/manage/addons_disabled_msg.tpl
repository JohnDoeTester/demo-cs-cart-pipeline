{$is_addon_management_enabled = true}
{if
    fn_allowed_for("MULTIVENDOR") && $selected_storefront_id
    || fn_allowed_for("ULTIMATE") && $runtime.company_id
}
    {$is_addon_management_enabled = false}
{/if}

{if $is_addon_management_enabled && ($settings.init_addons === 'none' || $settings.init_addons === 'core')}
    <div class="alert alert-block addon-info-msg">
        <span>{__("tools_addons_disabled_msg") nofilter}</span>
        <form action="{""|fn_url}" method="post">
            <input type="hidden" name="dispatch" value="addons.tools">
            <button type="submit" class="btn btn-warning" name="init_addons" value="restore">
                {__("tools_re_enable_add_ons")}
            </button>
        </form>
    </div>
{/if}

