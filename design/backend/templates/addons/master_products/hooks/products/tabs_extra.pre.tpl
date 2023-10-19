{if $runtime.company_id && !$product_data.company_id}
    {$hide_for_vendor=true scope=parent}
    {$hide_inputs="cm-hide-inputs" scope=parent}
    {$edit_link_text=__("view") scope=parent}
    {$link_text=__("view") scope=parent}
    {$skip_delete=true scope=parent}
{/if}