{*
    $product_review
*}
{if $product_review}
    <article class="ty-product-review-post">

        {include file="addons/product_reviews/views/product_reviews/components/post_customer.tpl"
            product_review=$product_review
        }

        <section class="ty-product-review-post__content ty-dialog-caret">

            {hook name="product_reviews:post_content"}

                {include file="addons/product_reviews/views/product_reviews/components/post_header.tpl"
                    product_review=$product_review
                }

                {include file="addons/product_reviews/views/product_reviews/components/post_message.tpl"
                    product_review=$product_review
                }

                {include file="addons/product_reviews/views/product_reviews/components/post_footer.tpl"
                    product_review=$product_review
                }

            {/hook}

        </section>

        {include file="addons/product_reviews/views/product_reviews/components/post_vendor_reply.tpl"
            product_review=$product_review
        }
    </article>
{/if}
