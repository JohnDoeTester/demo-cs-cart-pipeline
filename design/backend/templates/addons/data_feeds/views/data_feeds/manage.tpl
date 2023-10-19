{hook name="data_feeds:notice"}
{notes title=__("notice")}
{if ("ULTIMATE"|fn_allowed_for)}
    {$switch_key = "switch_company_id"}
    {$switch_value = $runtime.company_id}
{else}
    {$switch_key = "s_storefront"}
    {$switch_value = $selected_storefront_id}
{/if}
<p>{__("export_cron_hint")}:<br />
    {"php /path/to/cart/"|fn_get_console_command:$config.admin_index:["dispatch" => "exim.cron_export","cron_password" => {$addons.data_feeds.cron_password},$switch_key => $switch_value]}
    </p>
{/notes}
{/hook}

{capture name="mainbox"}

<form action="{""|fn_url}" method="post" id="manage_datafeeds_form" name="manage_datafeeds_form">

{$data_feed_statuses=""|fn_get_default_statuses:false}

{if $datafeeds}
    {capture name="data_feeds_table"}
        <div class="table-responsive-wrapper longtap-selection">
            <table class="table sortable table-middle table--relative table-responsive">
            <thead
                data-ca-bulkedit-default-object="true"
                data-ca-bulkedit-component="defaultObject"
            >
                <tr>
                    <th width="6%" class="left mobile-hide">
                        {include file="common/check_items.tpl" check_statuses=$data_feed_statuses}

                        <input type="checkbox"
                            class="bulkedit-toggler hide"
                            data-ca-bulkedit-disable="[data-ca-bulkedit-default-object=true]"
                            data-ca-bulkedit-enable="[data-ca-bulkedit-expanded-object=true]"
                        />
                    </th>
                    <th width="45%" class="nowrap">{__("name")}</th>
                    <th width="35%" class="nowrap">{__("filename")}</th>
                    <th width="8%" class="nowrap">&nbsp;</th>
                    <th width="8%" class="nowrap right">{__("status")}</th>
                </tr>
            </thead>
            {foreach from=$datafeeds item=datafeed}
            <tr class="cm-row-status-{$datafeed.status|lower} cm-longtap-target"
                data-ca-longtap-action="setCheckBox"
                data-ca-longtap-target="input.cm-item"
                data-ca-id="{$datafeed.datafeed_id}"
            >
                <td width="6%" class="left mobile-hide">
                    <input type="checkbox" name="datafeed_ids[]" value="{$datafeed.datafeed_id}" class="checkbox cm-item cm-item-status-{$datafeed.status|lower} hide" />
                </td>

                <td width="45%" data-th="{__("name")}">
                    <a href="{"data_feeds.update?datafeed_id=`$datafeed.datafeed_id`"|fn_url}">{$datafeed.datafeed_name}</a>
                    {include file="views/companies/components/company_name.tpl" object=$datafeed}
                </td>

                <td width="35%" class="nowrap" data-th="{__("filename")}">
                    {$datafeed.file_name}
                </td>

                <td width="8%" class="nowrap" data-th="{__("tools")}">
                    {capture name="tools_list"}
                        <li>{btn type="list" class="cm-confirm cm-ajax cm-comet" text=__("local_export") href="exim.export_datafeed?datafeed_ids[]=`$datafeed.datafeed_id`&location=L&`$switch_key`=`$switch_value`"}</li>
                        <li>{btn type="list" class="cm-confirm cm-ajax cm-comet" text=__("export_to_server") href="exim.export_datafeed?datafeed_ids[]=`$datafeed.datafeed_id`&location=S&`$switch_key`=`$switch_value`"}</li>
                        <li>{btn type="list" class="cm-confirm cm-ajax cm-comet" text=__("upload_to_ftp") href="exim.export_datafeed?datafeed_ids[]=`$datafeed.datafeed_id`&location=F&`$switch_key`=`$switch_value`"}</li>
                        <li class="divider"></li>
                        <li>{btn type="list" text=__("edit") href="data_feeds.update?datafeed_id=`$datafeed.datafeed_id`"}</li>
                    {/capture}
                    <div class="hidden-tools">
                        {dropdown content=$smarty.capture.tools_list}
                    </div>
                </td>

                <td width="8%" class="nowrap right" data-th="{__("status")}">
                    {include file="common/select_popup.tpl" id=$datafeed.datafeed_id status=$datafeed.status hidden=false object_id_name="datafeed_id" table="data_feeds"}
                </td>

            </tr>
            {/foreach}
            </table>
        </div>
    {/capture}

    {include file="common/context_menu_wrapper.tpl"
        form="manage_datafeeds_form"
        object="data_feeds"
        items=$smarty.capture.data_feeds_table
    }
{else}
    <p class="no-items">{__("no_data")}</p>
{/if}

{capture name="adv_buttons"}
    {include file="common/tools.tpl" tool_href="data_feeds.add" prefix="bottom" title="{__("add_datafeed")}" hide_tools=true icon="icon-plus"}
{/capture}

</form>

{/capture}
{include file="common/mainbox.tpl"
    title=__("data_feeds")
    content=$smarty.capture.mainbox
    tools=$smarty.capture.tools
    select_languages=true
    adv_buttons=$smarty.capture.adv_buttons
    select_storefront=true
    show_all_storefront=true
    storefront_switcher_param_name="storefront_id"
}
