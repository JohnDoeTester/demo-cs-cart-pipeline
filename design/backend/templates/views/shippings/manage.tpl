{capture name="mainbox"}

<form action="{""|fn_url}" method="post" id="shippings_form" name="shippings_form" class="{if ""|fn_check_form_permissions} cm-hide-inputs{/if}">

{$shipping_statuses=""|fn_get_default_statuses:false}
{$has_permission = fn_check_permissions("shippings", "update", "admin", "POST")}
{$has_available_methods = empty($runtime.company_id) || in_array($runtime.company_id, array_column($shippings, 'company_id'))}

{if $shippings}
    <div id="shippings_content">
        {capture name="shippings_table"}
            <div class="table-responsive-wrapper longtap-selection">
                <table width="100%" class="table table-middle table--relative table-responsive">
                <thead
                    data-ca-bulkedit-default-object="true"
                    data-ca-bulkedit-component="defaultObject"
                >
                <tr>
                    <th width="5%" class="mobile-hide">
                        {include file="common/check_items.tpl" 
                            check_statuses=($has_permission) ? $shipping_statuses : ''
                            is_check_disabled=!$has_available_methods
                        }

                        <input type="checkbox"
                            class="bulkedit-toggler hide"
                            data-ca-bulkedit-disable="[data-ca-bulkedit-default-object=true]" 
                            data-ca-bulkedit-enable="[data-ca-bulkedit-expanded-object=true]"
                        />
                    </th>
                    <th width="6%">{__("position_short")}</th>
                    <th>{__("name")}</th>
                    <th width="10%">{__("delivery_time")}</th>
                    <th width="10%">{__("weight_limit")}&nbsp;({$settings.General.weight_symbol})</th>
                    <th width="10%">{__("usergroups")}</th>

                    {hook name="shippings:manage_header"}
                    {/hook}

                    <th>&nbsp;</th>
                    <th width="10%" class="right">{__("status")}</th>
                </tr>
                </thead>
                {foreach from=$shippings item=shipping}

                {assign var="allow_save" value=$shipping|fn_allow_save_object:"shippings"}

                {if $allow_save}
                    {assign var="status_display" value=""}
                    {assign var="link_text" value=__("edit")}
                {else}
                    {assign var="status_display" value="text"}
                    {assign var="link_text" value=__("view")}
                {/if}

                <tr class="cm-row-status-{$shipping.status|lower} cm-longtap-target {if !$allow_save}cm-hide-inputs{else}cm-no-hide-input{/if}"
                    {if $has_permission && $has_available_methods}
                        data-ca-longtap-action="setCheckBox"
                        data-ca-longtap-target="input.cm-item"
                        data-ca-id="{$shipping.shipping_id}"
                    {/if}  
                >
                    <input type="hidden" name="shipping_data[{$shipping.shipping_id}][tax_ids][{$shipping.tax_ids}]" value="{$shipping.tax_ids}" />
                    
                    <td width="5%" class="mobile-hide">
                        <input type="checkbox" name="shipping_ids[]" value="{$shipping.shipping_id}" class="cm-item cm-item-status-{$shipping.status|lower} hide" />
                    </td>
                    <td width="6%" data-th="{__("position_short")}">
                        <input type="text" name="shipping_data[{$shipping.shipping_id}][position]" size="3" value="{$shipping.position}" class="input-micro input-hidden" /></td>
                    <td data-ct-shipping-name="{$shipping.shipping}" data-th="{__("name")}">
                        <a href="{"shippings.update?shipping_id=`$shipping.shipping_id`"|fn_url}">{$shipping.shipping}</a>
                        {include file="views/companies/components/company_name.tpl" object=$shipping}
                    </td>
                    <td width="10%" data-th="{__("delivery_time")}">
                        <input type="text" name="shipping_data[{$shipping.shipping_id}][delivery_time]" size="20" value="{$shipping.delivery_time}" class="input-small input-hidden" /></td>
                    <td width="10%" class="nowrap" data-th="{__("weight_limit")}&nbsp;({$settings.General.weight_symbol})">
                        <input type="text" name="shipping_data[{$shipping.shipping_id}][min_weight]" size="4" value="{$shipping.min_weight}" class="input-mini input-hidden" />&nbsp;-&nbsp;<input type="text" name="shipping_data[{$shipping.shipping_id}][max_weight]" size="4" value="{if $shipping.max_weight != "0.00"}{$shipping.max_weight}{/if}" class="input-mini input-hidden right" /></td>
                    <td width="10%" class="nowrap" data-th="{__("usergroups")}">
                        {include file="common/select_usergroups.tpl"
                            select_mode=true
                            title=__("usergroup")
                            id="ship_data_`$shipping.shipping_id`"
                            name="shipping_data[`$shipping.shipping_id`][usergroup_ids]"
                            usergroups=$usergroups
                            usergroup_ids=$shipping.usergroup_ids
                            input_extra=""
                        }
                    </td>

                    {hook name="shippings:manage_data"}
                    {/hook}

                    <td class="nowrap" data-th="{__("tools")}">
                        {capture name="tools_list"}
                            {hook name="shippings:list_extra_links"}
                                <li>{btn type="list" text=$link_text href="shippings.update?shipping_id=`$shipping.shipping_id`"}</li>
                                {if $allow_save}
                                    <li>{btn type="list" text=__("delete") class="cm-confirm" href="shippings.delete?shipping_id=`$shipping.shipping_id`" method="POST"}</li>
                                {/if}
                            {/hook}
                        {/capture}
                        <div class="hidden-tools">
                            {dropdown content=$smarty.capture.tools_list}
                        </div>
                    </td>
                    <td width="10%" class="right" data-th="{__("status")}">
                        {include file="common/select_popup.tpl" id=$shipping.shipping_id display=$status_display status=$shipping.status hidden="" object_id_name="shipping_id" table="shippings"}
                    </td>
                </tr>
                {/foreach}
                </table>
            </div>
        {/capture}

        {include file="common/context_menu_wrapper.tpl"
            form="shippings_form"
            object="shippings"
            items=$smarty.capture.shippings_table
            has_permissions=$has_permission
        }
    <!--shippings_content--></div>
{else}
    <p class="no-items">{__("no_data")}</p>
{/if}
</form>

{capture name="buttons"}
    {capture name="tools_list"}
        {hook name="shippings:manage_tools_list"}
        {/hook}
    {/capture}
    {dropdown content=$smarty.capture.tools_list}

    {if $shippings}
        {include file="buttons/save.tpl" 
            but_name="dispatch[shippings.m_update]" 
            but_role="submit-button" 
            but_target_form="shippings_form"
        }
    {/if}
{/capture}

{capture name="adv_buttons"}
    {include file="common/tools.tpl" tool_href="shippings.add" prefix="top" hide_tools=true link_text="" title=__("add_shipping_method") icon="icon-plus"}
{/capture}

{/capture}
{include file="common/mainbox.tpl" title=__("manage_shippings") content=$smarty.capture.mainbox buttons=$smarty.capture.buttons adv_buttons=$smarty.capture.adv_buttons select_languages=true select_storefront=true storefront_switcher_param_name="storefront_id" selected_storefront_id=$selected_storefront_id}