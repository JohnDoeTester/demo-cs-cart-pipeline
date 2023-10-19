{$price_per_unit = "price_per_unit_`$obj_id`"}
{if $smarty.capture.$price_per_unit|trim}
    {$smarty.capture.$price_per_unit nofilter}
{/if}
