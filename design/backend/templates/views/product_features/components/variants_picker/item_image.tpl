{if $enable_image}
    {literal}
        ${data.image_url
        ? `<img src="${data.image_url}" width="30" height="30" alt="${data.name}" class="object-picker__product-feature-image"/>`
            : `<div class="no-image object-picker__product-feature-no-image" style="width: 30px; height: 30px;"><span class="cs-icon glyph-image"></span></div>`
        }
    {/literal}
{/if}
<div class="object-picker__product-feature-label">{$title_pre} {literal}${data.name}{/literal} {$title_post}</div>