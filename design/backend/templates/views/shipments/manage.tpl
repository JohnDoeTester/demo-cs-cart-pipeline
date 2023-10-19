{capture name="mainbox"}

<form action="{""|fn_url}" method="post" id="shipments_form" name="manage_shipments_form">

{include file="common/pagination.tpl" save_current_page=true save_current_url=true}

{$c_url = $config.current_url|fn_query_remove:"sort_by":"sort_order"}

{if $shipments}

<div id="shipments_content">
    {capture name="shipments_table"}
        <div class="table-responsive-wrapper longtap-selection">
            <table width="100%" class="table table-middle table--relative table-responsive table--overflow-hidden">
            <thead data-ca-bulkedit-default-object="true">
            <tr>
                <th class="center mobile-hide table__check-items-column">
                    {include file="common/check_items.tpl"
                        check_statuses=$shipment_statuses
                        meta="table__check-items"
                    }

                    <input type="checkbox"
                        class="bulkedit-toggler hide"
                        data-ca-bulkedit-disable="[data-ca-bulkedit-default-object=true]"
                        data-ca-bulkedit-enable="[data-ca-bulkedit-expanded-object=true]"
                    />
                </th>
                <th width="20%">
                    {include file="common/table_col_head.tpl" type="id" text=__("shipment_id")}
                </th>
                <th width="12%">
                    {include file="common/table_col_head.tpl" type="order_id"}
                </th>
                <th width="14%">
                    {include file="common/table_col_head.tpl" type="shipment_date"}
                </th>
                <th width="14%">
                    {include file="common/table_col_head.tpl" type="order_date"}
                </th>
                <th width="22%">
                    {include file="common/table_col_head.tpl" type="customer"}
                </th>
                <th width="8%">&nbsp;</th>
                <th width="10%" class="right">
                    {include file="common/table_col_head.tpl" type="status"}
                </th>
            </tr>
            </thead>
            {foreach from=$shipments item=shipment}
            <tr class="cm-longtap-target cm-row-status-{$shipment.status|lower}"
                data-ca-longtap-action="setCheckBox"
                data-ca-longtap-target="input.cm-item"
                data-ca-id="{$shipment.shipment_id}"
                data-ca-bulkedit-dispatch-parameter="shipment_ids[]"
            >
                <td class="center mobile-hide table__check-items-cell">
                    <input type="checkbox" name="shipment_ids[]" value="{$shipment.shipment_id}" class="cm-item cm-item-status-{$shipment.status|lower} hide" />
                </td>
                <td width="20%" data-th="{__("shipment_id")}" class="table__first-column">
                    <a class="underlined" href="{"shipments.details?shipment_id=`$shipment.shipment_id`"|fn_url}"><span>#{$shipment.shipment_id}</span></a>
                </td>
                <td width="12%" data-th="{__("order_id")}">
                    <a class="underlined" href="{"orders.details?order_id=`$shipment.order_id`"|fn_url}"><span>#{$shipment.order_id}</span></a>
                </td>
                <td width="14%" data-th="{__("shipment_date")}">
                    {if $shipment.shipment_timestamp}{$shipment.shipment_timestamp|date_format:"`$settings.Appearance.date_format`"}{else}--{/if}
                </td>
                <td width="14%" data-th="{__("order_date")}">
                    {if $shipment.order_timestamp}{$shipment.order_timestamp|date_format:"`$settings.Appearance.date_format`"}{else}--{/if}
                </td>
                <td width="22%" data-th="{__("customer")}">
                    {if $shipment.user_id}<a href="{"profiles.update?user_id=`$shipment.user_id`"|fn_url}">{/if}{$shipment.s_lastname} {$shipment.s_firstname}{if $shipment.user_id}</a>{/if}
                    {if $shipment.company}<p class="muted nowrap">{$shipment.company}</p>{/if}
                </td>
                <td width="8%" class="nowrap" data-th="{__("tools")}">

                    <div class="hidden-tools">
                        {assign var="return_current_url" value=$config.current_url|escape:url}
                        {capture name="tools_list"}
                            {hook name="shipments:list_extra_links"}
                                <li>{btn type="list" text=__("view") href="shipments.details?shipment_id=`$shipment.shipment_id`"}</li>
                                <li>{btn type="list" text=__("print_slip") class="cm-new-window" href="shipments.packing_slip?shipment_ids[]=`$shipment.shipment_id`"}</li>
                                <li class="divider"></li>
                                <li>{btn type="list" text=__("delete") class="cm-confirm" href="shipments.delete?shipment_ids[]=`$shipment.shipment_id`&redirect_url=`$return_current_url`" method="POST"}</li>
                            {/hook}
                        {/capture}
                        {dropdown content=$smarty.capture.tools_list}
                    </div>

                </td>
                <td width="10%" class="right" data-th="{__("status")}">
                    {include file="common/select_popup.tpl" id=$shipment.shipment_id status=$shipment.status items_status=$shipment_statuses table="shipments" object_id_name="shipment_id" popup_additional_class="dropleft"}
                </td>

            </tr>
            {/foreach}
            </table>
        </div>
    {/capture}

    {include file="common/context_menu_wrapper.tpl"
        form="manage_shipments_form"
        object="shipments"
        items=$smarty.capture.shipments_table
    }
<!--shipments_content--></div>
{else}
    <p class="no-items">{__("no_data")}</p>
{/if}

{include file="common/pagination.tpl"}
</form>
{/capture}

{capture name="buttons"}
    {capture name="tools_list"}
        {hook name="shipments:list_tools"}
        {/hook}
    {/capture}
    {if $smarty.capture.tools_list|trim}
        {dropdown content=$smarty.capture.tools_list}
    {/if}
{/capture}

{capture name="sidebar"}
    {include file="common/saved_search.tpl" dispatch="shipments.manage" view_type="shipments"}
    {include file="views/shipments/components/shipments_search_form.tpl" dispatch="shipments.manage"}
{/capture}

{capture name="title"}
    {strip}
    {__("shipments")}
    {if $smarty.request.order_id}
        &nbsp;({__("order")}&nbsp;#{$smarty.request.order_id})
    {/if}
    {/strip}
{/capture}
{include file="common/mainbox.tpl" title=$smarty.capture.title content=$smarty.capture.mainbox sidebar=$smarty.capture.sidebar buttons=$smarty.capture.buttons}
