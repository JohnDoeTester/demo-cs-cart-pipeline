{script src="js/addons/product_variations/tygh/backend/generator.js?ver=1.2"}

<div id="generate_variations_container">
    <div class="cm-variations-generator product-variations__generator"
        data-ca-container-id="generate_variations_container"
        data-ca-features-container-id="generate_variations_container__features_container"
        data-ca-combinations-container-id="generate_variations__combinations_container"
        data-ca-buttons-container-id="tools_tab_create_new_{$product_data.product_id}"
        data-ca-quick-add-feature-selector="#product_variations_quick_add_feature"
    >
        {if !$group}
            {$purpose_group_catalog_item = "\Tygh\Addons\ProductVariations\Product\FeaturePurposes::CREATE_CATALOG_ITEM"|constant}
            {$purpose_group_variation_catalog_item = "\Tygh\Addons\ProductVariations\Product\FeaturePurposes::CREATE_VARIATION_OF_CATALOG_ITEM"|constant}

            <div class="control-toolbar">
                <div class="control-toolbar__btns cm-variations-generator__features" id="variations_generator_features">
                    <div class="control-toolbar__btns-center">
                        {$search_data = ["product_id" => $product_data.product_id, "filter_category_ids" => $product_data.category_ids, "purpose" => []]}
                        {$search_data["purpose"][] = $purpose_group_catalog_item}
                        {$search_data["purpose"][] = $purpose_group_variation_catalog_item}
                        {$search_data["exclude_feature_ids"] = $feature_ids}
                        {$search_data["get_only_available"] = true}

                        {include file="views/product_features/components/picker/picker.tpl"
                            empty_variant_text=__("product_variations.generator.features.placeholder")
                            search_data=$search_data
                            multiple=true
                            hide_selection=true
                            close_on_select=false
                            meta="object-picker--product-variations-features control-toolbar__select"
                            allow_add=true
                            create_option_to_end="true"
                        }
                    </div>
                </div>
                <div class="control-toolbar__panel">
                    <div id="product_variations_quick_add_feature"
                        data-ca-product-id="{$product_data.product_id}"
                        data-ca-target-id="generate_variations_container__features_container"
                        data-ca-inline-dialog-action-context="product_variation_generator"
                        data-ca-inline-dialog-url="{"product_features.quick_add?category_id={$product_data.main_category}&{http_build_query(["category_ids" => $product_data.category_ids|default:[]|array_values])}"|fn_url}&show_purposes=1&filter_purposes[]={$purpose_group_catalog_item}&filter_purposes[]={$purpose_group_variation_catalog_item}">
                    </div>
                </div>
            </div>
        {/if}

        <form action="{"product_variations.generate"|fn_url}"
            name="generate_product_to_group_form"
            method="post"
            class="form-horizontal form-edit"
        >
            <input type="hidden" name="product_id" value="{$product_data.product_id}"/>
            <div class="cm-variations-generator__features-variants" id="generate_variations_container__features_container">
                {if $selected_features}
                    {foreach $selected_features as $feature}
                        <div class="control-group cm-variations-generator__select-feature-variations" data-ca-feautre-id="{$feature.feature_id}">
                            <input type="hidden" name="feature_ids[]" value="{$feature.feature_id}"/>
                            <label class="control-label" for="variations_feature_{$feature.feature_id}">{$feature.internal_name}
                                <div class="link cm-variations-generator_add-all-variants" data-ca-feature-id="{$feature.feature_id}">
                                    <span class="btn-link">{__("product_variations.add_all_variants")}</span>
                                </div>
                            </label>
                            <div class="controls" id="product_feature_variations_{$feature.feature_id}">
                                <div class="product-assign-features__row">
                                    {include file="views/product_features/components/variants_picker/picker.tpl"
                                        empty_variant_text=__("product_variations.generator.feature_variants.placeholder")
                                        feature_id=$feature.feature_id
                                        multiple=true
                                        close_on_select=false
                                        item_ids=$features_variant_ids[$feature.feature_id]|default:[]
                                        unremovable_item_ids=$exists_features_variant_ids[$feature.feature_id]|default:[]
                                        enable_permanent_placeholder=true
                                        input_name="features_variants_ids[{$feature.feature_id}][]"
                                        meta="product-assign-features__select"
                                    }
                                    {if !$group}
                                        {include file="buttons/button.tpl"
                                            but_role="button-icon"
                                            but_meta="btn cm-variations-generator__delete-feature-variation"
                                            but_icon="icon-trash product-update-features_delete-icon"
                                            title=__("delete")
                                        }
                                    {/if}
                                </div>
                            </div>
                        </div>
                    {/foreach}

                    {if $group}
                        <div class="well">
                            {__("product_variations.generator.warning.new_features_add")}
                        </div>
                    {/if}
                {else}
                    <div class="no-items row-fluid">
                        <div class="span8 offset2 left">{__("product_variations.no_available_features", ["[manage_features_href]" => "product_features.manage"|fn_url]) nofilter}</div>
                    </div>
                {/if}
            <!--generate_variations_container__features_container--></div>

            <div class="cm-variations-generator__combinations" id="generate_variations__combinations_container">
                {if $combinations}
                    <p>{__("product_variations.generator.table.title")}</p>
                    <div class="table-responsive-wrapper">
                        <table width="100%" class="table table-middle table--relative table-responsive">
                            <thead>
                            <tr>
                                <th width="2%">{include file="common/check_items.tpl" style="checkbox" checked=$is_all_combinations_active}</th>
                                <th width="2%">&nbsp;</th>
                                <th width="20%" class="nowrap"><span>{__("features")}</span></th>
                                <th width="25%" class="nowrap"><span>{__("name")}</span></th>
                                <th width="16%" class="nowrap">{__("sku")}</th>
                                <th width="13%" class="nowrap">{__("price")} ({$currencies.$primary_currency.symbol nofilter})</th>
                                <th width="9%" class="nowrap">{__("quantity")}</th>
                            </tr>
                            </thead>
                            {$combinations_count = $combinations|count}
                            {foreach $combinations as $combination}
                                {if !$combination.parent_combination_id}
                                    {if !$combination@first}
                                        </tbody>
                                    {/if}

                                    <tbody class="combinations-table__parent-combination">
                                        {include file="addons/product_variations/views/product_variations/components/combination_item.tpl" combinations_count=$combinations_count}
                                    </tbody>
                                    <tbody data-ca-switch-id="product_variations_group_{$combination.combination_id}">
                                {else}
                                    {include file="addons/product_variations/views/product_variations/components/combination_item.tpl" combinations_count=$combinations_count}
                                {/if}
                            {/foreach}
                            </tbody>
                        </table>
                    </div>
                {/if}
            <!--generate_variations__combinations_container--></div>

        </form>
    </div>
<!--generate_variations_container--></div>
