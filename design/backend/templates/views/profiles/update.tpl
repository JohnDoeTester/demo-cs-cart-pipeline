{if $user_data}
    {assign var="id" value=$user_data.user_id}
{else}
    {assign var="id" value=0}
{/if}

{$show_storefront_picker = $show_storefront_picker|default:false}

{include file="views/profiles/components/profiles_scripts.tpl"}

<form name="profile_form" enctype="multipart/form-data" action="{""|fn_url}" method="post" class="admin-content-external-form form-horizontal form-edit {if ($runtime.company_id && $id && $user_data.company_id != $runtime.company_id && $id != $auth.user_id) || $hide_inputs} cm-hide-inputs{/if}">
{capture name="mainbox"}

{capture name="tabsbox"}
    {$hide_inputs=false}

    {if $user_data.user_type == $auth.user_type && $user_data.is_root == 'Y' && $user_data.user_id != $auth.user_id && (!$user_data.company_id || $user_data.company_id == $auth.company_id)}
        {$hide_inputs=true}
    {/if}

    {if "ULTIMATE"|fn_allowed_for && !$user_data|fn_allow_save_object:"users" && $id && !$id|fn_ult_check_users_usergroup_companies && $user_data.user_id != $auth.user_id}
        {$hide_inputs=true}
    {/if}

    {if "MULTIVENDOR"|fn_allowed_for && (!$user_data|fn_allow_save_object:"users" || $runtime.company_id && ($smarty.request.user_type == 'C' || $user_data.company_id|fn_string_not_empty && $user_data.company_id != $runtime.company_id)) && $user_data.user_id != $auth.user_id}
        {$hide_inputs=true}
    {/if}

    <input type="hidden" name="user_id" value="{$id}" />
    <input type="hidden" class="cm-no-hide-input" name="selected_section" id="selected_section" value="{$selected_section}" />
    <input type="hidden" class="cm-no-hide-input" name="user_type" value="{$smarty.request.user_type}" />

    <div id="content_general">
        {hook name="profiles:general_content"}
            {include file="views/profiles/components/profiles_account.tpl"}

            {if ("ULTIMATE"|fn_allowed_for || $user_type == "V") && $id != $auth.user_id}

                {$zero_company_id_name_lang_var = false}
                {if "ULTIMATE"|fn_allowed_for && $user_type|fn_check_user_type_admin_area}
                    {$zero_company_id_name_lang_var = 'all_vendors'}
                {/if}

                {include file="views/companies/components/company_field.tpl"
                    name="user_data[company_id]"
                    id="user_data_company_id"
                    selected=$user_data.company_id
                    zero_company_id_name_lang_var=$zero_company_id_name_lang_var
                    disable_company_picker=$hide_inputs
                }

            {else}
                <input type="hidden" name="user_data[company_id]" value="{$user_data.company_id|default:0}">
            {/if}

            {if $show_storefront_picker}
                <div class="control-group">
                    <label class="control-label">{__("storefront")}:</label>
                    <div class="controls">
                        {include file="views/storefronts/components/picker/picker.tpl"
                            input_name="user_data[storefront_id]"
                            picker_id="elm_user_data_storefront_id"
                            item_ids=[$user_data.storefront_id]
                            show_advanced=false
                            show_empty_variant="MULTIVENDOR"|fn_allowed_for
                            empty_variant_text=__("all_storefronts")
                            allow_clear=true
                            disabled=$hide_inputs
                            select_class="cm-no-hide-input"
                        }
                    </div>
                </div>
            {else}
                <input type="hidden" name="user_data[storefront_id]" value="{$user_data.storefront_id|default:0}">
            {/if}

            {if
                $id
                && $user_data.user_type === "UserTypes::ADMIN"|enum
                && $id == $auth.user_id
            }
                {if "ULTIMATE:FREE"|fn_allowed_for && $is_activated_free !== "YesNo::YES"|enum}
                    {if
                        $auth.is_root === "YesNo::YES"|enum
                        && !$auth.company_id
                        && $settings.Upgrade_center.license_number
                    }
                        <div class="control-group">
                            <label for="helpdesk_account_{$id}" class="control-label">{__("helpdesk_account.helpdesk_account")}</label>
                            <div class="controls">
                                <p>{__("helpdesk_account.activate_free_license_message")}</p>
                                {include file="buttons/helpdesk.tpl"
                                    btn_href="helpdesk_connector.activate_license_mail_request"
                                    btn_text=__("activate")
                                    btn_class="cm-ajax"
                                }
                            </div>
                        </div>
                    {/if}
                {else}
                    <div class="control-group">
                        <label for="helpdesk_account_{$id}" class="control-label">{__("helpdesk_account.helpdesk_account")}</label>
                        {if $user_data.helpdesk_user_id}
                            <div class="controls">
                                <p>{__("helpdesk_account.signed_in_message")}</p>
                                <p>
                                    {include file = "buttons/helpdesk.tpl"
                                        btn_href = fn_url($helpdesk_disconnect_url)
                                        btn_text = __("helpdesk_account.sign_out")
                                    }
                                </p>
                            </div>
                        {else}
                            <div class="controls">
                                <p>{__("helpdesk_account.signed_out_message")}</p>
                                <p>
                                    {include file = "buttons/helpdesk.tpl"
                                        btn_href = fn_url($helpdesk_connect_url)
                                        btn_text = __("helpdesk_account.sign_in")
                                    }
                                </p>
                            </div>
                        {/if}
                    </div>
                {/if}
            {/if}
        {/hook}

        {include file="views/profiles/components/profile_fields.tpl" section="C" title=__("contact_information")}

        {if $settings.General.user_multiple_profiles == "Y" && $id}
            {include file="common/subheader.tpl" title=__("user_profile_info")}
            <p class="form-note">{__("text_multiprofile_notice")}</p>
            {include file="views/profiles/components/multiple_profiles.tpl"}
        {/if}

        {if $profile_fields.B}
            {include file="views/profiles/components/profile_fields.tpl" section="B" title=__("billing_address")}
            {include file="views/profiles/components/profile_fields.tpl" section="S" title=__("shipping_address") body_id="sa" shipping_flag=$profile_fields|fn_compare_shipping_billing}
        {else}
            {include file="views/profiles/components/profile_fields.tpl" section="S" title=__("shipping_address") shipping_flag=false}
        {/if}
    </div>

    {if $navigation.tabs.usergroups}
        <div id="content_usergroups" class="cm-hide-save-button">
            {if $usergroups}
            <div class="table-responsive-wrapper">
                <table width="100%" class="table table-middle table-responsive">
                <thead>
                <tr>
                    <th width="50%">{__("usergroup")}</th>
                    <th class="right" width="10%">{__("status")}</th>
                </tr>
                </thead>
                {foreach from=$usergroups item=usergroup}
                    <tr>
                        <td data-th="{__("usergroup")}"><a href="{"usergroups.manage#group`$usergroup.usergroup_id`"|fn_url}">{$usergroup.usergroup}</a></td>
                        <td class="right" data-th="{__("status")}">
                            {$hide_for_vendor=false}
                            {if !"usergroups.manage"|fn_check_view_permissions:"POST"}
                                {$hide_for_vendor=true}
                            {/if}
                            {if $user_data.usergroups[$usergroup.usergroup_id]}
                                {assign var="ug_status" value=$user_data.usergroups[$usergroup.usergroup_id].status}
                            {else}
                                {assign var="ug_status" value="F"}
                            {/if}
                            {include file="common/select_popup.tpl" id=$usergroup.usergroup_id status=$ug_status hidden="" items_status="profiles"|fn_get_predefined_statuses extra="&user_id=`$id`" update_controller="usergroups" notify=true notify_text=__("notify_user") hide_for_vendor=$hide_for_vendor}
                        </td>
                    </tr>
                {/foreach}
                </table>
            </div>
            {else}
                <p class="no-items">{__("no_data")}</p>
            {/if}
        </div>
    {/if}

    <div id="content_addons">
        {hook name="profiles:detailed_content"}
        {/hook}
    </div>
    {if $show_api_tab}
        <div id="content_api">
            {if !$hide_api_checkbox}
                <div class="control-group">
                    <label for="sw_api_container" class="control-label">{__("api_access_for_user")}</label>
                    <div class="controls">
                        {include file="common/switcher.tpl"
                            checked=$user_data.api_key != ""
                            input_id="sw_api_container"
                            input_name="user_api_status"
                            input_value="YesNo::YES"|enum
                            input_attrs=["data-ca-api-key-container-id" => "api_container", "data-ca-show-api-key-warning" => "{if $user_data.api_key}false{else}true{/if}"]
                        }
                    </div>
                </div>
            {/if}

            <div id="api_container"{if $user_data.api_key === ""} class="hidden"{/if}>
                <div class="control-group">
                    <label class="control-label">{__("api_key")}</label>
                    <div class="controls">
                        {if $user_data.api_key}
                            {include file="buttons/button.tpl" but_role="action" but_id="refresh_api_key" but_target="api_key_holder" but_text="{__("generate_new_api_key")}" but_meta="btn-indent"}
                            <input type="text" class="input-large" name="user_data[raw_api_key]" value="*************************" disabled id="api_key_holder"/>
                        {else}
                            <input type="text" class="input-large js-new-api-key" name="user_data[raw_api_key]" value="{$new_api_key}" readonly="readonly"  disabled />
                        {/if}
                        <div class="well well-small help-block{if $user_data.api_key} hidden{/if}">
                            {__("please_copy_api_key")}
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {script src="js/tygh/backend/api_access.js"}
    {/if}

    {hook name="profiles:tabs_content"}
    {/hook}
    {if !$user_data|fn_allow_save_object:"users" && $id && $user_data.user_id != $auth.user_id || $hide_inputs}
        {assign var="hide_first_button" value=true}
    {/if}

    {if $id}
        {hook name="profiles:tabs_extra"}
        {/hook}
    {/if}
{/capture}

