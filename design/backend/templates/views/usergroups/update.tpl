{if $usergroup.usergroup_id}
    {assign var="id" value=$usergroup.usergroup_id}
{else}
    {assign var="id" value=0}
{/if}

<div id="content_group{$id}">

<form action="{""|fn_url}" method="post" enctype="multipart/form-data" name="update_usergroups_form_{$id}" class="form-horizontal form-edit ">
<input type="hidden" name="usergroup_id" value="{$id}" />

{capture name="tabsbox"}
{include file="common/subheader.tpl" title=__("general") target="#content_general_{$id}"}
<div id="content_general_{$id}" class="collapse in collapse-visible">
{hook name="usergroups:general_content"}
    <div class="control-group">
        <label class="control-label cm-required" for="elm_usergroup_{$id}">{__("usergroup")}</label>
        <div class="controls">
            <input type="text" id="elm_usergroup_{$id}" name="usergroup_data[usergroup]" size="35" value="{$usergroup.usergroup}" class="input-medium" />
        </div>
    </div>

    <div class="control-group">
        <label class="control-label" for="elm_usergroup_type_{$id}">{__("type")}</label>
        <div class="controls">
            {if $id}
                <input type="hidden" name="usergroup_data[type]" value="{$usergroup.type}"/>
                <div class="controls-text">
                    {$usergroup_types[$usergroup.type]}
                </div>
            {else}
                <select id="elm_usergroup_type_{$id}" name="usergroup_data[type]">
                    {foreach $usergroup_types as $type_code => $type_name}
                        <option value="{$type_code}">{$type_name}</option>
                    {/foreach}
                </select>
            {/if}
        </div>
    </div>
    {include file="common/select_status.tpl" input_name="usergroup_data[status]" id="usergroup_data_`$id`" obj=$usergroup hidden=true}
    {if $show_privileges_tab}
    <hr/>
    {/if}
{/hook}
</div>
{include file="views/usergroups/components/get_privileges.tpl" grouped_privileges=$grouped_privileges}

{hook name="usergroups:tabs_content"}{/hook}
{/capture}
{if $navigation.tabs|count === 0}
    {$navigation.tabs = []}
{/if}
{include file="common/tabsbox.tpl" content=$smarty.capture.tabsbox navigation=$navigation}

<div class="buttons-container">
{include file="buttons/save_cancel.tpl" but_name="dispatch[usergroups.update]" cancel_action="close" save=$id}
</div>

</form>
<!--content_group{$id}--></div>