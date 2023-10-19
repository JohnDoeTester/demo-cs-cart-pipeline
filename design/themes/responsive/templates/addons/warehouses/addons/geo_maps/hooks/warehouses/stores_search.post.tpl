<div class="ty-warehouses-stores-search__view-wrapper">
    <div class="ty-warehouses-stores-search__view">
        <label>
            <input type="radio"
                   name="view"
                   checked
                   id="warehouses_view_selector_{$block.block_id}_list"
                   class="ty-warehouses-stores-search__view-selector"
                   data-ca-warehouses-view-selector-on="#warehouses_list_{$block.block_id}"
                   data-ca-warehouses-view-selector-off="#warehouses_map_{$block.block_id}"
            >
            {__("warehouses.stores_list")}
        </label>
    </div>
    <div class="ty-warehouses-stores-search__view">
        <label>
            <input type="radio"
                   name="view"
                   class="ty-warehouses-stores-search__view-selector"
                   id="warehouses_view_selector_{$block.block_id}_map"
                   data-ca-warehouses-view-selector-on="#warehouses_map_{$block.block_id}"
                   data-ca-warehouses-view-selector-off="#warehouses_list_{$block.block_id}"
            >
            {__("warehouses.stores_map")}
        </label>
    </div>
</div>
