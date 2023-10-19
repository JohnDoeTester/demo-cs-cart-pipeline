{*
    $product_id
    $locate_to_product_review_tab
*}

<section class="ty-product-review-write-review">

    <h4 class="ty-product-review-write-review__title">
        {__("product_reviews.review_this_product")}
    </h4>

    <div class="ty-product-review-write-review__description">
        <p>{__("product_reviews.review_this_product_description")}</p>
    </div>

    {include
        file="addons/product_reviews/views/product_reviews/components/write_product_review_button.tpl"
        name=__("product_reviews.write_review")
        product_id=$product_id
        locate_to_product_review_tab=$locate_to_product_review_tab
        but_meta="ty-btn__secondary"
    }

</section>
