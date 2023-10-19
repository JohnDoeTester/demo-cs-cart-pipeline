{if $addon.snapshot_correct && $addon_install_datetime}
    {capture name="tools_list"}
        {hook name="addons_detailed:action_buttons"}
            {if !$is_storefront_admin}
                {if $addon.refresh_url}
                    <li>{btn type="list" method="POST" text=__("refresh") href="{$addon.refresh_url}"}</li>
                {/if}
                {$line = "`$_addon`.confirmation_deleting"|fn_is_lang_var_exists}
                {if $line}
                    {$btn_delete_data["data-ca-confirm-text"] = __("`$_addon`.confirmation_deleting")}
                {/if}
                <li>{btn type="list" class="cm-confirm text-error" method="POST" text=__("uninstall") href="{$addon.delete_url}" data=$btn_delete_data}</li>
            {/if}
        {/hook}
    {/capture}
    {dropdown content=$smarty.capture.tools_list}
    {hook name="addons:action_buttons"}

        {include file="buttons/save.tpl"
            but_name="dispatch[addons.update]"
            but_role="action"
            but_target_form="update_addon_`$_addon`_form"
            but_meta="cm-submit hidden cm-addons-save-settings"
        }

        {* Subscription tab *}
        {include file="buttons/save.tpl"
            but_name="dispatch[addons.update]"
            but_role="action"
            but_target_form="update_addon_`$_addon`_subs_form"
            but_meta="cm-submit hidden cm-addons-save-subscription"
        }

    {/hook}
{elseif $addon.snapshot_correct && !$addon_install_datetime && !$is_storefront_admin}
    {hook name="addons:action_buttons"}
        {btn type="text" class="btn btn-primary" method="POST" text=__("addons.install") href="addons.install?addon={$_addon}&return_url={"addons.update&addon={$addon.addon}"|escape:url}"}
    {/hook}
{elseif !$is_storefront_admin}
    {* Get addon license required text *}
    {include file="views/addons/components/addons/addon_license_required.tpl"
        key=$_addon
    }
    {hook name="addons:action_buttons"}
        <a href={$license_required.href}
            class="btn btn-primary cm-post cm-dialog-opener cm-dialog-auto-size"
            data-ca-target-id={$license_required.target_id}
            data-ca-dialog-title="{$license_required.promo_popup_title}"
        >
            {if $addon_install_datetime}
                {__("addons.activate")}
            {else}
                {__("addons.install")}
            {/if}
        </a>
    {/hook}
{/if}
