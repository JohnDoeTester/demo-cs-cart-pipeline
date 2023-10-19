<div class="object-selector-result-wrapper">
    <span class="object-selector-result">
        {include 
            file="common/image.tpl" 
            image=$product.main_pair.icon|default:$product.main_pair.detailed 
            image_id=$product.main_pair.image_id 
            image_width=$settings.Thumbnails.product_admin_mini_icon_width 
            image_height=$settings.Thumbnails.product_admin_mini_icon_height 
            image_css_class="products-list__image--fix"
            no_image_css_class="products-list__image--fix"
            show_detailed_link=false
        }
        <div class="product-list__name">
            <span class="object-selector-result__text">
                <span class="object-selector-result__body product-list__name-body">{$product.product}</span>
            </span>
            <div class="product-list__labels product-list__labels--secondary">
                {hook name="products:product_additional_info"}
                    <div class="product-code">
                        <span class="product-code__label">{$product.product_code}</span>
                    </div>
                {/hook}
            </div>
        </div>
        <div class="product-list__price">
            {include file="common/price.tpl" value=$product.price}
        </div>
    </span>
</div>
