{$protocol = ($settings.Security.secure_storefront === "YesNo::YES"|enum) ? "https" : "http"}
{if $provider_data.storefront_ids}
    {$storefront_ids = $provider_data.storefront_ids}
{else}
    {$storefront_ids = $all_storefront_ids}
{/if}
{foreach $storefront_ids as $storefront_id}
<div class="control-group">
    {include file="common/widget_copy.tpl"
        widget_copy_code_text=fn_url("?storefront_id={$storefront_id}", "SiteArea::STOREFRONT"|enum, $protocol)
    }
</div>
{/foreach}
