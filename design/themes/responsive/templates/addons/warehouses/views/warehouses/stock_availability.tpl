<div data-ca-warehouses-stock-availability-product-id="{$product_id}"
     id="warehouses_stock_availability_{$product_id}">
    {if $show_stock_availability}
        <div class="ty-warehouses-shipping__wrapper">
            <div class="ty-warehouses-shipping__title">
                {__("warehouses.product_availability")}:
                <div class="ty-warehouses__geolocation">
                    <span class="ty-warehouses__geolocation__opener">
                        {include_ext file="common/icon.tpl" class="ty-icon-location-arrow"}
                        <span class="ty-warehouses__geolocation__opener-text">
                                <span class="ty-warehouses__geolocation__location">{$location.city}</span>
                        </span>
                    </span>
                </div>
            </div>
        </div>
    {/if}

    {if $show_stock_availability || $warn_about_delay}
        <div class="ty-warehouses-shipping__wrapper">
            {include file="addons/warehouses/components/product_availability.tpl"
                in_stock_stores_count = $in_stock_stores_count
                availbe_stores_count = $availbe_stores_count
                product_id = $product_id
            }
            {include file="addons/warehouses/components/shipping_delay.tpl"
                warn_about_delay = $warn_about_delay
                shipping_delay = $shipping_delay
            }
        </div>
    {/if}
<!--warehouses_stock_availability_{$product_id}--></div>

{script src="js/addons/warehouses/stock_availability.js"}
