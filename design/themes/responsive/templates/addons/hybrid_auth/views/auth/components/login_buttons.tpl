{hook name="hybrid_auth:login_buttons"}
    {if !isset($redirect_url)}
        {$redirect_url = $config.current_url}
    {/if}
    {__("hybrid_auth.social_login")}:
    <p class="ty-text-center">{$smarty.capture.hybrid_auth nofilter}
        {strip}
            <input type="hidden" name="redirect_url" value="{$redirect_url}" />
            {foreach $providers_list as $provider_data}
                {if $provider_data.status === "ObjectStatuses::ACTIVE"|enum}
                    <a class="cm-login-provider ty-hybrid-auth__icon" data-idp="{$provider_data.provider_id}" data-provider="{$provider_data.provider}">
                        <img src="{$provider_data.icon}" title="{$provider_data.provider}" alt="{$provider_data.provider}" />
                    </a>
                {/if}
            {/foreach}
        {/strip}
    </p>
{/hook}
