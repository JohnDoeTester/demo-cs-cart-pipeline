{*
    $average_rating
    $total_product_reviews
    $obj_prefix
    $obj_id
    $button
    $link
    $locate_to_product_review_tab
    $product
*}

<section class="ty-product-review-product-rating-overview-short" id="product_review_product_rating_overview_short_{$obj_prefix}{$obj_id}">

    {include file="addons/product_reviews/views/product_reviews/components/product_reviews_stars.tpl"
        rating=$average_rating
        size="large"
        button=$button
        link=$link
    }

    {include file="addons/product_reviews/views/product_reviews/components/product_reviews_total_reviews.tpl"
        total_product_reviews=$total_product_reviews
        button=$button
        link=$link
    }

    {include
        file="addons/product_reviews/views/product_reviews/components/write_product_review_button.tpl"
        name=__("product_reviews.write_review")
        product_id=$product.product_id
        locate_to_product_review_tab=$locate_to_product_review_tab
        but_meta="ty-btn__text"
    }

<!--product_review_product_rating_overview_short_{$obj_prefix}{$obj_id}--></section>
