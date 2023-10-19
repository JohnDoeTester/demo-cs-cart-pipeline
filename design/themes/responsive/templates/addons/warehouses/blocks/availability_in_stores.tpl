{** block-description:warehouses.product_availability **}

{$show_store_groups = count($items) > 1}
<div class="ty-warehouses-stores-search">
    {hook name="warehouses:stores_search"}
        <div class="ty-warehouses-stores-search__query-wrapper">
            <input type="text"
                   class="ty-warehouses-stores-search__query"
                   data-ca-warehouses-stores-list-selector="#warehouses_list_items_{$block.block_id}"
                   data-ca-warehouses-not-found-selector="#warehouses_list_items_{$block.block_id}_not_found"
                   data-ca-warehouses-view-selector-on="#warehouses_list_{$block.block_id}"
                   data-ca-warehouses-view-selector-off="#warehouses_map_{$block.block_id}"
                   placeholder="{__("warehouses.search_store")}"
            />
        </div>
    {/hook}
</div>
{hook name="warehouses:stores_list"}
    <div class="ty-warehouses-stores-list"
         id="warehouses_list_{$block.block_id}"
         data-ca-warehouses-view-selector="#warehouses_view_selector_{$block.block_id}_list"
    >
        <div class="ty-warehouses-stores-list__header">
            <div class="ty-warehouses-store__header-item ty-warehouses-store__header-item--name-wrapper">
                {__("warehouses.store_and_address")}
            </div>
            <div class="ty-warehouses-store__header-item ty-warehouses-store__header-item--open-hours">
                {__("store_locator.work_time")}
            </div>
            <div class="ty-warehouses-store__header-item ty-warehouses-store__header-item--phone">
                {__("warehouses.phone")}
            </div>
            <div class="ty-warehouses-store__header-item ty-warehouses-store__header-item--availability">
                {__("availability")}
            </div>
        </div>
        <div class="ty-warehouses-stores-list__items" id="warehouses_list_items_{$block.block_id}">
            {foreach $items as $group_id => $stores_group}
                <div class="ty-warehouses-store__group" id="warehouses_list_items_{$block.block_id}_group_{$group_id}">
                    {if $show_store_groups}
                        <div class="ty-warehouses-store__group-name">
                            {$stores_group.name}
                        </div>
                    {/if}
                    {foreach $stores_group.items as $store}
                        <div class="ty-warehouses-stores-list__item"
                             data-ca-warehouses-store-group-selector="#warehouses_list_items_{$block.block_id}_group_{$group_id}"
                        >
                            <div class="ty-warehouses-store__name-wrapper">
                                <a class="ty-warehouses-store__name"
                                   data-ca-warehouses-marker-selector="#warehouses_marker_{$block.block_id}_{$store.store_location_id}"
                                   data-ca-warehouses-view-selector-off="#warehouses_list_{$block.block_id}"
                                   data-ca-warehouses-view-selector-on="#warehouses_map_{$block.block_id}"
                                   data-ca-warehouses-map-selector="#warehouses_map_{$block.block_id}_map"
                                >
                                    {$store.name}
                                </a>
                                <div class="ty-warehouses-store__address">
                                    {$store.pickup_address}
                                </div>
                            </div>
                            <div class="ty-warehouses-store__open-hours">
                                {$store.pickup_time}
                            </div>
                            <div class="ty-warehouses-store__phone">
                                {$store.pickup_phone}
                            </div>
                            <div class="ty-warehouses-store__availability">
                                {if $store.amount && $store.amount > 0}
                                    {if $settings.Appearance.in_stock_field === "YesNo::YES"|enum}
                                        {__("availability")}: {$store.amount}
                                    {else}
                                        <span class="ty-qty-in-stock">{__("in_stock")}</span>
                                    {/if}
                                {elseif $store.is_available && $store.shipping_delay}
                                    {__("warehouses.shipping_delay.description.short", [
                                        "[shipping_delay]" => $store.shipping_delay
                                    ])}
                                {elseif $store.is_available}
                                    {__("warehouses.product_available_if_ordered")}
                                {else}
                                    {__("text_out_of_stock")}
                                {/if}
                            </div>
                        </div>
                    {/foreach}
                </div>
            {/foreach}
        </div>
        <div class="ty-warehouses-stores-list__not-found hidden" id="warehouses_list_items_{$block.block_id}_not_found">
            <p class="ty-no-items">
                {__("warehouses.no_matching_stores_found")}
            </p>
        </div>
    </div>
{/hook}

{script src="js/addons/warehouses/search.js"}
