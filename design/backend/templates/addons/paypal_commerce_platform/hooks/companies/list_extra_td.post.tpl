<td class="row-status">
    <span class="paypal-commerce-platform__account">
        {if $company.paypal_commerce_platform_account_id}
            {$company.paypal_commerce_platform_account_id}
        {else}
            {__("paypal_commerce_platform.not_connected")}
        {/if}
    </span>
</td>