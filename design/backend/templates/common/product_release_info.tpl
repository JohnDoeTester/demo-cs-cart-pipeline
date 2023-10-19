{strip}
{$env_provider = $env_provider|default:$app["product.env"]}
{$is_name_shown = $is_product_shown|default:true}
{$is_version_shown = $is_version_shown|default:true}
{$is_time_shown = $is_time_shown|default:true}

{$release_time = $env_provider->getReleaseTime()}
{$release_time.params["[month]"] = __($release_time.params["[month]"])}

<span class="product-release">
    {if $is_name_shown}
        <span class="product-release__name">{$env_provider->getProductName()}</span>
    {/if}
    {if $is_version_shown}
        <span class="product-release__version">v{$env_provider->getProductVersion()}</span>
    {/if}
    {if $is_time_shown}
        <span class="product-release__time">({__($release_time.message, $release_time.params)})</span>
    {/if}
</span>
{/strip}