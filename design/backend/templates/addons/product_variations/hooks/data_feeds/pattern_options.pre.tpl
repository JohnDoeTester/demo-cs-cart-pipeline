<div class="control-group">
    <label for="elm_datafeed_product_types" class="control-label">{__("product_type")}:</label>
    <div class="controls">
        <input type="hidden" name="datafeed_data[params][product_types]">
        <select name="datafeed_data[params][product_types][]" multiple="multiple" id="elm_datafeed_product_types">
            {foreach $product_types as $product_type => $product_type_name}
                <option {if $datafeed_data.params.product_types && in_array($product_type, $datafeed_data.params.product_types)}selected="selected"{/if} value="{$product_type}">{$product_type_name}</option>
            {/foreach}
        </select>
    </div>
</div>