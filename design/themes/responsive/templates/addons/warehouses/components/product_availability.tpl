{*
int $in_stock_stores_count
int $available_stores_count
int $product_id
*}
{if $in_stock_stores_count || $available_stores_count}
    <div class="ty-warehouses-shipping__item">
        <div class="ty-warehouses-shipping__label">
            <a class="ty-warehouses-shipping__link cm-external-click"
               data-ca-warehouses-href="{fn_url("products.view?product_id={$product_id}&selected_section=availability_in_stores#availability_in_stores")}"
               data-ca-warehouses-tab-anchor="#availability_in_stores"
               data-ca-warehouses-tab-selector=".ty-tabs__item#availability_in_stores"
               data-ca-warehouses-dialog-opener-selector=".cm-dialog-opener#availability_in_stores"
               data-ca-external-click-id="availability_in_stores"
            >
                {include_ext file="common/icon.tpl" class="ty-icon-cart"}
                <span class="ty-warehouses-shipping__link-text">
                    {if $in_stock_stores_count}
                        {__("warehouses.product_in_stock")}
                    {else}
                        {__("warehouses.product_available_if_ordered")}
                    {/if}
                </span>
            </a>
            <div class="ty-warehouses-shipping__value">
                {if $in_stock_stores_count}
                    {__("warehouses.in_n_stores", [
                        $in_stock_stores_count
                    ])}
                {else}
                    {__("warehouses.in_n_stores", [
                        $available_stores_count
                    ])}
                {/if}
            </div>
        </div>
    </div>
{/if}
