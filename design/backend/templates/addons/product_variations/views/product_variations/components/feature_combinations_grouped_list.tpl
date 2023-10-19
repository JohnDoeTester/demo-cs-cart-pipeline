{$expand_all = $expand_all|default:false}

<table class="table table-tree table-middle table--relative">
    <thead>
    <tr>
        <th width="1%">{include file="common/check_items.tpl"}</th>
        <th width="99%">
            <div class="pull-left">
                <span alt="{__("expand_collapse_list")}" title="{__("expand_collapse_list")}" id="on_variations_tree" class="cm-combinations{if $expand_all} hidden{/if}"><span class="icon-caret-right"> </span></span>
                <span alt="{__("expand_collapse_list")}" title="{__("expand_collapse_list")}" id="off_variations_tree" class="cm-combinations{if !$expand_all} hidden{/if}"><span class="icon-caret-down"> </span></span>
            </div>
            &nbsp;{__("product_variations.variations")}
        </th>
    </tr>
    </thead>
</table>
<div class="variations_tree">
    {$variant_id = false}
    {$groups = []}

    {foreach $combinations as $compbination_id => $combination}
        {$first_variant_id = $combination.selected_variants|reset}

        {if $variant_id !== $first_variant_id}
            {$variant_id = $first_variant_id}

            {if !isset($groups.$variant_id)}
                {$groups.$variant_id.disable = true}
            {/if}
        {/if}
        {if !$combination.exists}
            {$groups.$variant_id.disable = false}
        {/if}
        
        {$groups.$variant_id.items.$compbination_id = $combination}
    {/foreach}

    {foreach $groups as $group_id => $group}
        {$first_combination = $group.items|reset}

        <table class="table table-tree table-middle table--relative">
            <tr class="multiple-table-row cm-row-status-{if $group.disable}d{else}a{/if}">
                <td width="1%">
                    <input type="checkbox" value="" data-ca-target="group_{$group_id}" class="cm-check-items cm-item cm-item-status-d" {if $group.disable} checked disabled{/if} />
                </td>
                <td width="99%">
                    {strip}
                        <span style="padding-left: {$level * 14}px;">
                            <span alt="{__("expand_sublist_of_items")}" title="{__("expand_sublist_of_items")}" id="on_group_{$group_id}" class="cm-combination {if $expand_all}hidden{/if}" ><span class="icon-caret-right"> </span></span>
                            <span alt="{__("collapse_sublist_of_items")}" title="{__("collapse_sublist_of_items")}" id="off_group_{$group_id}" class="cm-combination{if !$expand_all} hidden{/if}" ><span class="icon-caret-down"> </span></span>
                            <span class="row-status">{$first_combination.group_name}</span>
                        </span>
                    {/strip}
                </td>
            </tr>
        </table>
        <div id="group_{$group_id}" {if !$expand_all}class="hidden"{/if}>
            <table class="table table-tree table-middle table--relative">
                {foreach $group.items as $combination_id => $item}
                    {include file="addons/product_variations/views/product_variations/components/feature_combinations_list_row.tpl"
                        level=1
                        combination=$item
                        combination_id=$combination_id
                        group_meta="group_{$group_id}"
                    }
                {/foreach}
            </table>
        </div>
    {/foreach}

</div>
