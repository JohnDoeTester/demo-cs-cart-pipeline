<div class="products__features {if $selected_section !== "features"}hidden{/if} {if $hide_inputs}cm-hide-inputs{/if}" id="content_features">
    {script src="js/tygh/backend/products/products_update_features.js"}

    {$allow_add_feature = $allow_save_feature|default:true}

    {if fn_check_permissions("products", "update_feature", "admin", "POST")
        && fn_check_permissions("product_features", "quick_add", "admin", "POST")
    }
        <div class="control-toolbar cm-toggle-button">
            <div class="control-toolbar__btns">
                <div class="control-toolbar__btns-right">
                    {btn type="text"
                        id="add_feature_`$id`"
                        text=__("add_feature")
                        icon_first=true
                        icon="icon-plus"
                        class="btn cm-inline-dialog-opener cm-hide-with-inputs"
                        data=["data-ca-inline-dialog-container" => "product_features_quick_add_feature"]
                    }
                </div>
            </div>
            <div class="control-toolbar__panel">
                <div id="product_features_quick_add_feature"
                     data-ca-product-id="{$product_id}"
                     data-ca-target-id="products_update_features_content"
                     data-ca-return-url="{"products.get_features?product_id={$product_id}&items_per_page={$features_search.items_per_page}"|fn_url|escape:url}"
                     data-ca-inline-dialog-action-context="products_update_features"
                     data-ca-inline-dialog-url="{"product_features.quick_add?category_id={$product_data.main_category}&{http_build_query(["category_ids" => $product_data.category_ids|default:[]|array_values])}"|fn_url}">
                </div>
            </div>
        </div>
    {/if}
    <div id="products_update_features_content">
        {if $product_features}
            {include file="common/pagination.tpl" search=$features_search div_id="product_features_pagination_`$product_id`" current_url="products.get_features?product_id=`$product_id`&items_per_page=`$features_search.items_per_page`"|fn_url disable_history=true}

            <div class="product-features__group">
                {include file="views/products/components/product_assign_features.tpl" product_features=$product_features}
            </div>

            {include file="common/pagination.tpl" search=$features_search div_id="product_features_pagination_`$product_id`" current_url="products.get_features?product_id=`$product_id`&items_per_page=`$features_search.items_per_page`"|fn_url disable_history=true}

        {else}
            <p class="no-items">{__("no_items")}</p>
        {/if}
    <!--products_update_features_content--></div>
</div>