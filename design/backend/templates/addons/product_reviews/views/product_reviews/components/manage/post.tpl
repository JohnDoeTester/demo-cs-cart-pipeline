{*
    $product_review                 array                               Product review
    $product_review_id              int                                 Product review ID
    $rating_value                   int                                 Rating value
    $product_review_images          array                               Product review images
    $available_message_types        array                               Available message types
    $NAME_CHARACTERS_THRESHOLD      int                                 Name characters threshold
*}

{if $product_review}
    {$show_product = $show_product|default:true}
    {$NAME_CHARACTERS_THRESHOLD = 30}

    <section>

        <section>

            <header class="flex flex-wrap spaced-child">

                {* Review ID *}
                <a href="{"product_reviews.update?product_review_id=`$product_review_id`"|fn_url}">
                    {__("product_reviews.review")} #{$product_review_id}
                </a>
                
                {* Review stars *}
                {include file="addons/product_reviews/views/product_reviews/components/rating/stars.tpl"
                    rating=$rating_value
                    link="product_reviews.update?product_review_id=`$product_review_id`"|fn_url
                }

                {* Review with photo icon *}
                {include file="addons/product_reviews/views/product_reviews/components/reviews/review_with_photo_icon.tpl"
                    product_review_images=$product_review_images
                    link=true
                }

            </header>

            {* Message *}
            {foreach $available_message_types as $message_type}
                {include file="common/content_more.tpl"
                    text=$product_review.message.$message_type|escape|nl2br
                    meta="cs-content-more__text--`$message_type`"
                }
            {/foreach}

            {* Review images *}
            {include file="addons/product_reviews/views/product_reviews/components/reviews/review_images.tpl"
                product_review_images=$product_review_images
            }

        </section>

        {* Vendor reply *}
        {if $product_review_reply}
            <div class="shift-left">
                {capture name="prefix"}
                    {include file="addons/product_reviews/views/product_reviews/components/reviews/vendor_name.tpl"
                        product_review_reply=$product_review_reply
                    }:
                {/capture}

                {include file="common/content_more.tpl"
                    text=$product_review_reply.reply|escape|nl2br
                    prefix=$smarty.capture.prefix
                    meta="cs-content-more__text--review-reply "
                }
            </div>
        {/if}

        <footer>
            <small class="dashed-child">

                {* Review product *}
                {if $show_product && $product.product}
                    <a href="{"products.update?product_id=`$product.product_id`"|fn_url}"
                        title="{$product.product}"
                    >
                        {$product.product|truncate:$NAME_CHARACTERS_THRESHOLD:"...":true}
                    </a>
                {/if}

                {* Review customer *}
                {include file="addons/product_reviews/views/product_reviews/components/reviews/customer.tpl"
                    user_data=$user_data
                }

            </small>
        </footer>

    </section>
{/if}
