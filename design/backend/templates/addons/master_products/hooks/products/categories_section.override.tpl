{if $product_data.product_id
    && $product_data.master_product_id
    && !$product_type->isFieldAvailable("categories")
}
    {$multiple_categories = count($product_data.category_ids) > 1}

    {capture name="vendor_product_categories"}
        {foreach from=$product_data.category_ids|default:$request_category_id item="c_id"}
            {assign var="category_data" value=$c_id|fn_get_category_data:$smarty.const.CART_LANGUAGE:'':false:true:false:true}
            {if $multiple_categories}
                <p class="cm-js-item">
            {/if}
            {foreach from=$category_data.path_names key="path_id" item="path_name" name="path_names"}
                <a target="_blank" class="{if !$smarty.foreach.path_names.last}ty-breadcrumbs__a{else}ty-breadcrumbs__current{/if}" href="{"categories.update&category_id={$path_id}"|fn_url}">{$path_name}</a>{if !$smarty.foreach.path_names.last} / {/if}
            {/foreach}
            {if $multiple_categories}
                </p>
            {/if}
        {/foreach}
    {/capture}

    <div class="control-group">
        <label class="control-label">{__("categories")}</label>
        <div class="controls">
            <p>
                {$smarty.capture.vendor_product_categories nofilter}
            </p>
        </div>
    </div>
{/if}
