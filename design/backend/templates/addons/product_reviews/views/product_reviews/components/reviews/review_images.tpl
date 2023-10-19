{*
    $product_review_images          array                               Product review images
    $image                          array                               Image
    $preview_id                     string                              Preview ID
    $size                           string                              Size
    $image_width                    int                                 Image width
    $image_height                   int                                 Image height
    $show_delete                    bool                                Show delete
*}

{if $product_review_images}

    {* Attach previewer script *}
    {include file="common/previewer.tpl"}

    {$preview_id = $product_review.product_review_id|uniqid}
    {if $size === "large"}
        {$image_width = ($settings.Thumbnails.product_admin_mini_icon_width|intval * 2)}
        {$image_height = ($settings.Thumbnails.product_admin_mini_icon_height|intval * 2)}
    {else}
        {$image_width = $settings.Thumbnails.product_admin_mini_icon_width}
        {$image_height = $settings.Thumbnails.product_admin_mini_icon_height}
    {/if}

    <section class="flex flex-wrap spaced-child">
        {foreach $product_review_images as $image name="post_images"}
            <div class="cs-product-reviews-reviews-review-images">

                <div class="cs-product-reviews-reviews-review-images__toolbar">
                    {if $show_delete}
                        <label class="cs-product-reviews-reviews-review-images__delete-label">
                            <input type="checkbox"
                                name="product_review_data[delete_images][]"
                                value="{$image.pair_id}"
                                class="cs-product-reviews-reviews-review-images__delete-checkbox"
                            />
                            <div class="cs-product-reviews-reviews-review-images__delete-btn">
                                {include_ext file="common/icon.tpl"
                                    class="icon icon-trash cs-product-reviews-reviews-review-images__delete-icon"
                                }
                            </div>
                        </label>
                    {/if}
                </div>


                <a id="image_preview_product_review_{$preview_id}"
                    href="{$image.detailed.image_path}"
                    data-ca-image-id="preview_product_review_{$preview_id}"
                    data-ca-image-order={$smarty.foreach.post_images.index}
                    class="cm-previewer cs-product-reviews-reviews-review-images__link"
                >
                        {include file="common/image.tpl"
                            images=$image
                            image_width=$image_width
                            image_height=$image_height
                            show_detailed_link=false
                            image_css_class="cs-product-reviews-reviews-review-images__image"
                        }
                </a>

            </div>
        {/foreach}
    </section>
{/if}
