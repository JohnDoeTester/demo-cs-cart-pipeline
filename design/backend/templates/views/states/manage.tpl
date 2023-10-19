{capture name="mainbox"}

<form action="{""|fn_url}" method="post" id="states_form" name="states_form" class="{if ""|fn_check_form_permissions} cm-hide-inputs{/if}">
<input type="hidden" name="country_code" value="{$search.country}" />
{include file="common/pagination.tpl" save_current_page=true save_current_url=true}

{$state_statuses=""|fn_get_default_statuses:false}
{$has_permission = fn_check_permissions("states", "update_status", "admin", "POST", ["table" => "states"]) && fn_check_permissions("states", "m_delete", "admin", "POST", ["table" => "states"])}
{$has_permisson_add = fn_check_permissions("states", "update", "admin", "POST")}

{if $states}
    {capture name="states_table"}
        <div class="table-responsive-wrapper longtap-selection">
            <table width="100%" class="table table-middle table--relative table-responsive state-table{if !$has_permission} cm-hide-inputs{/if}">
            <thead 
                data-ca-bulkedit-default-object="true"
                data-ca-bulkedit-component="defaultObject"
            >
            <tr>
                <th width="6%" class="mobile-hide">
                    {include file="common/check_items.tpl" check_statuses=($has_permission) ? $state_statuses : []}

                    <input type="checkbox"
                        class="bulkedit-toggler hide"
                        data-ca-bulkedit-disable="[data-ca-bulkedit-default-object=true]" 
                        data-ca-bulkedit-enable="[data-ca-bulkedit-expanded-object=true]"
                    />
                </th>
                <th width="10%">{__("code")}</th>
                <th>{__("state")}</th>
                <th width="5%">&nbsp;</th>
                <th class="right" width="10%">{__("status")}</th>
            </tr>
            </thead>
            {foreach from=$states item=state}
            <tr class="cm-row-status-{$state.status|lower} cm-longtap-target"
                {if $has_permission}
                    data-ca-longtap-action="setCheckBox"
                    data-ca-longtap-target="input.cm-item"
                    data-ca-id="{$state.state_id}"
                {/if}
            >
                <td width="6%" class="mobile-hide">
                    <input type="checkbox" name="state_ids[]" value="{$state.state_id}" class="cm-item cm-item-status-{$state.status|lower} hide" /></td>
                <td width="10%" class="left nowrap row-status" data-th="{__("code")}">
                    <span>{$state.code}</span>
                </td>
                <td data-th="{__("state")}">
                    <input type="text" name="states[{$state.state_id}][state]" size="55" value="{$state.state}" class="input-hidden"/>
                </td>
                <td width="5%" class="nowrap" data-th="{__("tools")}">
                    {capture name="tools_list"}
                        <li>{btn type="list" class="cm-confirm" text=__("delete") href="states.delete?state_id=`$state.state_id`&country_code=`$search.country`" method="POST"}</li>
                    {/capture}
                    <div class="hidden-tools">
                        {dropdown content=$smarty.capture.tools_list}
                    </div>
                </td>
                <td width="10%" class="right" data-th="{__("status")}">
                    {$has_permission_update_status = fn_check_permissions("tools", "update_status", "admin", "GET", ["table" => "states"])}
                    {include file="common/select_popup.tpl" id=$state.state_id status=$state.status hidden="" object_id_name="state_id" table="states" non_editable=!$has_permission_update_status}
                </td>
            </tr>
            {/foreach}
            </table>
        </div>
    {/capture}

    {include file="common/context_menu_wrapper.tpl"
        form="states_form"
        object="states"
        items=$smarty.capture.states_table
        has_permission=$has_permission
    }
{else}
    <p class="no-items">{__("no_data")}</p>
{/if}

{include file="common/pagination.tpl"}

</form>

{capture name="tools"}
    {capture name="add_new_picker"}

    <form action="{""|fn_url}" method="post" name="add_states_form" class="form-horizontal form-edit">
    <input type="hidden" name="state_data[country_code]" value="{$search.country_code}" />
    <input type="hidden" name="country_code" value="{$search.country_code}" />
    <input type="hidden" name="state_id" value="0" />

    {foreach from=$countries item="country" key="code"}
        {if $code == $search.country_code}
            {$title="{__("new_states")} (`$country`)"}
        {/if}
    {/foreach}

    <div class="cm-j-tabs">
        <ul class="nav nav-tabs">
            <li id="tab_new_states" class="cm-js active"><a>{__("general")}</a></li>
        </ul>
    </div>

    <div class="cm-tabs-content">
    <fieldset>
        <div class="control-group">
            <label class="cm-required control-label" for="elm_state_code">{__("code")}:</label>
            <div class="controls">
            <input type="text" id="elm_state_code" name="state_data[code]" size="8" value="" />
            </div>
        </div>

        <div class="control-group">
            <label class="control-label" for="elm_state_name">{__("state")}:</label>
            <div class="controls">
            <input type="text" id="elm_state_name" name="state_data[state]" size="55" value="" />
            </div>
        </div>

        {include file="common/select_status.tpl" input_name="state_data[status]" id="elm_state_status"}
    </fieldset>
    </div>

    <div class="buttons-container">
        {include file="buttons/save_cancel.tpl" create=true but_name="dispatch[states.update]" cancel_action="close"}
    </div>

</form>

{/capture}
{/capture}

{capture name="buttons"}
    {capture name="tools_list"}
        {hook name="states:manage_tools_list"}
        {/hook}
    {/capture}
    {dropdown content=$smarty.capture.tools_list}

    {if $states}
        {include file="buttons/save.tpl" but_name="dispatch[states.m_update]" but_role="action" but_target_form="states_form" but_meta="cm-submit"}
    {/if}
{/capture}

{capture name="adv_buttons"}
    {include file="common/popupbox.tpl" id="new_state" action="states.add" text=$title content=$smarty.capture.add_new_picker title=__("add_state") act="general" icon="icon-plus"}
{/capture}

{capture name="sidebar"}
<div class="sidebar-row">
<h6>{__("admin_search_title")}</h6>
<form action="{""|fn_url}" name="states_filter_form" method="get">
<div class="sidebar-field">
    <label>{__("country")}:</label>
        <select name="country_code">
            {foreach from=$countries item="country" key="code"}
                <option {if $code == $search.country_code}selected="selected"{/if} value="{$code}">{$country}</option>
            {/foreach}
        </select>
</div>
    {include file="buttons/search.tpl" but_name="dispatch[states.manage]" method="GET"}
</form>
</div>
{/capture}


{/capture}
{include file="common/mainbox.tpl" 
    title=__("states") 
    content=$smarty.capture.mainbox 
    adv_buttons=($has_permisson_add) ? $smarty.capture.adv_buttons : ""
    buttons=$smarty.capture.buttons 
    sidebar=$smarty.capture.sidebar 
    select_languages=true
}