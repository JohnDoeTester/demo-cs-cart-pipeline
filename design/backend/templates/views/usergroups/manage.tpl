{script src="js/tygh/tabs.js"}

{capture name="mainbox"}

{$hide_inputs = ""|fn_check_form_permissions}
<form action="{""|fn_url}" method="post" id="usergroups_form" name="usergroups_form" class="{if $hide_inputs} cm-hide-inputs{/if}">

{$user_group_statuses=""|fn_get_default_statuses:true}
{$has_permission = fn_check_permissions("usergroups", "update", "admin", "POST", ["table" => "states"]) && fn_check_permissions("states", "m_delete", "admin", "POST", ["table" => "states"])}

{hook name="usergroups:manage"}
{if $usergroups}
    {capture name="usergroups_table"}
        <div class="table-responsive-wrapper longtap-selection">
            <table class="table table-middle table--relative table-responsive table--overflow-hidden">
            <thead
                data-ca-bulkedit-default-object="true"
                data-ca-bulkedit-component="defaultObject"
            >
            <tr>
                <th class="mobile-hide table__check-items-column">
                    {include file="common/check_items.tpl"
                        check_statuses=($has_permission) ? $user_group_statuses : ''
                        meta="table__check-items"
                    }

                    <input type="checkbox"
                        class="bulkedit-toggler hide"
                        data-ca-bulkedit-disable="[data-ca-bulkedit-default-object=true]" 
                        data-ca-bulkedit-enable="[data-ca-bulkedit-expanded-object=true]"
                    />
                </th>
                <th width="37%">
                    {include file="common/table_col_head.tpl" text=__("usergroup")}
                </th>
                <th width="45%">
                    {include file="common/table_col_head.tpl" text=__("type")}
                </th>
                {hook name="usergroups:manage_header"}{/hook}
                <th width="8%" class="mobile-hide">
                    {include file="common/table_col_head.tpl" text="&nbsp;"}
                </th>
                <th width="10%" class="right">
                    {include file="common/table_col_head.tpl" text=__("status")}
                </th>
            </tr>
            </thead>
            {foreach from=$usergroups item=usergroup}
            <tr class="cm-row-status-{$usergroup.status|lower} cm-longtap-target"
                {if $has_permission}
                    data-ca-longtap-action="setCheckBox"
                    data-ca-longtap-target="input.cm-item"
                    data-ca-id="{$state.state_id}"
                {/if}
            >
                <td class="mobile-hide table__check-items-cell">
                    <input type="checkbox" name="usergroup_ids[]" value="{$usergroup.usergroup_id}" class="cm-item cm-item-status-{$usergroup.status|lower} hide" />
                </td>
                <td width="37%" class="row-status" data-th="{__("usergroup")}">
                    {if $hide_inputs}
                        {$usergroup.usergroup}
                    {else}
                        <a class="row-status cm-external-click bulkedit-deselect" data-ca-external-click-id="{"opener_group`$usergroup.usergroup_id`"}">{$usergroup.usergroup}</a>
                    {/if}
                </td>
                <td width="45%" class="row-status" data-th="{__("type")}">
                    {$usergroup_types[$usergroup.type]}
                </td>

                {hook name="usergroups:manage_data"}{/hook}

                <td width="8%" class="row-status mobile-hide">
                    {if $usergroup.type == "A"}
                        {assign var="_href" value="usergroups.assign_privileges?usergroup_id=`$usergroup.usergroup_id`"}
                        {assign var="_link_text" value=__("privileges")}
                    {else}
                        {assign var="_href" value=""}
                        {assign var="_link_text" value=""}
                    {/if}
                    {capture name="tools_list"}
                        {hook name="usergroups:list_extra_links"}
                            <li>{include file="common/popupbox.tpl" id="group`$usergroup.usergroup_id`" text=$usergroup.usergroup link_text=__("edit") act="link" href="usergroups.update?usergroup_id=`$usergroup.usergroup_id`&group_type=`$usergroup.type`"}</li>
                            <li>{btn type="list" text=__("delete") class="cm-confirm" href="usergroups.delete?usergroup_id=`$usergroup.usergroup_id`" method="POST"}</li>
                        {/hook}
                    {/capture}
                    <div class="hidden-tools cm-hide-with-inputs">
                        {dropdown content=$smarty.capture.tools_list}
                    </div>
                </td>
                <td width="10%" class="nowrap right" data-th="{__("status")}">
                    {assign var="hide_for_vendor" value=false}
                    {if !"usergroups.manage"|fn_check_view_permissions:"POST"}
                        {assign var="hide_for_vendor" value=true}
                    {/if}
                    {include file="common/select_popup.tpl" id=$usergroup.usergroup_id status=$usergroup.status hidden=true object_id_name="usergroup_id" table="usergroups" hide_for_vendor=$hide_for_vendor}
                </td>
            </tr>
            {/foreach}
            </table>
        </div>
    {/capture}

    {include file="common/context_menu_wrapper.tpl"
        form="usergroups_form"
        object="usergroups"
        items=$smarty.capture.usergroups_table
        has_permissions=$has_permission
    }
{else}
    <p class="no-items">{__("no_items")}</p>
{/if}
{/hook}

</form>

{capture name="buttons"}
    {if "usergroups.update"|fn_check_view_permissions}
        {capture name="tools_list"}
            {hook name="usergroups:manage_tools_list"}
                <li>{btn type="list" text=__("user_group_requests") href="usergroups.requests"}</li>
            {/hook}
        {/capture}
        {dropdown content=$smarty.capture.tools_list}
    {/if}
{/capture}

{capture name="adv_buttons"}
    {if "usergroups.update"|fn_check_view_permissions}
        {capture name="add_new_picker"}
            {include file="views/usergroups/update.tpl" usergroup=[] show_privileges_tab=isset($usergroup_types["UsergroupTypes::TYPE_ADMIN"|enum])}
        {/capture}
        {include file="common/popupbox.tpl" id="add_new_usergroups" text=__("new_usergroups") title=__("new_usergroups") content=$smarty.capture.add_new_picker act="general" icon="icon-plus"}
    {/if}
{/capture}

{/capture}
{include file="common/mainbox.tpl" title=__("usergroups") content=$smarty.capture.mainbox buttons=$smarty.capture.buttons adv_buttons=$smarty.capture.adv_buttons select_languages=true}