<div class="{if $block.status === "D"}bm-block-manager__block--disabled{/if}
    bm-block-manager__block bm-block-manager__block--{$location_data.dispatch}
    {if $content_alignment == "RIGHT"} ty-float-right
    {elseif $content_alignment == "LEFT"} ty-float-left{/if}"
    data-ca-block-manager-snapping-id="{$snapping_id}"
>
    {include file="backend:views/block_manager/frontend_render/components/block_menu.tpl"}
    <div class="bm-block-manager__block-content clearfix">
        {include file=$wrapper}
    </div>
</div>