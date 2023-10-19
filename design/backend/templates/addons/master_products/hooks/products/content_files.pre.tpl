{if $runtime.company_id && !$product_data.company_id}
    {$hide_for_vendor = true scope = parent}
{/if}
