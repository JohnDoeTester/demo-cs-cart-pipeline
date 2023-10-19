{if $item_ids}
    {foreach from=$item_ids item="product" key="product_id"}
        {if $display}
            {capture name="product_options"}
                {assign var="prod_opts" value=$product.product_id|fn_get_product_options}
                {if $prod_opts && $product.aoc == "{"YesNo::YES"|enum}"}
                    <span>{__("options")}: </span>&nbsp;{__("any_option_combinations")}
                {elseif $product.product_options}
                    {if $product.product_options_value}
                        {include file="common/options_info.tpl" product_options=$product.product_options_value}
                    {else}
                        {$product_options = ($get_option_info) ? ($product.product_options|fn_get_selected_product_options_info) : $product.product_options}
                        {include file="common/options_info.tpl" product_options=$product_options}
                    {/if}
                {/if}
                {if $product.any_variation}
                    {__("product_bundles.any_variation")}
                    <input type="hidden" name="item_data[products][{$product_id}][any_variation]" value="{"YesNo::YES"|enum}">
                {/if}
            {/capture}
        {/if}
        {$product_name = $product.product_id|fn_get_product_name|default:__("deleted_product")}
        {include file="pickers/products/js.tpl"
            product=$product_name
            root_id=$data_id
            delete_id=$product_id
            input_name="`$input_name`[`$product_id`]"
            amount=$product.amount
            amount_input="text"
            type="options"
            options=$smarty.capture.product_options
            options_array=$product.product_options
            product_id=$product.product_id
            product_info=$product
            aoc=$product.aoc
        }
    {/foreach}
{/if}
