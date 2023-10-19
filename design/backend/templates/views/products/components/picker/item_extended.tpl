{$show_secondary = $show_secondary|default:false}
{$show_simple_product = $show_simple_product|default:false}
{$show_in_stock = $show_in_stock|default:false}

{literal}
    ${data.image && data.image.image_path
    ? `<img src="${data.image.image_path}" width="${data.image.width}" height="${data.image.height}" alt="${data.image.alt}" class="object-picker__products-image"/>`
        : `<div class="no-image object-picker__products-image object-picker__products-image--no-image" style="width: ${data.image_width}px; height: ${data.image_height}px;"> <span class="cs-icon glyph-image"></span></div>`
    }
{/literal}
<div class="object-picker__products-content">
    <div class="object-picker__products-main">
        {hook name="products:object_picker_products_main"}
            <div class="object-picker__products-name">
                {if $type === "result"}
                    <div class="object-picker__name-content">
                        {$title_pre} 
                        {literal}
                            ${data.product ? data.product : data.text}
                        {/literal} 
                        {$title_post}
                    </div>
                {else}
                    <a href="{literal}${data.url}{/literal}" class="object-picker__products-name-content object-picker__products-name-content--link">{$title_pre} {literal}${data.product}{/literal} {$title_post}</a>
                {/if}
            </div>

            <div class="object-picker__products-labels">
                {hook name="products:object_picker_products_additional_info"}
                    {literal}
                        ${data.product_code
                            ? `<div class="object-picker__products-product-code"><span class="object-picker__products-product-code-label">${data.product_code}</span></div>`
                            : ``
                        }
                    {/literal}
                {/hook}
            </div>

            {literal}
                ${data.company_name
                    ? `<div class="object-picker__products-company-name">${data.company_name}</div>`
                    : ``
                }
            {/literal}
        {/hook}
    </div>
    {if !$show_simple_product || $show_in_stock || $show_secondary}
    <div class="object-picker__products-secondary">
        {hook name="products:object_picker_products_secondary"}
            {if !$show_simple_product}
            {literal}
                ${data.price_formatted
                    ? `<div class="object-picker__products-price">${data.price_formatted}</div>`
                    : ``
                }
            {/literal}
            {/if}
            {if $show_in_stock}
            {literal}
                ${data.amount_text
                    ? `<div class="object-picker__products-in-stock">${data.amount_text}</div>`
                    : ``
                }
            {/literal}
            {/if}
        {/hook}
    </div>
    {/if}
</div>