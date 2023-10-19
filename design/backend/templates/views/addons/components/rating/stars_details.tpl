{*
    $ratings_stats                  array                               Ratings stats
    $quantity                       int                                 Quantity
    $rating                         array                               Rating
    $without_empty_stars            bool                                Without empty stars
*}

{if $ratings_stats}    
    <section class="cs-addons-rating-stars-details">

        {foreach $ratings_stats as $quantity => $rating}
            <div class="cs-addons-rating-stars-details__row">

                <div class="cs-addons-rating-stars-details__quantity">
                    {include file="views/addons/components/rating/stars.tpl"
                        rating=$quantity
                        size="small"
                        type="secondary"
                        without_empty_stars=true
                        flip=true
                    }
                </div>

                <progress
                    class="cs-addons-rating-stars-details__line"
                    max="100"
                    value="{$rating.percentage}"
                >{$rating.percentage}</progress>


                <div class="cs-addons-rating-stars-details__percentage">
                    {$rating.percentage|round}%
                </div>

            </div>
        {/foreach}

    </section>
{/if}
