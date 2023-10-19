{include file="views/profiles/components/profiles_scripts.tpl"}

{$dispatch = "profiles.update"}

{if $runtime.action}
    {$dispatch = "profiles.update.{$runtime.action}"}
{/if}

{if $runtime.mode == "add" && $settings.General.quick_registration == "YesNo::YES"|enum}
    <div class="ty-account">

        <form name="profiles_register_form" enctype="multipart/form-data" action="{""|fn_url}" method="post">
            {include file="views/profiles/components/profile_fields.tpl" section="C" nothing_extra="YesNo::YES"|enum}
            {include file="views/profiles/components/profiles_account.tpl" nothing_extra="YesNo::YES"|enum location="checkout"}

            {if $smarty.request.return_url}
                <input type="hidden" name="return_url" value="{$smarty.request.return_url}" />
            {/if}

            {hook name="profiles:account_update"}
            {/hook}

            {include file="common/image_verification.tpl" option="register" align="left" assign="image_verification"}
            {if $image_verification}
            <div class="ty-control-group">
                {$image_verification nofilter}
            </div>
            {/if}

            <div class="ty-profile-field__buttons buttons-container">
                {include file="buttons/register_profile.tpl" but_name="dispatch[{$dispatch}]"}
            </div>
        </form>
    </div>
    {capture name="mainbox_title"}{__("register_new_account")}{/capture}
{else}

    {capture name="tabsbox"}
        <div class="ty-profile-field ty-account form-wrap" id="content_general">
            <form name="profile_form" enctype="multipart/form-data" action="{""|fn_url}" method="post">
                <input id="selected_section" type="hidden" value="general" name="selected_section"/>
                <input id="default_card_id" type="hidden" value="" name="default_cc"/>
                <input type="hidden" name="profile_id" value="{$user_data.profile_id}" />

                {if $smarty.request.return_url}
                    <input type="hidden" name="return_url" value="{$smarty.request.return_url}" />
                {/if}

                {capture name="group"}
                    {include file="views/profiles/components/profiles_account.tpl"}
                    {include file="views/profiles/components/profile_fields.tpl" section="C" title=__("contact_information")}

                    {if $profile_fields.B || $profile_fields.S}
                        {if $settings.General.user_multiple_profiles == "YesNo::YES"|enum && $runtime.mode == "update"}
                            <p>{__("text_multiprofile_notice")}</p>
                            {include file="views/profiles/components/multiple_profiles.tpl" profile_id=$user_data.profile_id}
                        {/if}

                        {if $settings.Checkout.address_position == "billing_first"}
                            {$first_section = "B"}
                            {$first_section_text = __("billing_address")}
                            {$sec_section = "S"}
                            {$sec_section_text = __("shipping_address")}
                            {$body_id = "sa"}
                        {else}
                            {$first_section = "S"}
                            {$first_section_text = __("shipping_address")}
                            {$sec_section = "B"}
                            {$sec_section_text = __("billing_address")}
                            {$body_id = "ba"}
                        {/if}

                        {include file="views/profiles/components/profile_fields.tpl" section=$first_section body_id="" ship_to_another=true title=$first_section_text}
                        {include file="views/profiles/components/profile_fields.tpl" section=$sec_section body_id=$body_id ship_to_another=true title=$sec_section_text address_flag=$profile_fields|fn_compare_shipping_billing ship_to_another=$ship_to_another}
                    {/if}

                    {hook name="profiles:account_update"}
                    {/hook}

                    {include file="common/image_verification.tpl" option="register" align="center"}

                {/capture}
                {$smarty.capture.group nofilter}

                <div class="ty-profile-field__buttons buttons-container">
                    {if $runtime.mode == "add"}
                        {include file="buttons/register_profile.tpl" but_name="dispatch[{$dispatch}]" but_id="save_profile_but"}
                    {else}
                        {include file="buttons/save.tpl" but_name="dispatch[{$dispatch}]" but_meta="ty-btn__secondary" but_id="save_profile_but"}
                        <input class="ty-profile-field__reset ty-btn ty-btn__tertiary" type="reset" name="reset" value="{__("revert")}" id="shipping_address_reset"/>

                        <script>
                        (function(_, $) {
                            var address_switch = $('input:radio:checked', '.ty-address-switch');
                            $("#shipping_address_reset").on("click", function(e) {
                                setTimeout(function() {
                                    address_switch.click();
                                }, 50);
                            });
                        }(Tygh, Tygh.$));
                        </script>
                        {if $settings.General.show_delete_account_button === "YesNO::YES"|enum && $user_data.user_type === "UserTypes::CUSTOMER"|enum}
                            {capture name="delete_my_account"}
                                {include file="views/profiles/components/user_action_popup.tpl"
                                    action="anonymization_request"
                                    description=__("delete_my_account_description")
                                    title=__("delete_my_account")
                                    id=$block.block_id
                                }
                            {/capture}

                            {include file="common/popupbox.tpl"
                                link_text=__("delete_my_account")
                                title=__("delete_my_account")
                                id="anonymization_request_dialog_`$block.block_id`"
                                content=$smarty.capture.delete_my_account
                                link_meta="ty-ml-s"
                            }
                        {/if}
                    {/if}
                </div>
            </form>
        </div>

        {capture name="additional_tabs"}
            {if $runtime.mode == "update"}
                {if $usergroups && !$user_data|fn_check_user_type_admin_area}
                    <div id="content_usergroups">
                        <table class="ty-table">
                            <thead>
                                <tr>
                                    <th style="width: 30%">{__("usergroup")}</th>
                                    <th style="width: 30%">{__("status")}</th>
                                    {if $settings.General.allow_usergroup_signup == "YesNo::YES"|enum}
                                        <th style="width: 40%">{__("action")}</th>
                                    {/if}
                                </tr>
                            </thead>
                            <tbody>
                            
                                {foreach $usergroups as $usergroup}
                                    {if $user_data.usergroups[$usergroup.usergroup_id]}
                                        {$ug_status = $user_data.usergroups[$usergroup.usergroup_id].status}
                                    {else}
                                        {$ug_status = "F"}
                                    {/if}
                                    {if $settings.General.allow_usergroup_signup == "YesNo::YES"|enum || $settings.General.allow_usergroup_signup != "YesNo::YES"|enum && $ug_status == "A"}
                                        <tr>
                                            <td>{$usergroup.usergroup}</td>
                                            <td>
                                                {if $ug_status == "A"}
                                                    {__("active")}
                                                    {$_link_text = __("remove")}
                                                    {$_req_type = "cancel"}
                                                {elseif $ug_status == "F"}
                                                    {__("available")}
                                                    {$_link_text = __("join")}
                                                    {$_req_type = "join"}
                                                {elseif $ug_status == "D"}
                                                    {__("declined")}
                                                    {$_link_text = __("join")}
                                                    {$_req_type = "join"}
                                                {elseif $ug_status == "P"}
                                                    {__("pending")}
                                                    {$_link_text = __("cancel")}
                                                    {$_req_type = "cancel"}
                                                {/if}
                                            </td>
                                            {if $settings.General.allow_usergroup_signup == "YesNo::YES"|enum}
                                                <td>
                                                    <a class="cm-ajax" data-ca-target-id="content_usergroups" href="{"profiles.usergroups?usergroup_id=`$usergroup.usergroup_id`&type=`$_req_type`"|fn_url}">{$_link_text}</a>
                                                </td>
                                            {/if}
                                        </tr>
                                    {/if}
                                {/foreach}
                            </tbody>
                        </table>
                    <!--content_usergroups--></div>
                {/if}

                {hook name="profiles:tabs"}
                {/hook}
            {/if}
        {/capture}

        {$smarty.capture.additional_tabs nofilter}

    {/capture}

    {if $smarty.capture.additional_tabs|trim != ""}
        {include file="common/tabsbox.tpl" content=$smarty.capture.tabsbox active_tab=$smarty.request.selected_section track=true}
    {else}
        {$smarty.capture.tabsbox nofilter}
    {/if}

    {capture name="mainbox_title"}{__("profile_details")}{/capture}
{/if}
