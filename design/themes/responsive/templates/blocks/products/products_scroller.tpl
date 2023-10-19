{** block-description:tmpl_scroller **}

{if $settings.Appearance.enable_quick_view == "Y" && $block.properties.enable_quick_view == "Y"}
    {$quick_nav_ids = $items|fn_fields_from_multi_level:"product_id":"product_id"}
{/if}

{if $block.properties.hide_add_to_cart_button == "Y"}
        {$_show_add_to_cart=false}
    {else}
        {$_show_add_to_cart=true}
    {/if}
    {if $block.properties.show_price == "Y"}
        {$_hide_price=false}
    {else}
        {$_hide_price=true}
{/if}

{$show_old_price = true}

{$obj_prefix="`$block.block_id`000"}
{$block.block_id = "{$block.block_id}_{uniqid()}"}
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
        <div class="owl-controls clickable owl-controls-outside"  id="owl_outside_nav_{$block.block_id}">
            <div class="owl-buttons">
                <div id="owl_prev_{$obj_prefix}" class="owl-prev">{include_ext file="common/icon.tpl" class="ty-icon-left-open-thin"}</div>
                <div id="owl_next_{$obj_prefix}" class="owl-next">{include_ext file="common/icon.tpl" class="ty-icon-right-open-thin"}</div>
            </div>
        </div>
    </div>
{/if}

<div id="scroll_list_{$block.block_id}" class="owl-carousel ty-scroller-list ty-scroller"
    data-ca-scroller-item="{$item_quantity}"
    data-ca-scroller-item-desktop="{$item_quantity_desktop}"
    data-ca-scroller-item-desktop-small="{$item_quantity_desktop_small}"
    data-ca-scroller-item-tablet="{$item_quantity_tablet}"
    data-ca-scroller-item-mobile="{$item_quantity_mobile}"
>
    {foreach from=$items item="product" name="for_products"}
        {hook name="products:product_scroller_list"}
        <div class="ty-scroller-list__item ty-scroller__item">
            {hook name="products:product_scroller_list_item"}
            {$obj_id="scr_`$block.block_id`000`$product.product_id`"}
            <div class="ty-scroller-list__img-block">
                {include file="common/image.tpl" assign="object_img" images=$product.main_pair image_width=$block.properties.thumbnail_width image_height=$block.properties.thumbnail_width no_ids=true lazy_load=true}
                <a href="{"products.view?product_id=`$product.product_id`"|fn_url}">{$object_img nofilter}</a>
                {if $settings.Appearance.enable_quick_view == "Y" && $block.properties.enable_quick_view == "Y"}
                    {include file="views/products/components/quick_view_link.tpl" quick_nav_ids=$quick_nav_ids}
                {/if}
            </div>
            <div class="ty-scroller-list__description">
                {strip}
                    {include file="blocks/list_templates/simple_list.tpl" product=$product show_name=true show_price=true show_add_to_cart=$_show_add_to_cart but_role="action" hide_price=$_hide_price hide_qty=true show_product_labels=true show_discount_label=true show_shipping_label=true}
                {/strip}
            </div>
            {/hook}
        </div>
        {/hook}
    {/foreach}
</div>


{include file="common/scroller_init.tpl" prev_selector="#owl_prev_`$obj_prefix`" next_selector="#owl_next_`$obj_prefix`"}

