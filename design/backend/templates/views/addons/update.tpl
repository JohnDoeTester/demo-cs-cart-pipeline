{$_addon = $smarty.request.addon}
{script src="js/tygh/fileuploader_scripts.js"}
{script src="js/tygh/backend/addons/update.js"}
{include file="views/profiles/components/profiles_scripts.tpl" states=1|fn_get_all_states}

{capture name="mainbox"}
{if
    $auth.user_type === "UserTypes::ADMIN"|enum
    && !$auth.helpdesk_user_id
    && $addon.marketplace_id
}
    {if "ULTIMATE:FREE"|fn_allowed_for && $is_activated_free !== "YesNo::YES"|enum}
        {if
            $auth.is_root === "YesNo::YES"|enum
            && !$auth.company_id
            && $settings.Upgrade_center.license_number
        }
            <div class="well well-small help-block">
                {include file="buttons/helpdesk.tpl"
                    btn_class="pull-right cm-ajax"
                    btn_text=__("activate")
                    btn_href="helpdesk_connector.activate_license_mail_request"
                }
                <p>{__("helpdesk_account.activate_free_license_message")}</p>
            </div>
        {/if}
    {else}
        <div class="well well-small help-block">
            {include file = "buttons/helpdesk.tpl" btn_class="pull-right"}
            <p>{__("helpdesk_account.signed_out_message.marketplace_single_addon")}</p>
        </div>
    {/if}
{/if}

<div id="content_group{$_addon}">

        {capture name="tabsbox"}

            {hook name="addons:tabs_content"}
                {* General tab *}
                {include file="views/addons/components/detailed_page/tabs/addon_general.tpl"}

                {* Settings tab *}
                {include file="views/addons/components/detailed_page/tabs/addon_settings.tpl"}

                {* Information tab *}
                {include file="views/addons/components/detailed_page/tabs/addon_information.tpl"}

                {* Update tab *}
                {include file="views/addons/components/detailed_page/tabs/addon_update.tpl"}

                {* Subscription tab *}
                {include file="views/addons/components/detailed_page/tabs/addon_subscription.tpl"}

                {* Reviews tab *}
                {include file="views/addons/components/detailed_page/tabs/addon_reviews.tpl"}
            {/hook}

        {/capture}

        {include file="common/tabsbox.tpl"
            content=$smarty.capture.tabsbox
            group_name=$runtime.controller
            active_tab=$smarty.request.selected_section
            track=true
        }

<!--content_group{$_addon}--></div>
{/capture}

{include file="common/mainbox.tpl"
    title=$addon.name
    content=$smarty.capture.mainbox
    sidebar=({include file="views/addons/components/detailed_page/sidebar/detailed_page_sidebar.tpl"})
    buttons=({include file="views/addons/components/detailed_page/header/addon_header_buttons.tpl"})
    select_storefront=$select_storefront
    show_all_storefront=$show_all_storefront
    storefront_switcher_param_name="storefront_id"
}
