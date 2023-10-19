{** block-description:buy_together **}

{script src="js/tygh/exceptions.js"}

{if $chains}
    {if !$config.tweaks.disable_dhtml && !$no_ajax}
        {assign var="is_ajax" value=true}
    {/if}
    
    {foreach from=$chains key="key" item="chain"}
        <h3 class="ty-buy-together__header ty-subheader">{$chain.name}</h3>

        {include file="addons/buy_together/components/buy_together_chain_form.tpl"}
    {/foreach}
{/if}
