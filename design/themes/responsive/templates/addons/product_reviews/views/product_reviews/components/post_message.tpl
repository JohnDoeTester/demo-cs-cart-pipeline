{*
    $product_review
    $addons
*}

<blockquote class="ty-product-review-post-message ty-blockquote" id="post_{$product_review.product_review_id}">

    {if $addons.product_reviews.review_fields === "advanced"}

        {include file="addons/product_reviews/views/product_reviews/components/post_message_section.tpl"
            message_title=__("product_reviews.advantages")
            message_body=$product_review.message.advantages
        }

        {include file="addons/product_reviews/views/product_reviews/components/post_message_section.tpl"
            message_title=__("product_reviews.disadvantages")
            message_body=$product_review.message.disadvantages
        }

    {/if}

    {include file="addons/product_reviews/views/product_reviews/components/post_message_section.tpl"
        message_title=__("comment")
        message_body=$product_review.message.comment
    }

</blockquote>
