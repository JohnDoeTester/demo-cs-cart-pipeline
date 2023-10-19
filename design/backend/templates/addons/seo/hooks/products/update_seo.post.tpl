{if $view_uri && $runtime.company_id && "ULTIMATE"|fn_allowed_for || "MULTIVENDOR"|fn_allowed_for}
{$protocol = ($settings.Security.secure_storefront === "YesNo::YES"|enum) ? "https" : "http"}
{include file="common/subheader.tpl" title=__("seo.rich_snippets") target="#acc_addon_seo_richsnippets" meta="mobile-hidden"}
<div id="acc_addon_seo_richsnippets" class="collapsed in mobile-hidden">

<div class="seo-rich-snippet">

    <h3>
        {$page_title = $product_data.page_title|default:$product_data.product}
        <a class="srs-title cm-seo-srs-title" href="{"products.view?product_id=`$product_data.product_id`"|fn_url:"C":$protocol}" target="_blank">
            {$page_title|strip_tags|truncate:$page_title_seo_length:"..."}
        </a>
    </h3>
    <div>
        <div>
            <cite class="srs-url">{""|fn_url:"C":$protocol}</cite>
        </div>

        <div class="srs-price">{strip}
            {hook name="products:seo_snippet_attributes"}
            {include file="common/price.tpl" value=$product_data.price span_id="elm_seo_srs_price"} - {__("in_stock")}
            {/hook}
        {/strip}</div>

        {$description = $product_data.meta_description|default:$product_data.full_description|default:$product_data.short_description}
        <span class="srs-description cm-seo-srs-description">
            {$description|strip_tags|truncate:$description_seo_length:"..." nofilter}
        </span>
    </div>
</div>

</div>
{/if}
