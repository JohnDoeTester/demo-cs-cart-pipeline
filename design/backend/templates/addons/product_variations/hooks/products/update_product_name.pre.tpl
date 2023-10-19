{if $parent_product_data}
    <div class="control-group">
        <div class="controls">
            <p>
                {__("product_variations.variation_of_product", [
                    "[url]" => "products.update?product_id={$product_data.parent_product_id}"|fn_url,
                    "[product]" => $parent_product_data.variation_name
                ]) nofilter}
            </p>
        </div>
    </div>
{/if}