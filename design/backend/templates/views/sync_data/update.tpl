{hook name="sync_data:update"}
    {if $provider_data.update_template}
        {include file=$provider_data.update_template}
    {else}
        {include file="views/sync_data/components/no_update_template.tpl"}
    {/if}
{/hook}