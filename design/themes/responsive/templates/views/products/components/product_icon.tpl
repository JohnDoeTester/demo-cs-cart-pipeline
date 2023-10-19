{assign var="product_detail_view_url" value="products.view?product_id=`$product.product_id`"}
{capture name="product_detail_view_url"}
{** Sets product detail view link *}
{hook name="products:product_detail_view_url"}
{$product_detail_view_url}
{/hook}
{/capture}

{$product_detail_view_url = $smarty.capture.product_detail_view_url|trim}

{capture name="main_icon"}
    <a href="{"$product_detail_view_url"|fn_url}">
        {include file="common/image.tpl" obj_id=$obj_id_prefix images=$product.main_pair image_width=$settings.Thumbnails.product_lists_thumbnail_width image_height=$settings.Thumbnails.product_lists_thumbnail_height}
    </a>
{/capture}

{if $product.image_pairs && $show_gallery}
<div class="ty-center-block">
    <div class="ty-thumbs-wrapper owl-carousel cm-image-gallery ty-scroller"
        data-ca-items-count="1"
        data-ca-items-responsive="true"
        data-ca-scroller-item="1"
        data-ca-scroller-item-desktop="1"
        data-ca-scroller-item-desktop-small="1"
        data-ca-scroller-item-tablet="1"
        data-ca-scroller-item-mobile="1"
        id="icons_{$obj_id_prefix}">
        {if $product.main_pair}
            <div class="cm-gallery-item cm-item-gallery ty-scroller__item">
                {$smarty.capture.main_icon nofilter}
            </div>
        {/if}
        {foreach from=$product.image_pairs item="image_pair"}
            {if $image_pair}
                <div class="cm-gallery-item cm-item-gallery ty-scroller__item">
                    <a href="{"$product_detail_view_url"|fn_url}">
                        {include file="common/image.tpl" no_ids=true images=$image_pair image_width=$settings.Thumbnails.product_lists_thumbnail_width image_height=$settings.Thumbnails.product_lists_thumbnail_height lazy_load=true}
                    </a>
                </div>
            {/if}
        {/foreach}
    </div>
</div>
{else}
    {$smarty.capture.main_icon nofilter}
{/if}