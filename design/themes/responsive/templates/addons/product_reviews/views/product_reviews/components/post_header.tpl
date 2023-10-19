{*
    $product_review
*}

<header class="ty-product-review-post-header">

    {include file="addons/product_reviews/views/product_reviews/components/product_reviews_stars.tpl"
        rating=$product_review.rating_value
    }

    {if $product_review.product_options}
        <div class="ty-product-review-post-header__product-options">
            {include file="common/options_info.tpl" product_options=$product_review.product_options no_block=true}
        </div>
    {/if}

</header>
