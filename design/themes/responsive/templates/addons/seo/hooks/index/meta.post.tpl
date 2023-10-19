{if !fn_seo_is_indexed_page($smarty.request)}
    <meta name="robots" content="noindex" />
{else}
    {if $seo_canonical.current}
        <link rel="canonical" href="{$seo_canonical.current}" />
    {/if}
    {if $seo_canonical.prev}
        <link rel="prev" href="{$seo_canonical.prev}" />
    {/if}
    {if $seo_canonical.next}
        <link rel="next" href="{$seo_canonical.next}" />
    {/if}
{/if}

{foreach $seo_alt_hreflangs_list as $seo_alt_lang_code => $seo_alt_lang}
    <link title="{$seo_alt_lang.name}" dir="{$seo_alt_lang.direction}" type="text/html" rel="alternate" hreflang="{$seo_alt_lang_code}" href="{$seo_alt_lang.href}" />
{/foreach}

{foreach $schema_org_markup_items as $markup_item}
    <script type="application/ld+json">
        {$pretty_print = 0}
        {if defined("DEVELOPMENT") && $smarty.const.DEVELOPMENT}
            {$pretty_print = constant("JSON_PRETTY_PRINT")}
        {/if}
        {json_encode($markup_item, $pretty_print) nofilter}
    </script>
    {$is_json_schema_org_markup_displayed = true scope="root"}
{/foreach}