{if "MULTIVENDOR"|fn_allowed_for}
    {$no_hide_input="cm-no-hide-input"}
{/if}

{include file="views/profiles/components/profiles_scripts.tpl"}

{capture name="mainbox"}

<form action="{""|fn_url}" method="post" name="userlist_form" id="userlist_form" class="{if $runtime.company_id && !"ULTIMATE"|fn_allowed_for}cm-hide-inputs{/if}">
<input type="hidden" name="fake" value="1" />
<input type="hidden" name="user_type" value="{$smarty.request.user_type}" class="cm-no-hide-input"/>

{include file="common/pagination.tpl" save_current_page=true save_current_url=true div_id=$smarty.request.content_id}

{$c_url=$config.current_url|fn_query_remove:"sort_by":"sort_order"}

{$rev=$smarty.request.content_id|default:"pagination_contents"}

{$person_name_col_width = ($smarty.request.user_type == "UserTypes::CUSTOMER"|enum && $can_view_orders) ? "15%" : "23%"}
{$email_col_width = ($smarty.request.user_type == "UserTypes::CUSTOMER"|enum && $can_view_orders) ? "15%" : "22%"}

{if $users}
    {capture name="profiles_table"}
        <div class="table-responsive-wrapper longtap-selection">
            <table width="100%" class="table table-middle table--relative table-responsive table--overflow-hidden">
            <thead data-ca-bulkedit-default-object="true" data-ca-bulkedit-component="defaultObject">
            <tr>
                <th class="center {$no_hide_input} mobile-hide table__check-items-column">
                {include file="common/check_items.tpl"
                    check_statuses=""|fn_get_default_status_filters:true
                    meta="table__check-items"
                }

                    {if fn_check_view_permissions("orders.manage", "GET")
                        || fn_check_view_permissions("profiles.export_range", "POST")
                        || fn_check_permissions("profiles", "m_delete", "admin", "POST", ["user_type" => $smarty.request.user_type])
                        || (fn_check_permissions("profiles", "m_activate", "admin", "POST", ["user_type" => $smarty.request.user_type])
                        && fn_check_permissions("profiles", "m_disable", "admin", "POST", ["user_type" => $smarty.request.user_type]))
                    }
                        <input type="checkbox"
                            class="bulkedit-toggler hide"
                            data-ca-bulkedit-disable="[data-ca-bulkedit-default-object=true]"
                            data-ca-bulkedit-enable="[data-ca-bulkedit-expanded-object=true]"
                            data-ca-bulkedit-dispatch-parameter="user_ids[]"
                        />
                    {/if}
                </th>
                <th width="10%" class="nowrap">
                    {include file="common/table_col_head.tpl" type="id"}
                </th>
                <th width="{$person_name_col_width}">
                    {include file="common/table_col_head.tpl" type="name" text=__("person_name")}
                </th>
                <th width="{$email_col_width}">
                    {include file="common/table_col_head.tpl" type="email"}
                </th>
                <th width="14%">
                    {include file="common/table_col_head.tpl" type="last_login"}
                </th>
                <th width="15%">
                    {include file="common/table_col_head.tpl" text=__("phone")}
                </th>
                {if !$search.user_type}
                    <th width="14%">
                        {include file="common/table_col_head.tpl" text=__("type")}
                    </th>
                {/if}
                {if $smarty.request.user_type === "UserTypes::CUSTOMER"|enum && $can_view_orders}
                    <th width="17%">
                        {include file="common/table_col_head.tpl" text=__("orders")}
                    </th>
                {/if}
                {hook name="profiles:manage_header"}{/hook}
                <th width="5%" class="right mobile-hide">
                    &nbsp;
                </th>
                <th width="9%" class="right">
                    {include file="common/table_col_head.tpl"
                        type="status"
                        title=__("status")
                    }
                </th>

            </tr>
            </thead>
            {foreach from=$users item=user}

            {$allow_save=$user|fn_allow_save_object:"users"}

            {if !$allow_save && !"RESTRICTED_ADMIN"|defined && $auth.is_root != 'Y'}
                {$link_text=__("view")}
                {$popup_additional_class=""}
            {elseif $allow_save || "RESTRICTED_ADMIN"|defined || $auth.is_root == 'Y'}
                {$link_text=""}
                {$popup_additional_class="cm-no-hide-input"}
            {else}
                {$popup_additional_class=""}
                {$link_text=""}
            {/if}

            <tr class="cm-row-status-{$user.status|lower} cm-longtap-target {if ("ULTIMATE"|fn_allowed_for && (!$allow_save || ($user.user_id == $smarty.session.auth.user_id)))} cm-hide-inputs{/if}"
                data-ca-longtap-action="setCheckBox"
                data-ca-longtap-target="input.cm-item"
                data-ca-id="{$user.user_id}"
            >
                <td class="center {$no_hide_input} mobile-hide table__check-items-cell">
                    <input type="checkbox" name="user_ids[]" value="{$user.user_id}" class="cm-item cm-item-status-{$user.status|lower} hide" /></td>
                <td width="10%" data-th="{__("id")}" class="table__first-column"><a class="row-status" href="{"profiles.update?user_id=`$user.user_id`&user_type=`$user.user_type`"|fn_url}">{$user.user_id}</a></td>
                <td width="{$person_name_col_width}" class="row-status wrap" data-th="{__("person_name")}">
                    {if $user.firstname || $user.lastname}
                        <a href="{"profiles.update?user_id=`$user.user_id`&user_type=`$user.user_type`"|fn_url}">{$user.lastname} {$user.firstname}</a>
                    {else}
                        -
                    {/if}

                    {if $user.user_type === "UserTypes::ADMIN"|enum && $user.is_root === "YesNo::YES"|enum}
                        {if "ULTIMATE"|fn_allowed_for}
                            {if $user.company_id}
                                <p class="muted">
                                    <small>{__("storefront_admin", ["[storefront]" => $user.company_id|fn_get_company_name])}</small>
                                </p>
                            {/if}

                            {if !$user.company_id}
                                <p class="muted">
                                    <small>{__("main_administrator")}</small>
                                </p>
                            {/if}
                        {else}
                            <p class="muted">
                                <small>{__("main_administrator")}</small>
                            </p>
                        {/if}
                    {elseif $user.company_id}
                        {include file="views/companies/components/company_name.tpl" object=$user}
                    {/if}
                </td>
                <td width="{$email_col_width}" data-th="{__("email")}"><a class="row-status" href="mailto:{$user.email|escape:url}">{$user.email}</a></td>
                <td width="14%" class="row-status" data-th="{__("last_login")}">{if $user.last_login}{$user.last_login|date_format:"`$settings.Appearance.date_format`, `$settings.Appearance.time_format`"}{else}{/if}</td>
                <td width="15%" class="row-status" data-th="{__("phone")}">
                    <a href="tel:{$user.phone}"><bdi>{$user.phone}</bdi></a>
                </td>
                {if $smarty.request.user_type == "UserTypes::CUSTOMER"|enum && $can_view_orders}
                    <td width="17%" class="row-status" data-th="{__("orders")}"><a href="{"orders.manage?is_search=Y&user_id=`$user.user_id`"|fn_url}">{$orders_stats[$user.user_id].total_orders|default: 0}</a> / <a href="{"orders.manage?is_search=Y&user_id=`$user.user_id`&{http_build_query(["status" => $settled_statuses|array_values])}"|fn_url}">{$orders_stats[$user.user_id].total_settled_orders|default: 0}</a> / <a href="{"orders.manage?is_search=Y&user_id=`$user.user_id`&{http_build_query(["status" => $settled_statuses|array_values])}"|fn_url}">{$orders_stats[$user.user_id].total_spend|format_price:$currencies.$secondary_currency|default: 0 nofilter}</a></td>
                {/if}
                {if !$search.user_type}
                <td width="14%" class="row-status" data-th="{__("type")}">
                    {if $user.user_type == "A"}{__("administrator")}{elseif $user.user_type == "V"}{__("vendor_administrator")}{elseif $user.user_type == "C"}{__("customer")}{elseif $user.user_type == "P"}{__("affiliate")}{/if}
                </td>
                {/if}
                {hook name="profiles:manage_data"}{/hook}
                <td width="5%" class="right nowrap mobile-hide">
                    {capture name="tools_list"}
                        {$list_extra_links = false}
                        {hook name="profiles:list_extra_links"}
                            {if $user.user_type == "C"}
                                <li>{btn type="list" text=__("view_all_orders") href="orders.manage?user_id=`$user.user_id`"}</li>
                                {$list_extra_links = true}
                            {/if}
                            {if
                                fn_user_need_login($user.user_type)
                                && (
                                    !$runtime.company_id
                                    || fn_check_permission_manage_profiles($user.user_type)
                                )
                                && $user.user_id != $auth.user_id
                                && !(
                                    $user.user_type === $auth.user_type
                                    && $user.is_root === "YesNo::YES"|enum
                                    && (
                                        !$user.company_id
                                        || $user.company_id == $auth.company_id
                                    )
                                )
                            }
                                <li>{btn type="list" target="_blank" text=__("log_in_as_user") href="profiles.act_as_user?user_id=`$user.user_id`"}</li>
                                {$list_extra_links = true}
                            {/if}
                            {hook name="list_extra_links:anonymization"}
                            {$return_current_url=$config.current_url|escape:url}
                                {if $user.user_type === "UserTypes::CUSTOMER"|enum}
                                    <li>{btn type="list" text=__("anonymize") class="cm-confirm" data=["data-ca-confirm-text" => "{__("text_anonymize_question")}"] href="profiles.anonymize?user_id=`$user.user_id`&redirect_url=`$return_current_url`" method="POST"}</li>
                                {/if}
                            {/hook}
                        {/hook}
                        {if $list_extra_links}
                            <li class="divider"></li>
                        {/if}

                        {if $smarty.request.user_type}
                            {$user_edit_link="profiles.update?user_id=`$user.user_id`&user_type=`$smarty.request.user_type`"}
                        {else}
                            {$user_edit_link="profiles.update?user_id=`$user.user_id`&user_type=`$user.user_type`"}
                        {/if}
                        <li>{btn type="list" text=__("edit") href=$user_edit_link}</li>

                        {capture name="tools_delete"}
                            <li>{btn type="list" text=__("delete") class="cm-confirm" href="profiles.delete?user_id=`$user.user_id`&redirect_url=`$return_current_url`" method="POST"}</li>
                        {/capture}
                        {if $user.user_id != $smarty.session.auth.user_id}
                            {if !$runtime.company_id && !($user.user_type == "A" && $user.is_root == "Y")}
                                {$smarty.capture.tools_delete nofilter}
                            {elseif $allow_save}
                                {if "MULTIVENDOR"|fn_allowed_for && $user.user_type == "V" && $user.is_root == "N"}
                                    {$smarty.capture.tools_delete nofilter}
                                {/if}

                                {if "ULTIMATE"|fn_allowed_for}
                                    {$smarty.capture.tools_delete nofilter}
                                {/if}
                            {/if}
                        {/if}
                    {/capture}
                    <div class="hidden-tools">
                        {dropdown content=$smarty.capture.tools_list}
                    </div>
                </td>
                <td width="9%" class="right" data-th="{__("status")}">
                    <input type="hidden" name="user_types[{$user.user_id}]" value="{$user.user_type}" />
                    {if $user.is_root == "Y" && ($user.user_type == "A" || $user.user_type == "V" && $runtime.company_id && $runtime.company_id == $user.company_id)}
                        {$u_id=""}
                    {else}
                        {$u_id=$user.user_id}
                    {/if}

                    {$non_editable=false}

                    {if $user.is_root == "Y" && $user.user_type == $auth.user_type && (!$user.company_id || $user.company_id == $auth.company_id) || $user.user_id == $auth.user_id || ("MULTIVENDOR"|fn_allowed_for && $runtime.company_id && ($user.user_type == 'C' || $user.company_id && $user.company_id != $runtime.company_id))}
                        {$non_editable=true}
                    {/if}

                    {include file="common/select_popup.tpl" id=$u_id status=$user.status hidden="" update_controller="profiles" notify=true notify_text=__("notify_user") popup_additional_class="`$popup_additional_class` dropleft" non_editable=$non_editable}
                </td>
            </tr>
            {/foreach}
            </table>
        </div>
    {/capture}

    {include file="common/context_menu_wrapper.tpl"
        form="userlist_form"
        object="profiles"
        items=$smarty.capture.profiles_table
    }
{else}
    <p class="no-items">{__("no_data")}</p>
{/if}

