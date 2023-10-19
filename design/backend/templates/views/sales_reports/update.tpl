{if $report}
    {assign var="report_id" value=$report.report_id}
{else}
    {assign var="report_id" value=0}
{/if}

{capture name="mainbox"}

{capture name="tabsbox"}
<form action="{""|fn_url}" enctype="multipart/form-data" method="post" name="statistics_form" class=" form-horizontal" id="statistics_form">
<input type="hidden" name="report_id" value="{$report_id}">
<input type="hidden" name="selected_section" value="{$smarty.request.selected_section|default:"general"}">

<div id="content_general">

    <div class="control-group">
        <label for="description" class="cm-required control-label">{__("name")}:</label>
        <div class="controls">
            <input type="text" name="report_data[description]" id="description" value="{$report.description}" size="70">
        </div>
    </div>

    {include file="common/select_status.tpl" input_name="report_data[status]" id="report" obj=$report}
</div>

{if $report}
<div id="content_tables">
    {if $report.tables}
        {capture name="statistics_table"}
            <div class="table-responsive-wrapper longtap-selection">
                <table class="table table-middle table--relative table-responsive">
                <thead
                        data-ca-bulkedit-default-object="true"
                        data-ca-bulkedit-component="defaultObject"
                >
                    <tr>
                        <th class="center mobile-hide" width="1%">
                            {include file="common/check_items.tpl"}

                            <input type="checkbox"
                                   class="bulkedit-toggler hide"
                                   data-ca-bulkedit-disable="[data-ca-bulkedit-default-object=true]"
                                   data-ca-bulkedit-enable="[data-ca-bulkedit-expanded-object=true]"
                            />
                        </th>
                        <th width="4%">{__("position_short")}</th>
                        <th width="55%">{__("name")}</th>
                        <th width="10%">{__("type")}</th>
                        <th width="20%">{__("value_to_display")}</th>
                        <th width="10%">&nbsp;</th>
                    </tr>
                </thead>
                <tbody>
                {foreach $report.tables as $table}
                <tr class="cm-row-item cm-longtap-target"
                    data-ca-longtap-action="setCheckBox"
                    data-ca-longtap-target="input.cm-item"
                    data-ca-id="{$table.table_id}"
                >
                    <td width="1%" class="center mobile-hide">
                        <input type="hidden" name="report_data[tables][{$table.table_id}][table_id]" value="{$table.table_id}">
                        <input type="checkbox" name="del[{$table.table_id}]" id="delete_checkbox" value="Y" class="cm-item hide">
                    </td>
                    <td width="4%" data-th="{__("position_short")}"><input type="text" name="report_data[tables][{$table.table_id}][position]" value="{$table.position}" size="3" class="input-micro input-hidden"></td>
                    <td width="55%" data-th="{__("name")}"><a href="{"sales_reports.update_table?report_id=`$report_id`&table_id=`$table.table_id`"|fn_url}">{$table.description}</a></td>
                    <td width="10%" data-th="{__("type")}">
                        <select name="report_data[tables][{$table.table_id}][type]">
                            <option value="T">{__("table")}</option>
                            <option value="B" {if $table.type == "B"}selected="selected"{/if}>{__("graphic")} [{__("bar")}] </option>
                            <option value="P" {if $table.type == "P"}selected="selected"{/if}>{__("graphic")} [{__("pie_3d")}] </option>
                        </select>
                    </td>
                    <td width="20%" data-th="{__("value_to_display")}">
                        <select name="report_data[tables][{$table.table_id}][display]">
                            {foreach from=$report_elements.values item=element}
                            {assign var="element_id" value=$element.element_id}
                            {assign var="element_name" value="reports_parameter_$element_id"}
                                <option value="{$element.code}" {if $table.display == $element.code}selected="selected"{/if}>{__($element_name)}</option>
                            {/foreach}
                        </select>
                    </td>
                    <td width="10%" class="nowrap right" data-th="{__("tools")}">
                        <div class="hidden-tools">
                            {capture name="tools_list"}
                                {hook name="sales_reports:update_tools_list"}
                                    <li>{btn type="list" text=__("edit") href="sales_reports.update_table?report_id=`$report_id`&table_id=`$table.table_id`"}</li>
                                    <li>{btn type="delete" class="cm-config" href="sales_reports.delete_table?table_id=`$table.table_id`&report_id=`$report.report_id`" method="POST"}</li>
                                {/hook}
                            {/capture}
                            {dropdown content=$smarty.capture.tools_list}
                        </div>
                    </td>
                </tr>
                {/foreach}
                </tbody>
                </table>
            </div>
        {/capture}

        {include file="common/context_menu_wrapper.tpl"
            form="statistics_form"
            object="sales_reports_charts"
            items=$smarty.capture.statistics_table
            is_check_all_shown=true
        }
    {else}
        <p class="no-items">{__("no_data")}</p>
    {/if}
</div>
{/if}
</form>
{/capture}
{include file="common/tabsbox.tpl" content=$smarty.capture.tabsbox active_tab=$smarty.request.selected_section track=true}

{capture name="buttons"}
    <span class="mobile-hide shift-right">
    {if $report_id}
        {include file="common/tools.tpl" tool_href="sales_reports.update_table?report_id=`$report_id`" prefix="bottom" hide_tools=true link_text=__("add_chart")}
    {/if}
    </span>
    {include file="buttons/save_cancel.tpl" but_name="dispatch[sales_reports.update]" but_role="action" but_target_form="statistics_form" but_meta="cm-submit" save=$report_id}
{/capture}

{/capture}

{include
    file="common/mainbox.tpl"
    title=($report_id) ? $report.description : __("new_report")
    content=$smarty.capture.mainbox
    buttons=$smarty.capture.buttons
}
