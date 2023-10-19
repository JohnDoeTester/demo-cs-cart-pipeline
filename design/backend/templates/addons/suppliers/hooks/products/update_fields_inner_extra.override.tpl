{if $field == "supplier_id"}
    {if $product.product_id}
        {assign var="result_id" value="field_`$field`_`$product.product_id`_"}
    {else}
        {assign var="result_id" value="field_`$field`_0_"}
    {/if}
    {include file="addons/suppliers/views/suppliers/components/picker/picker.tpl" 
        input_name="products_data[`$product.product_id`][`$field`]"
        item_ids=[$product.$field]
        meta="span3"
    }
{/if}