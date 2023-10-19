{if !$product_type->isFieldAvailable("detailed_image")}
    <div class="control-group">
        <label class="control-label">{__("images")}:</label>
        <div class="controls">
            {include
                file="common/form_file_uploader.tpl"
                existing_pairs=(($product_data.main_pair) ? [$product_data.main_pair] : []) + $product_data.image_pairs|default:[]
                file_name="file"
                image_pair_types=['N' => 'product_add_additional_image', 'M' => 'product_main_image', 'A' => 'product_additional_image']
                allow_update_files=false
            }
        </div>
    </div>
{/if}
