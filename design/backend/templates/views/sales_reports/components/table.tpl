{script src="js/tygh/infinite_scroll.js"}
{if $table_conditions.$table_id}
    {include file="common/subheader.tpl" title=__("table_conditions") meta="collapsed" target="#box_table_conditions_`$table_id`"}
    <div id="box_table_conditions_{$table_id}" class="collapse">
        <dl class="dl-horizontal">
        {foreach from=$table_conditions.$table_id item="i"}
            <dt>{$i.name}:</dt>
            <dd>
                {foreach from=$i.objects item="o" name="feco"}
                    {if $o.href}<a href="{$o.href|fn_url}">{/if}{$o.name}{if $o.href}</a>{/if}{if !$smarty.foreach.feco.last}, {/if}
                {/foreach}
            </dd>
        {/foreach}
        </dl>
    </div>
{/if}

{$ajax_div_ids = "`$table_id`,data_list_orders_`$table_id`"}

{if $table.interval_id != 1}

<div class="cm-scroll-data scroll-data--fullwidth" id="scroll_content_{$table_id}" data-ca-target-id="{$ajax_div_ids}">
    <input type="hidden" id="count_scroll_{$table_id}" value="{$count_limit}" />
    <input type="hidden" id="begin_scroll_{$table_id}" value="{$count_part}" />
    <div class="table-wrapper">
    <table width="100%" class="table table--relative cm-table-list-orders">
        <thead class="cm-table-thead">
        <tr valign="top">
            <th style="padding: 1px;" >{$table.parameter}</th>
            {foreach from=$table.intervals item=row}
                <th class="center cm-tooltip" style="padding: 1px;">&nbsp;{$row.description}&nbsp;<a title="{$row.iso8601_from} &ndash; {$row.iso8601_to}" class="cm-tooltip">{include_ext file="common/icon.tpl" class="icon-question-sign"}</a></th>
            {/foreach}
        </tr>
        </thead>
        <tbody class="cm-scroll-content cm-ajax cm-table-tbody" id="{$table_id}">
            {cycle values="" assign=""}
            {foreach from=$table.elements item=element}
                <tr>
                    <td class="sales-report-title" >{$element.description nofilter}&nbsp;</td>
                    {assign var="element_hash" value=$element.element_hash}
                    {foreach from=$table.intervals item=row}
                        {assign var="interval_id" value=$row.interval_id}
                        <td class="center">
                            {if $table.values.$element_hash.$interval_id}
                                {if $table.display != "product_number" && $table.display != "order_number"}
                                    {include file="common/price.tpl"
                                        value=$table.values.$element_hash.$interval_id
                                        enable_non_breaking_space=false
                                    }
                                {else}
                                    {$table.values.$element_hash.$interval_id}
                                {/if}
                            {else}-{/if}
                        </td>
                    {/foreach}
                </tr>
            {/foreach}
            {if $totals}
                <tr class="td-no-bg cm-table-footer" id="total_scroll_{$table_id}">
                    <td class="right">{__("total")}:</td>
                    {foreach from=$totals item=row key=k_row}
                        <td class="center">
                            {if $row}
                                <span>
                                    {if $table.display != "product_number" && $table.display != "order_number"}
                                        {include file="common/price.tpl"
                                            value=$row
                                            enable_non_breaking_space=false
                                        }
                                    {else}
                                        {$row}
                                    {/if}
                                </span>
                            {else}
                                -
                            {/if}
                        </td>
                    {/foreach}
                </tr>
            {/if}
        <!--{$table_id}--></tbody>
    </table>
    </div>
</div>
{else}
<div class="cm-scroll-data scroll-data--fullwidth" id="scroll_content_{$table_id}" data-ca-target-id="{$ajax_div_ids}">
<input type="hidden" id="count_scroll_{$table_id}" value="{$count_limit}" />
<input type="hidden" id="begin_scroll_{$table_id}" value="{$count_part}" />
    <div class="table-responsive-wrapper">
    <table class="table table-middle table--relative">
        <thead id="elm_head_scroll_{$table_id}_{$count_part}" class="cm-table-thead">
            <tr valign="top">
                <th style="padding: 1px;">{$table.parameter}</th>
                {foreach from=$table.intervals item=row}
                    {assign var="interval_id" value=$row.interval_id}
                    {assign var="interval_name" value="reports_interval_$interval_id"}
                    <th class="right" width="70%">{__($interval_name)}</th>
                {/foreach}
            </tr>
        </thead>
        <tbody class="cm-scroll-content cm-ajax cm-table-tbody" id="{$table_id}">
            {assign var="elements_count" value=$table.elements|sizeof}
            {foreach from=$table.elements item=element}
                {assign var="element_hash" value=$element.element_hash}
                <tr>
                    {foreach from=$table.intervals item=row}
                        {assign var="interval_id" value=$row.interval_id}
                        {math equation="round(element_value/max_value*100)" element_value=$table.values.$element_hash.$interval_id|default:"0" max_value=$table.max_value|default:"1" assign="percent_value"}
                        <td width="85%">
                            {$element.description nofilter}&nbsp;
                            {include file="views/sales_reports/components/graph_bar.tpl" value_width=$percent_value}
                        </td>
                        <td class="right">
                            {if $table.values.$element_hash.$interval_id}
                                {if $table.display != "product_number" && $table.display != "order_number"}
                                    {include file="common/price.tpl"
                                        value=$table.values.$element_hash.$interval_id
                                        enable_non_breaking_space=false
                                    }
                                {else}
                                    {$table.values.$element_hash.$interval_id}
                                {/if}
                            {else}
                                -
                            {/if}
                        </td>
                    {/foreach}
                </tr>
            {/foreach}
            {if $totals}
            <tr class="td-no-bg" id="total_scroll_{$table_id}">
                <td class="right" width="70%">{if $totals}{__("total")}:{/if}</td>
                <td class="right" width="30%">
                    {foreach from=$totals item="row"}
                        {if $row}
                            {if $table.display != "product_number" && $table.display != "order_number"}
                                {include file="common/price.tpl"
                                    value=$row
                                    enable_non_breaking_space=false
                                }
                            {else}
                                {$row}
                            {/if}
                        {else}
                            -
                        {/if}
                    {/foreach}
                </td>
            </tr>
            {/if}
        <!--{$table_id}--></tbody>
    </table>
    </div>
</div>
{/if}
