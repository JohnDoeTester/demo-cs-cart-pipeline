{*
    $average_rating
    $total_product_reviews
    $out_of_five
*}

{if $total_product_reviews}
    {$out_of_five = $out_of_five|default:true}

    <section class="ty-product-review-product-rating-overview">

        <div class="ty-product-review-product-rating-overview__primary">

            {include file="addons/product_reviews/views/product_reviews/components/product_reviews_stars.tpl"
                rating=$average_rating
                size="xlarge"
            }

            {if $average_rating}
                <div class="ty-product-review-product-rating-overview__rating">
                    <strong class="ty-product-review-product-rating-overview__rating-current">
                        {$average_rating|round:1}
                    </strong>
                    {if $out_of_five}
                        <span class="ty-product-review-product-rating-overview__rating-out-of-five">
                            {__("product_reviews.out_of_five")}
                        </span>
                    {/if}
                </div>
            {/if}

        </div>

        <div class="ty-product-review-product-rating-overview__secondary">

            {include file="addons/product_reviews/views/product_reviews/components/product_reviews_total_reviews.tpl"
                total_product_reviews=$total_product_reviews
                secondary=true
            }

        </div>
    </section>
{/if}
