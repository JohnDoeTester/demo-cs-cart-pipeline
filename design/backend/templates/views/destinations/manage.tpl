{capture name="mainbox"}

<form action="{""|fn_url}" method="post" id="destinations_form" name="destinations_form" class="{if ""|fn_check_form_permissions}cm-hide-inputs{/if}">

<input type="hidden" name="redirect_url" value="{$config.current_url}">
{$destination_statuses=""|fn_get_default_statuses:false}
{$has_permission = fn_check_permissions("destinations", "update_status", "admin", "POST", ["table" => "destinations"]) && fn_check_permissions("destinations", "m_delete", "admin", "POST", ["table" => "destinations"])}

    {if $destinations}
        <div class="table-responsive-wrapper longtap-selection" id="destination_contents">
            {capture name="destinations_table"}
                <table width="100%" class="table table-middle table--relative table-responsive">
                <thead 
                    data-ca-bulkedit-default-object="true"
                    data-ca-bulkedit-component="defaultObject"
                >
                <tr>
                    {hook name="destinations:destinations_list_header"}
                        <th width="6%">
                            {include file="common/check_items.tpl" check_statuses=($has_permission) ? ($destination_statuses) : ""}

                            <input type="checkbox"
                                class="bulkedit-toggler hide"
                                data-ca-bulkedit-disable="[data-ca-bulkedit-default-object=true]" 
                                data-ca-bulkedit-enable="[data-ca-bulkedit-expanded-object=true]"
                            />
                        </th>
                        <th>{__("name")}</th>
                    {/hook}
                    <th width="5%">&nbsp;</th>
                    <th class="right" width="10%">{__("status")}</th>
                </tr>
                </thead>
                {foreach from=$destinations item=destination}
                <tr class="cm-row-status-{$destination.status|lower} cm-longtap-target" 
                    {if $has_permission}
                        data-ct-destination-id="{$destination.destination_id}"
                        data-ca-longtap-action="setCheckBox"
                        data-ca-longtap-target="input.cm-item"
                        data-ca-id="{$destination.destination_id}"
                    {/if}
                >
                    {hook name="destinations:destinations_list"}
                        <td width="6%" class="left" data-th="&nbsp;">
                            <input name="destination_ids[]"
                                type="checkbox"
                                value="{$destination.destination_id}"
                                class="cm-item hide cm-item-status-{$destination.status|lower}"
                            />
                        </td>
                        <td data-ct-destination-name="{$destination.destination}" data-th="{__("name")}">
                        <a class="row-status"
                            href="{"destinations.update?destination_id=`$destination.destination_id`"|fn_url}"
                        >{$destination.destination}</a>
                        </td>
                    {/hook}
                    <td width="5%" class="nowrap" data-th="&nbsp;">
                        {capture name="tools_list"}
                            {hook name="destinations:manage_tools_list"}
                                <li>{btn type="list" text=__("edit") href="destinations.update?destination_id=`$destination.destination_id`"}</li>
                                {if $destination.destination_id != 1}
                                    <li>{btn type="list" text=__("delete") class="cm-confirm" href="destinations.delete?destination_id=`$destination.destination_id`" method="POST"}</li>
                                {/if}
                            {/hook}
                        {/capture}
                        <div class="hidden-tools">
                        {dropdown content=$smarty.capture.tools_list}
                        </div>
                    </td>
                    <td class="right" width="10%" data-th="{__("status")}">
                        {$has_permission_update_status = fn_check_permissions("tools", "update_status", "admin", "GET", ["table" => "destinations"])}

                        {include file="common/select_popup.tpl" id=$destination.destination_id status=$destination.status hidden="" object_id_name="destination_id" table="destinations" non_editable=!$has_permission_update_status}
                    </td>
                </tr>
                {/foreach}
                </table>
            {/capture}

            {include file="common/context_menu_wrapper.tpl"
                form="destinations_form"
                object="destinations"
                items=$smarty.capture.destinations_table
                has_permission=$has_permission
            }
        <!--destination_contents--></div>
    {else}
        <p class="no-items">{__("no_items")}</p>
    {/if}
</form>
{/capture}

{capture name="buttons"}
    {if $destinations}
        {capture name="tools_list"}
            {hook name="destinations:action_buttons"}
            {/hook}
        {/capture}
        {dropdown content=$smarty.capture.tools_list}
    {/if}
{/capture}

{capture name="adv_buttons"}
    {include file="common/tools.tpl" tool_href="destinations.add" prefix="top" hide_tools="true" title=__("add_rate_area") icon="icon-plus"}
{/capture}

{include file="common/mainbox.tpl" title=__("rate_areas") content=$smarty.capture.mainbox adv_buttons=$smarty.capture.adv_buttons buttons=$smarty.capture.buttons select_languages=true}
