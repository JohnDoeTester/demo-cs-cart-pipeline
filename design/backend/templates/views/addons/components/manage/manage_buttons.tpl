{capture name="tools_list"}

    {hook name="addons:action_buttons"}

        {$is_addon_management_enabled = true}
        {if
            fn_allowed_for("MULTIVENDOR") && $selected_storefront_id
            || fn_allowed_for("ULTIMATE") && $runtime.company_id
        }
            {$is_addon_management_enabled = false}
        {/if}

        {if $is_addon_management_enabled && !"RESTRICTED_ADMIN"|defined}
            <li>
                {include file="common/popupbox.tpl"
                    id="upload_addon"
                    text=__("upload_addon")
                    title=__("upload_addon")
                    content=({include file="views/addons/components/upload_addon.tpl"})
                    act="edit"
                    link_class="cm-dialog-auto-size"
                    link_text=__("manual_installation")
                }
            </li>
        {/if}

        {if $is_addon_management_enabled && $settings.init_addons !== 'none'}
            <li>
                {btn type="text"
                    method="POST"
                    text=__("tools_addons_disable_all")
                    href="addons.tools?init_addons=none"
                }
            </li>
        {/if}

        {if $is_addon_management_enabled && ($settings.init_addons !== 'core' && $settings.init_addons !== 'none')}
            <li>
                {btn type="text"
                    method="POST"
                    text=__("tools_addons_disable_third_party")
                    href="addons.tools?init_addons=core"
                }
            </li>
        {/if}

    {/hook}

{/capture}

{dropdown content=$smarty.capture.tools_list}