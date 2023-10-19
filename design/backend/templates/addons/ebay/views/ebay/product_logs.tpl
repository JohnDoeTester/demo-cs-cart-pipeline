{capture name="mainbox"}

{capture name="sidebar"}
    {include file="common/saved_search.tpl" dispatch="ebay.product_logs" view_type="ebay_product_logs"}
    {include file="addons/ebay/views/ebay/components/search_form.tpl"}
{/capture}

{include file="common/pagination.tpl" save_current_page=true save_current_url=true}

{assign var="return_current_url" value=$config.current_url|escape:url}
{assign var="c_url" value=$config.current_url|fn_query_remove:"sort_by":"sort_order"}
{include_ext file="common/icon.tpl" class="icon-`$search.sort_order_rev`" assign=c_icon}
{include_ext file="common/icon.tpl" class="icon-dummy" assign=c_dummy}

{if $logs}
<div class="table-responsive-wrapper">
    <table width="100%" class="table table-middle table--relative table-responsive">
    <thead>
    <tr>
        <th>
            <a class="cm-ajax" href="{"`$c_url`&sort_by=datetime&sort_order=`$search.sort_order_rev`"|fn_url}" data-ca-target-id="pagination_contents">{__("time")} / {__("type")}{if $search.sort_by === "datetime"}{$c_icon nofilter}{else}{$c_dummy nofilter}{/if}</a>
        </th>
        <th>{__("action")}</th>
        <th>{__("product_name")}</th>
        <th>{__("description")}</th>
    </tr>
    </thead>
    {foreach from=$logs item=log}
    <tr>
        <td width="10%" data-th="{__("time")} / {__("type")}">
            {if $log.type_code == "error"}
                <strong class="text-error">{$log.type_name}</strong>
            {elseif $log.type_code == "warning"}
                <strong class="text-warning">{$log.type_name}</strong>
            {elseif $log.type_code == "info"}
                <strong class="text-info">{$log.type_name}</strong>
            {/if}
            <br />
            <small class="nowrap muted">{$log.datetime|date_format:"`$settings.Appearance.date_format`, `$settings.Appearance.time_format`"}</small>
        </td>
        <td width="18%" data-th="{__("action")}">{$log.action_name}</td>
        <td data-th="{__("product_name")}"><a href="{"products.update?product_id=`$log.product_id`"|fn_url}">{$log.product_name}</a></td>
        <td data-th="{__("description")}">{$log.message}</td>
    </tr>
    {/foreach}
    </table>
</div>
{else}
    <p class="no-items">{__("no_data")}</p>
{/if}

{include file="common/pagination.tpl"}
{/capture}

{capture name="buttons"}
    {capture name="tools_list"}
        <li>{btn type="list" text=__("clean_logs") href="ebay.clean_product_logs" class="cm-confirm" method="POST"}</li>
    {/capture}
    {dropdown content=$smarty.capture.tools_list}
{/capture}

{include file="common/mainbox.tpl" title=__("ebay_logs") content=$smarty.capture.mainbox buttons=$smarty.capture.buttons sidebar=$smarty.capture.sidebar}
