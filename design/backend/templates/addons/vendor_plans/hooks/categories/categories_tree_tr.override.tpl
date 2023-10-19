{if $category.disabled}
    <tbody>
    <tr class="{if $category.level > 0}multiple-table-row {/if}cm-row-status-{$category.status|lower}">
        {math equation="x*14" x=$category.level|default:"0" assign="shift"}
        <td width="5%">&nbsp;</td>
        <td width="8%">&nbsp;</td>
        <td width="54%">
            {strip}
                <span style="padding-{$direction}: {$shift}px;">
                {if $category.has_children || $category.subcategories}
                    {if $show_all}
                        <span alt="{__("expand_sublist_of_items")}" title="{__("expand_sublist_of_items")}" id="on_{$comb_id}" class="cm-combination {if $expand_all}hidden{/if}" /><span class="icon-caret-right"> </span></span>
                        {else}
                        <span alt="{__("expand_sublist_of_items")}" title="{__("expand_sublist_of_items")}" id="on_{$comb_id}" class="icon-caret-down cm-combination" onclick="if (!Tygh.$('#{$comb_id}').children().get(0)) Tygh.$.ceAjax('request', '{"categories.manage?category_id=`$category.category_id`"|fn_url nofilter}', {$ldelim}result_ids: '{$comb_id}'{$rdelim})"><span class="icon-caret-right"> </span></span>
                    {/if}
                    <span alt="{__("collapse_sublist_of_items")}" title="{__("collapse_sublist_of_items")}" id="off_{$comb_id}" class="cm-combination{if !$expand_all || !$show_all} hidden{/if}"><span class="icon-caret-down"></span></span>
                {/if}
                {$category.category}{if $category.status == "N"}&nbsp;<span class="small-note">-&nbsp;[{__("disabled")}]</span>{/if}
                </span>
            {/strip}
        </td>
        <td width="12%" class="center">&nbsp;</td>
        <td width="5%" class="center">&nbsp;</td>
        <td width="10%" class="right">&nbsp;</td>
    </tr>
    </tbody>
{/if}
