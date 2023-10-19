{capture name="mainbox"}

<form action="{""|fn_url}" method="post" target="" enctype="multipart/form-data" name="rma_list_form" id="rma_list_form">

{include file="common/pagination.tpl"}

{$c_url = $config.current_url|fn_query_remove:"sort_by":"sort_order"}
{$has_permissions = fn_check_permissions("rma", "delete", "admin", "POST")}
{include_ext file="common/icon.tpl" class="icon-`$search.sort_order_rev`" assign=c_icon}
{include_ext file="common/icon.tpl" class="icon-dummy" assign=c_dummy}

{if $return_requests}
    {capture name="rma_list_table"}
        <div class="table-responsive-wrapper longtap-selection">
            <table width="100%" class="table table-middle table--relative table-responsive">
            <thead
                data-ca-bulkedit-default-object="true"
                data-ca-bulkedit-component="defaultObject"
            >
                <tr>
                    <th width="6%" class="left mobile-hide">
                        {if $has_permissions}
                            {include file="common/check_items.tpl"}

                            <input type="checkbox"
                                class="bulkedit-toggler hide"
                                data-ca-bulkedit-disable="[data-ca-bulkedit-default-object=true]"
                                data-ca-bulkedit-enable="[data-ca-bulkedit-expanded-object=true]"
                            />
                        {/if}
                    </th>
                    <th width="5%" class="nowrap"><a class="cm-ajax" href="{"`$c_url`&sort_by=return_id&sort_order=`$search.sort_order_rev`"|fn_url}" data-ca-target-id="pagination_contents">{__("id")}{if $search.sort_by === "return_id"}{$c_icon nofilter}{else}{$c_dummy nofilter}{/if}</a></th>
                    <th width="15%"><a class="cm-ajax" href="{"`$c_url`&sort_by=status&sort_order=`$search.sort_order_rev`"|fn_url}" data-ca-target-id="pagination_contents">{__("status")}{if $search.sort_by === "status"}{$c_icon nofilter}{else}{$c_dummy nofilter}{/if}</a></th>
                    <th width="25%"><a class="cm-ajax" href="{"`$c_url`&sort_by=customer&sort_order=`$search.sort_order_rev`"|fn_url}" data-ca-target-id="pagination_contents">{__("customer")}{if $search.sort_by === "customer"}{$c_icon nofilter}{else}{$c_dummy nofilter}{/if}</a></th>
                    <th width="15%"><a class="cm-ajax" href="{"`$c_url`&sort_by=timestamp&sort_order=`$search.sort_order_rev`"|fn_url}" data-ca-target-id="pagination_contents">{__("date")}{if $search.sort_by === "timestamp"}{$c_icon nofilter}{else}{$c_dummy nofilter}{/if}</a></th>
                    <th width="15%"><a class="cm-ajax" href="{"`$c_url`&sort_by=action&sort_order=`$search.sort_order_rev`"|fn_url}" data-ca-target-id="pagination_contents">{__("action")}{if $search.sort_by === "action"}{$c_icon nofilter}{else}{$c_dummy nofilter}{/if}</a></th>
                    <th width="10%" class="center"><a class="cm-ajax" href="{"`$c_url`&sort_by=order_id&sort_order=`$search.sort_order_rev`"|fn_url}" data-ca-target-id="pagination_contents">{__("order")}&nbsp;{__("id")}{if $search.sort_by === "order_id"}{$c_icon nofilter}{else}{$c_dummy nofilter}{/if}</a></th>
                    <th width="10%" class="center"><a class="cm-ajax" href="{"`$c_url`&sort_by=amount&sort_order=`$search.sort_order_rev`"|fn_url}" data-ca-target-id="pagination_contents">{__("quantity")}{if $search.sort_by === "amount"}{$c_icon nofilter}{else}{$c_dummy nofilter}{/if}</a></th>
                    <th>&nbsp;</th>
                </tr>
            </thead>
            {foreach $return_requests as $request}
                <tr class="cm-longtap-target"
                    data-ca-longtap-action="setCheckBox"
                    data-ca-longtap-target="input.cm-item"
                    data-ca-id="{$request.return_id}"
                >
                    <td width="6%" class="left mobile-hide">
                        <input type="checkbox" name="return_ids[]" value="{$request.return_id}" class="cm-item cm-item-status-{$request.status|lower} hide" /></td>
                    <td data-th="{__("id")}"><a href="{"rma.details?return_id=`$request.return_id`"|fn_url}" class="underlined">#{$request.return_id}</a></td>
                    <td data-th="{__("status")}">
                        {include file="common/status.tpl" status=$request.status display="view" name="return_statuses[`$request.return_id`]" status_type=$smarty.const.STATUSES_RETURN}
                    </td>
                    <td data-th="{__("customer")}">{$request.firstname} {$request.lastname}</td>
                    <td data-th="{__("date")}"><a href="{"rma.details?return_id=`$request.return_id`"|fn_url}" class="underlined">{$request.timestamp|date_format:"`$settings.Appearance.date_format`, `$settings.Appearance.time_format`"}</a></td>
                    <td data-th="{__("action")}">{$request.action}</td>
                    <td class="center" data-th="{__("order")}&nbsp;{__("id")}"><a href="{"orders.details?order_id=`$request.order_id`"|fn_url}" class="underlined">{$request.order_id}</a></td>
                    <td class="center" data-th="{__("quantity")}">{$request.total_amount}</td>
                    <td class="nowrap" data-th="{__("tools")}">
                        {capture name="tools_list"}
                            <li>{btn type="list" text=__("edit") href="rma.details?return_id=`$request.return_id`"}</li>
                            <li>{btn type="list" class="cm-confirm" text=__("delete") href="rma.delete?return_id=`$request.return_id`" method="POST"}</li>
                        {/capture}
                        <div class="hidden-tools">
                            {dropdown content=$smarty.capture.tools_list}
                        </div>
                    </td>
                </tr>
            {/foreach}
            </table>
        </div>
    {/capture}

    {include file="common/context_menu_wrapper.tpl"
        form="rma_list_form"
        object="rma_returns"
        items=$smarty.capture.rma_list_table
        is_check_all_shown=true
    }
{else}
    <p class="no-items">{__("no_data")}</p>
{/if}

{include file="common/pagination.tpl"}

{capture name="buttons"}
    {capture name="tools_list"}
        <li>{btn type="list" text=__("rma_reasons") href="rma.properties?property_type=R"}</li>
        <li>{btn type="list" text=__("rma_actions") href="rma.properties?property_type=A"}</li>
        <li>{btn type="list" text=__("rma_request_statuses") href="statuses.manage?type=R"}</li>
    {/capture}
    {dropdown content=$smarty.capture.tools_list}
{/capture}
</form>

{capture name="sidebar"}
    {include file="common/saved_search.tpl" dispatch="rma.returns" view_type="rma"}
    {include file="addons/rma/views/rma/components/rma_search_form.tpl" dispatch="rma.returns"}
{/capture}

{/capture}
{include file="common/mainbox.tpl" title=__("return_requests") content=$smarty.capture.mainbox buttons=$smarty.capture.buttons sidebar=$smarty.capture.sidebar}
