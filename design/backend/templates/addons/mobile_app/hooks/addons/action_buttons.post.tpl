{if $_addon == "mobile_app"}
    {include file="buttons/save.tpl"
        but_name="dispatch[addons.update]"
        but_role="action"
        but_target_form="update_addon_`$_addon`_form"
        but_meta="cm-submit hidden cm-addons-save-changeable-settings"
    }

    {include file="buttons/button.tpl"
        but_role="action"
        but_meta="cm-post cm-ajax cm-comet cm-addons-download-config hidden btn-primary"
        but_href="mobile_app.download_config?storefront_id=`$selected_storefront_id`"
        but_text=__("mobile_app.download_config")
    }
{/if}
