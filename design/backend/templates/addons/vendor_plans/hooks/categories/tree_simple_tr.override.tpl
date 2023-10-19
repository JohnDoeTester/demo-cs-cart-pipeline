{if $cur_cat.disabled}
    <tr class="{if $cur_cat.level > 0} multiple-table-row {/if}cm-row-status-{$category.status|lower}">
        {math equation="x*14" x=$level assign="shift"}
        <td width="{if $cur_cat.level > 0}4%{else}1%{/if}">&nbsp;</td>
        <td>
            {if $cur_cat.has_children || $cur_cat.subcategories}
                {math equation="x+10" x=$shift assign="_shift"}
            {else}
                {math equation="x+21" x=$shift assign="_shift"}
            {/if}
            <span class="nowrap" style="padding-{$direction}: {$_shift}px;">
            {if $cur_cat.has_children || $cur_cat.subcategories}
                {if $show_all}
                    <span title="{__("expand_sublist_of_items")}" id="on_{$comb_id}" class="hand cm-combination-cat cm-uncheck {if isset($path.$cat_id) || $expand_all}hidden{/if}"><span class="icon-caret-right"></span></span>
                {else}
                    {if $except_id}
                        {assign var="_except_id" value="&except_id=`$except_id`"}
                    {/if}
                    <span title="{__("expand_sublist_of_items")}" id="on_{$comb_id}" class="hand cm-combination-cat cm-uncheck {if (isset($path.$cat_id))}hidden{/if}" onclick="if (!$('#{$comb_id}').children().length) Tygh.$.ceAjax('request', '{"categories.picker?category_id=`$cur_cat.category_id`&random=`$random`&display=`$display`&checkbox_name=`$checkbox_name``$_except_id`"|fn_url nofilter}', {$ldelim}result_ids: '{$comb_id}'{$rdelim})"><span class="icon-caret-right"> </span></span>
                {/if}
                <span title="{__("collapse_sublist_of_items")}" id="off_{$comb_id}" class="hand cm-combination-cat cm-uncheck {if !isset($path.$cat_id) && (!$expand_all || !$show_all)}hidden{/if}"><span class="icon-caret-down"></span></span>
            {/if}
            <span id="category_{$cur_cat.category_id}">{$cur_cat.category}</span>{if $cur_cat.status == "N"}&nbsp;<span class="small-note">-&nbsp;[{__("disabled")}]</span>{/if}
            </span>
        </td>
        {if !$runtime.company_id}
            <td class="right">&nbsp;</td>
        {/if}
    </tr>
{/if}
