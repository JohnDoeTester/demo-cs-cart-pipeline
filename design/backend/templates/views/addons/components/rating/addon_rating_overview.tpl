{*
    $average_rating                 number                              Average rating
    $total_addon_reviews            int                                 Total addon reviews
    $ratings_stats                  array                               Ratings stats
*}

{if $total_addon_reviews}
    <section class="cs-addons-rating-addon-rating-overview well">
        {include file="views/addons/components/rating/stars_with_text.tpl"
            rating=$average_rating
            size="xlarge"
        }

        {include file="views/addons/components/rating/stars_details.tpl"
            ratings_stats=$ratings_stats
        }

        {include file="views/addons/components/rating/total_reviews.tpl"
            total_addon_reviews=$total_addon_reviews
        }

    </section>
{/if}
