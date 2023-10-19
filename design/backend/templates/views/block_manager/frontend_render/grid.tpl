{if $layout_data.layout_width != "fixed"}
    {if $parent_grid.width > 0}
        {$fluid_width = fn_get_grid_fluid_width($layout_data.width, $parent_grid.width, $grid.width)}
    {else}
        {$fluid_width = $grid.width}
    {/if}
{/if}

{if $grid.status == "A"}
    {if $grid.alpha}<div data-ca-block-manager-row-id="{$grid.grid_id}" class="{if $layout_data.layout_width != "fixed"}row-fluid {else}row{/if}">{/if}
        {$width = $fluid_width|default:$grid.width}
        <div class="span{$width}{if $grid.offset} offset{$grid.offset}{/if} {$grid.user_class}
            {if $runtime.customization_mode.block_manager && $location_data.is_frontend_editing_allowed}
                bm-block-manager__blocks-place
            {/if}"
            data-ca-block-manager-grid-id="{$grid.grid_id}"
            data-ca-block-manager-grid-name="{__("grid")} {$grid.width}"
            {if $grid.wrapper !== "blocks/grid_wrappers/lite_checkout.tpl"}
                data-ca-block-manager-blocks-place="true"
            {/if}
            {if $grid.content_align === "\Tygh\BlockManager\Grid::ALIGN_LEFT"|constant}data-ca-block-manager-is-left-alignment="true"{/if}
            {if $grid.content_align === "\Tygh\BlockManager\Grid::ALIGN_RIGHT"|constant}data-ca-block-manager-is-right-alignment="true"{/if}
            >{strip}
            {if $grid.wrapper}
                {include file=$grid.wrapper content=$content}
            {else}
                {$content nofilter}
            {/if}
        {/strip}</div>
    {if $grid.omega}</div>{/if}
{/if}