{include file="common/tabsbox.tpl" content=$smarty.capture.tabsbox group_name=$runtime.controller active_tab=$selected_section track=true}

{/capture}

{if !$id}
    {$_user_desc = $user_type|fn_get_user_type_description}

    {$title = "{__("new_profile")}: `$_user_desc`"}
{else}
    {if $user_data.firstname}
        {$title = "`$user_data.firstname` `$user_data.lastname`"}
    {elseif $user_data.b_firstname}
        {$title = "`$user_data.b_firstname` `$user_data.b_lastname`"}
    {else}
        {$title = "`$user_data.email`"}
    {/if}
{/if}

{$redirect_url = "profiles.manage%26user_type=`$user_data.user_type`"}
{capture name="sidebar"}
    {hook name="profile:manage_sidebar"}
        {include file="views/profiles/components/profile_orders.tpl"}
    {/hook}
{/capture}
{capture name="buttons"}
    {capture name="tools_list"}
        {hook name="profiles:update_tools_list"}
        {if $user_data.user_type == "C"}
            <li>{btn type="list" text=__("view_all_orders") href="orders.manage?user_id=`$id`"}</li>
        {/if}
        {if $user_data.user_type|fn_user_need_login && (!$runtime.company_id || $runtime.company_id == $auth.company_id) && $user_data.user_id != $auth.user_id && !($user_data.user_type == 'A' && $user_data.is_root == 'Y' && !$user_data.company_id)}
            <li>{btn type="list" target="_blank" text=__("log_in_as_user") href="profiles.act_as_user?user_id=`$id`"}</li>
            {hook name="update_tools_list:anonymization"}
                {if $user_data.user_type === "UserTypes::CUSTOMER"|enum}
                    <li>{btn type="list" text=__("anonymize") class="cm-confirm" data=["data-ca-confirm-text" => "{__("text_anonymize_question")}"] href="profiles.anonymize?user_id=`$id`" method="POST"}</li>
                {/if}
            {/hook}
            <li class="divider"></li>
            {if !$hide_inputs}
                <li>{btn type="list" text=__("delete") class="cm-confirm" href="profiles.delete?user_id=`$id`&redirect_url=`$redirect_url`" method="POST"}</li>
            {/if}
        {/if}
        {/hook}
    {/capture}
    {if $id && $smarty.capture.tools_list|trim !==""}
        {dropdown content=$smarty.capture.tools_list}
    {/if}
<div class="btn-group btn-hover dropleft">
    {hook name="profiles:update_buttons"}
        {if $id}
            {include file="buttons/save_cancel.tpl" but_meta="dropdown-toggle" but_role="submit-link" but_name="dispatch[profiles.`$runtime.mode`]" but_target_form="profile_form" save=$id}
        {else}
            {include file="buttons/button.tpl" but_text=__("create") but_meta="dropdown-toggle" but_role="submit-link" but_name="dispatch[profiles.`$runtime.mode`]" but_target_form="profile_form" save=$id}
        {/if}
        <ul class="dropdown-menu">
            <li><a><input type="checkbox" name="notify_customer" value="Y" checked="checked"  id="notify_customer" />
                {__("notify_user")}</a></li>
        </ul>
    {/hook}
</div>

{/capture}

{include file="common/mainbox.tpl"
    title=$title
    content=$smarty.capture.mainbox
    buttons=$smarty.capture.buttons
    sidebar=$smarty.capture.sidebar}
</form>
