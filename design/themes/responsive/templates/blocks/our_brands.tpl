{$obj_prefix = "`$block.block_id`000"}
{$item_quantity = $block.properties.item_quantity|default:5}
{$item_quantity_desktop = $item_quantity}
{$item_quantity_mobile = 1}

{if $item_quantity > 3}
    {$item_quantity_desktop_small = $item_quantity - 1}
    {$item_quantity_tablet = $item_quantity - 2}
{elseif $item_quantity === 1}
    {$item_quantity_desktop_small = $item_quantity}
    {$item_quantity_tablet = $item_quantity}
{else}
    {$item_quantity_desktop_small = $item_quantity - 1}
    {$item_quantity_tablet = $item_quantity - 1}
{/if}

{if $block.properties.outside_navigation == "Y"}
    <div class="owl-theme ty-owl-controls">
        <div class="owl-controls clickable owl-controls-outside" id="owl_outside_nav_{$block.block_id}">
            <div class="owl-buttons">
                <div id="owl_prev_{$obj_prefix}" class="owl-prev">{include_ext file="common/icon.tpl" class="ty-icon-left-open-thin"}</div>
                <div id="owl_next_{$obj_prefix}" class="owl-next">{include_ext file="common/icon.tpl" class="ty-icon-right-open-thin"}</div>
            </div>
        </div>
    </div>
{/if}

<div id="scroll_list_{$block.block_id}" class="owl-carousel ty-scroller"
    data-ca-scroller-item="{$item_quantity}"
    data-ca-scroller-item-desktop="{$item_quantity_desktop}"
    data-ca-scroller-item-desktop-small="{$item_quantity_desktop_small}"
    data-ca-scroller-item-tablet="{$item_quantity_tablet}"
    data-ca-scroller-item-mobile="{$item_quantity_mobile}"
>
    {foreach from=$brands item="brand" name="for_brands"}
            {include file="common/image.tpl" assign="object_img" class="ty-grayscale" image_width=$block.properties.thumbnail_width image_height=$block.properties.thumbnail_width images=$brand.image_pair no_ids=true lazy_load=true obj_id="scr_`$block.block_id`000`$brand.variant_id`"}
            <div class="ty-center ty-scroller__item">
                <a href="{"product_features.view?variant_id=`$brand.variant_id`"|fn_url}">{$object_img nofilter}</a>
            </div>
    {/foreach}
</div>
{include file="common/scroller_init.tpl" items=$brands prev_selector="#owl_prev_`$obj_prefix`" next_selector="#owl_next_`$obj_prefix`"}
