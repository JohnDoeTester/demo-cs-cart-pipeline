{$show_simple_product = $show_simple_product|default:false}

{if !$show_simple_product}
{literal}
    ${data.image && data.image.image_path
        ? `<img src="${data.image.image_path}" width="30" height="30" alt="${data.image.alt}" class="object-picker__products-image"/>
        ` : `<div class="no-image object-picker__products-image object-picker__products-image--no-image" style="width: 30px; height: 30px;"> <span class="cs-icon glyph-image"></span></div>`
    }
{/literal}
{/if}

<div class="object-picker__products-main">
    <div class="object-picker__products-name">
        <div class="object-picker__products-name-content">
            <span class="object-picker__products-name-text">
                {$title_pre} 
                {literal}
                    ${data.product ? data.product : data.text}
                {/literal} 
                {$title_post}
            </span>
        </div>
    </div>
</div>