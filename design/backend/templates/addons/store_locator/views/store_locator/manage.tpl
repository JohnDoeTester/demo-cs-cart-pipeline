{script src="js/addons/store_locator/bulk_edit.js"}

{capture name="mainbox"}

<form action="{""|fn_url}" method="post" name="store_locator_form" id="store_locator_form">
<input type="hidden" name="fake" value="1" />

{include file="common/pagination.tpl" save_current_page=true}

<div class="items-container" id="store_locations">
    {if $store_locations}
        {capture name="store_locator_table"}
            <div class="table-responsive-wrapper longtap-selection">
                <table class="table table-middle table--relative table-responsive">

                <thead
                        data-ca-bulkedit-default-object="true"
                        data-ca-bulkedit-component="defaultObject"
                >
                <tr>
                    {hook name="store_locator:stores_list_header"}
                    <th width="1%" class="mobile-hide">
                        {include file="common/check_items.tpl" class="cm-no-hide-input"}

                        <input type="checkbox"
                               class="bulkedit-toggler hide"
                               data-ca-bulkedit-disable="[data-ca-bulkedit-default-object=true]"
                               data-ca-bulkedit-enable="[data-ca-bulkedit-expanded-object=true]"
                        />
                    </th>
                    <th width="1%">{__("position_short")}</th>
                    <th width="20%">{__("store_locator")}</th>
                    <th width="20%">{__("city")}</th>
                    <th width="20%">{__("rate_area")}</th>
                    {/hook}
                    <th width="5%">&nbsp;</th>
                    <th class="right" width="10%">{__("status")}</th>
                </tr>
                </thead>

                    {foreach $store_locations as $loc}
                    <tbody>
                    <tr class="cm-row-status-{$loc.status|lower} cm-longtap-target" valign="top"
                        data-ca-longtap-action="setCheckBox"
                        data-ca-longtap-target="input.cm-item"
                        data-ca-id="{$loc.store_location_id}"
                    >

                        {hook name="store_locator:stores_list"}
                        {assign var="allow_save" value=$loc|fn_allow_save_object:"store_locations"}
                        {if $allow_save}
                            {assign var="no_hide_input" value="cm-no-hide-input"}
                            {assign var="display" value=""}
                        {else}
                            {assign var="no_hide_input" value=""}
                            {assign var="display" value="text"}
                        {/if}

                        <td width="1%" class="left {$no_hide_input} mobile-hide">
                            <input type="checkbox" name="store_locator_ids[]" value="{$loc.store_location_id}" class="cm-item cm-item-status-{$loc.status|lower} hide" />
                        </td>
                        <td width="1%" data-th="{__("position_short")}">
                            <input type="text" name="store_locators[{$loc.store_location_id}][position]" size="3" value="{$loc.position}" class="input-micro input-hidden" />
                        </td>
                        <td width="20%" data-th="{__("store_locator")}">
                            <a class="row-status" href="{"store_locator.update?store_location_id=`$loc.store_location_id`"|fn_url}">{$loc.name}</a>
                            {include file="views/companies/components/company_name.tpl" object=$loc}
                        </td>

                        <td width="20%" data-th="{__("city")}">
                            <span class="row-status">{$loc.city}</span>
                        </td>

                        <td width="20%" data-th="{__("rate_area")}">
                            {if $loc.main_destination_id}
                                <input type="hidden" name="store_locators[{$loc.store_location_id}][main_destination_id]" value="{$loc.main_destination_id}"/>
                                {if fn_check_view_permissions("destinations.update")}
                                    <a href="{"destinations.update&destination_id={$loc.main_destination_id}"|fn_url}"
                                       class="row-status {if $destinations[$loc.main_destination_id]['status'] === "ObjectStatuses::DISABLED"|enum}shipping-rate-area-d{/if}"
                                    >{$destinations[$loc.main_destination_id]["destination"]}</a>
                                {else}
                                    <span class="row-status {if $destinations[$loc.main_destination_id]['status'] === "ObjectStatuses::DISABLED"|enum}shipping-rate-area-d{/if}">
                                        {$destinations[$loc.main_destination_id]["destination"]}
                                    </span>
                                {/if}
                            {else}
                                <span class="row-status">
                                    {__("store_locator.no_rate_area")}
                                </span>
                            {/if}
                            {if $loc.pickup_destinations_ids}
                                <input type="hidden" name="store_locators[{$loc.store_location_id}][pickup_destinations_ids]" value="{$loc.pickup_destinations_ids}"/>
                            {/if}
                        </td>
                        {/hook}

                        <td width="5%" class="center nowrap" data-th="{__("tools")}">
                            {capture name="tools_list"}
                                {if $allow_save}
                                    <li>{btn type="list" text=__("edit") href="store_locator.update?store_location_id=`$loc.store_location_id`"}</li>
                                    <li>{btn type="list" class="cm-confirm" text=__("delete") href="store_locator.delete?store_location_id=`$loc.store_location_id`" method="POST"}</li>
                                {/if}
                            {/capture}
                            <div class="hidden-tools right">
                                {dropdown content=$smarty.capture.tools_list}
                            </div>
                        </td>
                        <td width="10%" class="right nowrap" data-th="{__("status")}">
                            {include file="common/select_popup.tpl" id=$loc.store_location_id status=$loc.status hidden="" object_id_name="store_location_id" table="store_locations" popup_additional_class="`$no_hide_input`" display=$display}
                        </td>

                    </tr>
                    </tbody>
                    {/foreach}
                </table>
            </div>
        {/capture}

        {include file="common/context_menu_wrapper.tpl"
            form="store_locator_form"
            object="store_locator"
            items=$smarty.capture.store_locator_table
        }
    {else}
        <p class="no-items">{__("no_data")}</p>
    {/if}
<!--store_locations--></div>


    {include file="common/pagination.tpl" save_current_page=true}
</form>
    {capture name="adv_buttons"}
        {include file="common/tools.tpl" tool_href="store_locator.add" prefix="top" title=__("add_store_location") hide_tools=true}
    {/capture}


{/capture}
    {capture name="sidebar"}
        {hook name="store_locator:manage_sidebar"}
            {include file="addons/store_locator/components/search_form.tpl"
            dispatch="store_locator.manage"
            search=$search
            }
        {/hook}
    {/capture}

    {capture name="buttons"}
        {if $store_locations}
            {include file="buttons/save.tpl" 
                but_name="dispatch[store_locator.m_update]" 
                but_role="submit-button" 
                but_target_form="store_locator_form"
                but_meta="bulkedit-disable-save-button"
            }
        {/if}
    {/capture}
{include file="common/mainbox.tpl" title=__("store_locator") content=$smarty.capture.mainbox adv_buttons=$smarty.capture.adv_buttons select_languages=true buttons=$smarty.capture.buttons sidebar=$smarty.capture.sidebar}
