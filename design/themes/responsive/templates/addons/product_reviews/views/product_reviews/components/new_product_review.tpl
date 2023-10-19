{*
    $product_id
    $meta
    $post_redirect_url
    $config
    $product_reviews_images_upload_allowed
*}
{$max_images_upload = $config.tweaks.product_reviews.max_images_upload|default:10}

<script>
    (function(_, $) {
        $.extend(_, {
            max_images_upload: '{$max_images_upload}'
        });
    }(Tygh, Tygh.$));
</script>


<section id="new_post_dialog_{$product_id}"
    class="ty-product-review-new-product-review {if $meta} {$meta}{/if}"
>
    <form action="{""|fn_url}"
        method="post"
        class="{if !$post_redirect_url}cm-ajax cm-form-dialog-closer{/if} ty-product-review-new-product-review__form"
        name="add_post_form"
        enctype="multipart/form-data"
        id="add_post_form_{$product_id}"
    >

        <input type="hidden" name="result_ids" value="posts_list*,new_post*,average_rating*">
        <input type="hidden" name="product_review_data[product_id]" value="{$product_id}" />
        <input type="hidden" name="redirect_url" value="{$post_redirect_url|default:$config.current_url}" />
        <input type="hidden" name="selected_section" value="" />

        <div class="ty-product-review-new--review__body" id="new_post_{$product_id}">

            {include file="addons/product_reviews/views/product_reviews/components/new_product_review_rating.tpl"
                product_id=$product_id
                product_reviews_ratings=$product_reviews_ratings
            }

            {hook name="product_reviews:add_product_review"}
                {include file="addons/product_reviews/views/product_reviews/components/new_product_review_media.tpl"
                    product_reviews_images_upload_allowed=$product_reviews_images_upload_allowed
                }

                {include file="addons/product_reviews/views/product_reviews/components/new_product_review_message.tpl"
                    product_id=$product_id
                }

                {include file="addons/product_reviews/views/product_reviews/components/new_product_review_customer.tpl"
                    user_data=$user_data
                    product_id=$product_id
                    post_redirect_url=$post_redirect_url
                    product_review_data=$product_review_data
                    countries=$countries
                }
            {/hook}

            {include file="addons/product_reviews/views/product_reviews/components/new_product_review_additional.tpl"
                product_id=$product_id
            }

            {include file="addons/product_reviews/views/product_reviews/components/new_product_review_captcha.tpl"}

        <!--new_product_review_{$product_id}--></div>

        <footer class="buttons-container ty-product-review-new-product-review__footer">
            {include file="buttons/button.tpl"
                but_text=__("product_reviews.submit_review")
                but_meta="ty-btn__primary ty-width-full"
                but_role="submit"
                but_name="dispatch[product_reviews.add]"
            }
        </footer>
    </form>
<!--new_product_review_dialog_{$product_id}--></section>
