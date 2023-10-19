{*
    $average_rating                 number                              Average rating
    $total_product_reviews          int                                 Total product reviews
    $ratings_stats                  array                               Ratings stats
    $product_data                   array                               Product data
*}

{if $total_product_reviews}
    <section class="cs-product-reviews-rating-product-rating-overview well">
        {include file="addons/product_reviews/views/product_reviews/components/rating/stars_with_text.tpl"
            rating=$average_rating
            size="xlarge"
        }

        {include file="addons/product_reviews/views/product_reviews/components/rating/stars_details.tpl"
            ratings_stats=$ratings_stats
        }

        {include file="addons/product_reviews/views/product_reviews/components/rating/total_reviews.tpl"
            total_product_reviews=$total_product_reviews
            product_id=$product_data.product_id
            meta="muted"
        }

    </section>
{/if}