{include file="common/pagination.tpl" div_id=$smarty.request.content_id}

{capture name="buttons"}
    {if $users}
        {capture name="tools_list"}
        {/capture}
        {dropdown content=$smarty.capture.tools_list class="mobile-hide bulkedit-dropdown--legacy hide"}
    {/if}
{/capture}
</form>
{/capture}

{capture name="adv_buttons"}
    {if $smarty.request.user_type}
        {$_title=$smarty.request.user_type|fn_get_user_type_description:true}
    {else}
        {$_title=__("users")}
    {/if}
    {hook name="profiles:manage_adv_buttons"}
        {if $smarty.request.user_type}
            {if $can_add_user}
                <a class="btn cm-tooltip" href="{"profiles.add?user_type=`$smarty.request.user_type`"|fn_url}" title="{__("add_user")}">
                    {include_ext file="common/icon.tpl" class="icon-plus"}
                </a>
            {/if}
        {/if}
    {/hook}
{/capture}

{capture name="sidebar"}
    {hook name="profiles:manage_sidebar"}
    {include file="common/saved_search.tpl" dispatch="profiles.manage" view_type="users"}
    {include file="views/profiles/components/users_search_form.tpl" dispatch="profiles.manage"}
    {/hook}
{/capture}

{include file="common/mainbox.tpl" title=$_title content=$smarty.capture.mainbox sidebar=$smarty.capture.sidebar adv_buttons=$smarty.capture.adv_buttons buttons=$smarty.capture.buttons content_id="manage_users"}
