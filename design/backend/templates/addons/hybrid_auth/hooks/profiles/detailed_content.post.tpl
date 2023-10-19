{if $providers_list}
{include file="common/subheader.tpl" title=__("hybrid_auth.link_provider")}

<div id="hybrid_providers">
{foreach $providers_list as $provider_id => $provider_data}

    <span class="hybrid-auth-icon{if !in_array($provider_id, $linked_providers)} link-unlink-provider{/if}">
        <img src="{$provider_data.icon}" title="{$provider_data.provider}" />
    </span>

{/foreach}
<!--hybrid_providers--></div>
{/if}