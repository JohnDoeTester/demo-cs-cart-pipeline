{if !$product.is_negative_amount_allowed}
    {$allow_negative_amount="YesNo::NO"|enum scope=parent}
{/if}