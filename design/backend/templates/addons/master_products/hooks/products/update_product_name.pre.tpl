{if $product_data.master_product_id}
    <div class="control-group">
        <label for="elm_parent_product_{$id}"
               class="control-label"
        >{__("master_products.master_product")}:</label>
        <div class="controls" id="elm_parent_product_{$id}">
            <a class="shift-input"
               href="{"products.update?product_id=`$product_data.master_product_id`"|fn_url}"
            >{$product_data.product}</a>
        </div>
    </div>
{/if}