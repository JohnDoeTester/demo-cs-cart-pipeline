{$hide_amount = $hide_amount|default:false}
{$checkbox_name = $checkbox_name|default:"add_products_ids"}

<tr id="picker_product_row_{$row_index}">
    {hook name="product_list:table_content"}
    {if $hide_amount}
        <td class="center" width="1%" data-th=""><input type="{if $show_radio}radio{else}checkbox{/if}" name="{$checkbox_name}[]" value="{$product.product_id}" class="cm-item mrg-check" id="checkbox_id_{$product.product_id}" /></td>
    {/if}
        <td data-th="{__("product_name")}">
            {hook name="product_list:product_data"}
                <input type="hidden" id="product_{$product.product_id}" value="{$product.product}" />

            {if $hide_amount}
                <label for="checkbox_id_{$product.product_id}">{$product.product nofilter}</label>
            {else}
                <div>{$product.product nofilter}</div>
            {/if}
                <div class="product-list__labels">
                    {hook name="products:product_additional_info"}
                    {if $product.product_code}
                        <div class="product-code">
                            <span class="product-code__label">{$product.product_code}</span>
                        </div>
                    {/if}
                    {/hook}
                    {include file="views/companies/components/company_name.tpl" object=$product show_hidden_input=true}
                </div>


            {if !$hide_options}
                {include file="views/products/components/select_product_options.tpl" id=$product.product_id product_options=$product.product_options name="product_data" show_aoc=$show_aoc additional_class=$additional_class}
            {/if}
            {/hook}
        </td>
    {if $show_price}
        <td class="cm-picker-product-options right" data-th="{__("price")}">{if !$product.price|floatval && $product.zero_price_action == "A"}<input class="input-medium" id="product_price_{$product.product_id}" type="text" size="3" name="product_data[{$product.product_id}][price]" value="" />{else}{include file="common/price.tpl" value=$product.price}{/if}</td>
    {/if}
    {if !$hide_amount}
        <td class="center nowrap cm-value-changer" width="5%">
            <div class="input-prepend input-append">
                <a class="btn no-underline strong increase-font cm-decrease">{include_ext file="common/icon.tpl" class="icon-minus"}</a>
                <input id="product_id_{$product.product_id}" type="text" value="{$default_product_amount|default:"0"}" name="product_data[{$product.product_id}][amount]" size="3" class="input-micro cm-amount"{if $product.qty_step > 1} data-ca-step="{$product.qty_step}"{/if} />
                <a class="btn no-underline strong increase-font cm-increase">{include_ext file="common/icon.tpl" class="icon-plus"}</a>
            </div>
        </td>
    {/if}
    {if $is_order_management}
        <td class="center nowrap" width="5%">
            <div>
                <a class="btn cm-process-items cm-submit cm-ajax cm-add-product" id="{$product.product_id}" title="{__("add_product")}" data-ca-dispatch="dispatch[order_management.add]" data-ca-check-filter="#picker_product_row_{$i}" data-ca-target-form="add_products">{include_ext file="common/icon.tpl" class="icon-share-alt" data=["data-ca-check-filter" => "#picker_product_row_`$i`"]}</a>
            </div>
        </td>
    {/if}
    {/hook}

    {hook name="product_list:table_columns"}{/hook}
<!--picker_product_row_{$row_index}--></tr>
