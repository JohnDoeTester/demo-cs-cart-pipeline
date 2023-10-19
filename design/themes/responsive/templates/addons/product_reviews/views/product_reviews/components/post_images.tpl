{*
    $images
    $image
    $settings
    $preview_id
*}

{if $images}
    <div class="ty-product-review-post-images">
        {foreach $images as $image name="post_images"}
            <figure class="ty-product-review-post-images__item">
                {include file="common/image.tpl"
                    class=""
                    images=$image
                    image_width=$settings.Thumbnails.product_lists_thumbnail_width
                    image_height=$settings.Thumbnails.product_lists_thumbnail_height
                    image_link_additional_attrs=[
                        "data-ca-image-order"=>$smarty.foreach.post_images.index
                    ]
                    show_detailed_link=true
                    image_id="preview[product_review_`$preview_id`]"
                    link_class="cm-previewer-only ty-previewer-only"
                    obj_id=uniqid()
                }
            </figure>
        {/foreach}
    </div>
{/if}
