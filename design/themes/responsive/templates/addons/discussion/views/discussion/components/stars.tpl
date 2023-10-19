{$link_target=$link_target|default:"auto"}
{if !($link_target === "auto"
    && ($runtime.controller == "products" || $runtime.controller == "companies")
    && $runtime.mode === "view"
    && !$product.average_rating)
}
    {$link_target = "url"}
{/if}

<span class="ty-nowrap ty-stars">
    {if $link}
        {if $link_target === "url"}
            <a href="{$link|fn_url}">
        {else}
            <a class="cm-external-click" data-ca-scroll="content_discussion" data-ca-external-click-id="discussion">
        {/if}
    {/if}

    {section name="full_star" loop=$stars.full}
        {include_ext file="common/icon.tpl"
            class="ty-icon-star ty-stars__icon"
        }
    {/section}

    {if $stars.part}
        {include_ext file="common/icon.tpl"
            class="ty-icon-star-half ty-stars__icon"
        }
    {/if}

    {section name="full_star" loop=$stars.empty}
        {include_ext file="common/icon.tpl"
            class="ty-icon-star-empty ty-stars__icon"
        }
    {/section}

    {if $link}
        </a>
    {/if}
</span>
