{if isset($product_warehouses_amount)}
    <div class="control-group">
        <label class="control-label" for="elm_in_stock">{__("in_stock")}:</label>
        <div class="controls">
            <input type="text" name="product_data[amount]" id="elm_in_stock" size="10" value="{$product_warehouses_amount}" disabled class="input-small" />
        </div>
    </div>
{/if}
