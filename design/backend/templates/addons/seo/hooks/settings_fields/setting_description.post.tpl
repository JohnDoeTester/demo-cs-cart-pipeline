{if $item.name === "frontend_default_language" && $show_language_warning}
    <div class="text-warning">
        <strong>{__("warning")}!</strong>
        {if "ULTIMATE"|fn_allowed_for}
            {__("seo.storefront_frontend_default_language_warning", ["[link]" => "addons.update?addon=seo"|fn_url]) nofilter}
        {elseif $is_default_storefront_affected}
            {__("seo.default_storefront_frontend_default_language_warning", ["[link]" => "addons.update?addon=seo"|fn_url]) nofilter}
        {else}
            {__("seo.secondary_storefront_frontend_default_language_warning", ["[link]" => "addons.update?addon=seo"|fn_url]) nofilter}
        {/if}
    </div>
{/if}