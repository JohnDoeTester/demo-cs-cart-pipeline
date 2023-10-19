{if $product_data}
{$SIDEBAR_CONTENT_WIDTH = "192"}

<div class="sidebar-row sidebar-product">
    <h6>{__("product_details_sidebar")}</h6>
    <ul class="unstyled">
        {hook name="common:sidebar_product"}
            <li>
                <p>
                    {include file="common/image.tpl"
                        image=$product_data.main_pair.icon|default:$product_data.main_pair.detailed
                        image_id=$product_data.main_pair.image_id
                        image_width=$SIDEBAR_CONTENT_WIDTH
                        image_height=$SIDEBAR_CONTENT_WIDTH
                        href="products.update?product_id=`$product_data.product_id`"|fn_url
                        show_detailed_link=true
                    }
                </p>
            </li>
            <li>
                {if fn_check_permissions("products", "update", "admin")}
                    <a href="{"products.update?product_id=`$product_data.product_id`"|fn_url}" title="{$product_data.product}">
                        {$product_data.product}
                    </a>
                {else}
                    <span>
                        {$product_data.product}
                    </span>
                {/if}
            </li>
            <li>
                {hook name="common:sidebar_product_code"}
                    <span class="muted">
                        {$product_data.product_code}
                    </span>
                {/hook}
            </li>
            <li>
                {include file="common/price.tpl" value=$product_data.price}
            </li>
        {/hook}
    </ul>
</div>
{/if}