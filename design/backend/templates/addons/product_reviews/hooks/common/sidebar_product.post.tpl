{*
    $product_review                 array                               Product review
    $total_product_reviews          int                                 Total product reviews
*}

{if $product_data}
    <li>
        {include file="addons/product_reviews/views/product_reviews/components/rating/stars.tpl"
            rating=$product_data.average_rating
            total_product_reviews=$total_product_reviews
            link=true
        }
    </li>
    {if $total_product_reviews}
        <li>
            {if $create_new_product_reviews}
                {$product_review.product.product_id = $product_id}
            {/if}

            {include file="addons/product_reviews/views/product_reviews/components/rating/total_reviews.tpl"
                total_product_reviews=$total_product_reviews
                product_id=$product_review.product.product_id
                link=true
            }
        </li>
    {/if}
{/if}
