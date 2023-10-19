{if $company_id}
    {$quick_view_url = $quick_view_url|fn_link_attach:"is_microstore=Y" scope=parent}
{/if}