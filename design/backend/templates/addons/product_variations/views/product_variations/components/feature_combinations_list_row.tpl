<tr class="multiple-table-row cm-row-status-{if $combination.exists}d{else}a{/if}">
    {math equation="x*14" x=$level assign="shift"}

    <td width="1%">
        <input type="checkbox" name="combination_ids[]" value="{$combination_id}" class="cm-item{if $group_meta} cm-item-{$group_meta}{/if}"{if $combination.exists} checked disabled{/if}/>
    </td>
    <td width="99%">
        {strip}
            <span style="padding-left:{$shift}px;">
                <span class="row-status normal">
                    {$combination.name}
                </span>
            </span>
        {/strip}
    </td>
</tr>
