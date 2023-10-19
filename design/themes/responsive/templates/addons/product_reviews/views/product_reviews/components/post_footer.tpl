{*
    $product
    $product_review
*}

{if $product_review}
    <footer class="ty-product-review-post-footer">

        <div class="ty-product-review-post-footer__primary">
            {include file="addons/product_reviews/views/product_reviews/components/post_images.tpl"
                images=$product_review.images
                preview_id=$product.product_id|uniqid
            }
        </div>
        
        <div class="ty-product-review-post-footer__secondary">
            {include file="addons/product_reviews/views/product_reviews/components/post_votes.tpl"
                product_review_id=$product_review.product_review_id
                vote_up=$product_review.helpfulness.vote_up
                vote_down=$product_review.helpfulness.vote_down
            }
        </div>
    </footer>
{/if}
