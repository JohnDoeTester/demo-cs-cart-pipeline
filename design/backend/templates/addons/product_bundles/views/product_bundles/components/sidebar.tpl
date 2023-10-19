{$dispatch = "product_bundles.manage"}
<div class="sidebar-row">
    <h6>{__("admin_search_title")}</h6>
    <form action="{""|fn_url}" name="bundle_search_form" method="get" class="{$form_meta}">
        <div class="sidebar-field">
            <label for="elm_bundle">{__("product_bundles.product_bundle_name")}</label>
            <input type="text" name="q" id="elm_bundle" value="{$search.q}">
        </div>

        {if "MULTIVENDOR"|fn_allowed_for && !$runtime.company_id}
            <div class="sidebar-field">
                <label for="elm_owner">{__("owner")}</label>
                {include file="views/companies/components/picker/picker.tpl"
                    input_name="company_id"
                    show_advanced=false
                    show_empty_variant=true
                    item_ids=($search.company_id) ? [$search.company_id] : []
                    empty_variant_text=__("any_vendor")
                }
            </div>
        {/if}

        <div class="sidebar-field">
            <label>{__("product_bundles.search_in_products")}</label>

            {include file="views/products/components/picker/picker.tpl"
                advanced_picker_id="product_bundle_`$id`_"
                view_mode="simple"
                show_simple_product=true
                input_name="product_id"
                item_ids=[$search.product_id]
                show_empty_variant=true
                for_current_storefront=true
                result_class="object-picker__result--product-bundles"
                show_advanced=false
            }
        </div>

        <div class="sidebar-field">
            <input class="btn" type="submit" name="dispatch[{$dispatch}]" value="{__("admin_search_button")}">
        </div>
    </form>
</div>