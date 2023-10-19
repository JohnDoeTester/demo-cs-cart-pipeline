{*
    $average_rating
    $total_product_reviews
*}

{if $total_product_reviews}
    <section class="ty-product-review-product-rating">

        {include file="addons/product_reviews/views/product_reviews/components/product_rating_overview.tpl"
            average_rating=$average_rating
            total_product_reviews=$total_product_reviews
        }

        {include file="addons/product_reviews/views/product_reviews/components/product_stars_details.tpl"
            ratings_stats=$ratings_stats
            total_product_reviews=$total_product_reviews
        }

    </section>
{/if}
