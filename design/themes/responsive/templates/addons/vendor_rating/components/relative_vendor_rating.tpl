{$show_icon = true}
{if $addons.vendor_rating.bronze_rating_lower_limit === $addons.vendor_rating.silver_rating_lower_limit
&& $addons.vendor_rating.silver_rating_lower_limit === $addons.vendor_rating.gold_rating_lower_limit
&& $addons.vendor_rating.gold_rating_lower_limit === "0"}
    {$show_icon = false}
{/if}
{if $show_icon}
    <span class="ty-vendor-rating">
        {if $rating >= $addons.vendor_rating.bronze_rating_lower_limit && $rating < $addons.vendor_rating.silver_rating_lower_limit}
            {include_ext file="common/icon.tpl"
                class="ty-vendor-rating-icon ty-vendor-rating-icon--bronze"
                title="{__('vendor_rating.vendor_rating')}: `$rating`%"
            }
        {elseif $rating >= $addons.vendor_rating.silver_rating_lower_limit && $rating < $addons.vendor_rating.gold_rating_lower_limit}
            {include_ext file="common/icon.tpl"
                class="ty-vendor-rating-icon ty-vendor-rating-icon--silver"
                title="{__('vendor_rating.vendor_rating')}: `$rating`%"
            }
        {elseif $rating >= $addons.vendor_rating.gold_rating_lower_limit}
            {include_ext file="common/icon.tpl"
                class="ty-vendor-rating-icon ty-vendor-rating-icon--gold"
                title="{__('vendor_rating.vendor_rating')}: `$rating`%"
            }
        {/if}
    </span>
{/if}
