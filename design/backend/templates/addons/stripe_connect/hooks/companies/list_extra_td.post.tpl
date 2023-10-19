<td class="row-status {if $company.stripe_connect_account_id}text-success{else}muted{/if}">
    {if $company.stripe_connect_account_id}
        {__("stripe_connect.on")}
    {else}
        –
    {/if}
</td>