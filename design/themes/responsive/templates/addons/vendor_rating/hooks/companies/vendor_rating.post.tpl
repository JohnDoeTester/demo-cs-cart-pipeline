{if $show_vendor_rating}
    {include file="addons/vendor_rating/components/relative_vendor_rating.tpl" 
        rating=$company.relative_vendor_rating
    }
{/if}