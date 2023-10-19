{if $pf.product_variation_group}
    <tr>
        <td>{$pf.description}:</td>
        <td >
            {foreach $pf.variants as $variant}
                {if $variant.selected}
                    <span class="shift-input">{$variant.variant}</span>
                {/if}
            {/foreach}
            {include file="common/tooltip.tpl" tooltip=__("product_variations.feature_used_by_variation_group.tooltip", ["[code]" => $pf.product_variation_group.code])}
        </td>
    </tr>
{/if}