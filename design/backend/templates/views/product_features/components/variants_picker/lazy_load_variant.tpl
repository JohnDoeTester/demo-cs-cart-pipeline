{if $name}
    <div class="object-picker__selection-product-feature">
        {if $template_type === "color"}
            {include file="views/product_features/components/variants_picker/item_color.tpl"
                help=false
                enable_color=$enable_color
                assign="item"
            }
            {$item = $item|replace:'${data.color}':$color}
        {elseif $template_type === "image"}
            {include file="views/product_features/components/variants_picker/item_image.tpl"
                enable_image=false
                assign="item"
            }
        {else}
            {include file="views/product_features/components/variants_picker/item.tpl"
                assign="item"
            }
        {/if}

        {$item|replace:'${data.name}':$name nofilter}
    </div>
{/if}
