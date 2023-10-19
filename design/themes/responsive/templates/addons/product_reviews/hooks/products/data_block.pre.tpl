{*
    $show_rating
    $product
*}

{if $show_rating}

    {include file="addons/product_reviews/views/product_reviews/components/product_reviews_stars.tpl"
        rating=$product.average_rating
        link=true
        product=$product
    }

{/if}