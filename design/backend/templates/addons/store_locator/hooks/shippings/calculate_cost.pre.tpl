{if $store_locator_shipping && $shipping.company_id == 0}
    {include file="views/companies/components/company_field.tpl"
        name="calculate_data[company_id]"
        id="company_id_{$id}"
    }
{/if}