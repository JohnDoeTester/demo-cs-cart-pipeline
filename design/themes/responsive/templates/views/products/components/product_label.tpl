{if $label_href}
    {* Link label *}
    <a href="{$label_href}" {$label_extra} class="ty-product-labels__item ty-product-labels__item--link {if $label_mini}ty-product-labels__item--mini{/if} {if $label_rounded}ty-product-labels__item--rounded{/if} {if $label_meta}{$label_meta}{/if}">
        <div class="ty-product-labels__content">{if $label_icon}{include_ext file="common/icon.tpl" class="`$label_icon` ty-product-labels__icon"}{/if}{$label_text}</div>
    </a>
{else}
    {* Simple label *}
    <div {$label_extra} class="ty-product-labels__item {if $label_mini}ty-product-labels__item--mini{/if} {if $label_rounded}ty-product-labels__item--rounded{/if} {if $label_meta}{$label_meta}{/if}">
        <div class="ty-product-labels__content">{if $label_icon}{include_ext file="common/icon.tpl" class="`$label_icon` ty-product-labels__icon"}{/if}{$label_text}</div>
    </div>
{/if}
