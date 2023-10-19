{*
    $rating                         number                              Rating
    $average_rating                 number                              Average rating
    $size                           string                              Size
*}

{if $rating > 0}
    {$accurate_rating = $rating|round:1}

    <section>
        {include file="views/addons/components/rating/stars.tpl"
            rating=$rating
            size=$size
        }

        <div>
            <strong class="text-larger">
                {$accurate_rating}
            </strong>
            <span>
                {__("addons.out_of_five")}
            </span>
        </div>
    </section>
{/if}
