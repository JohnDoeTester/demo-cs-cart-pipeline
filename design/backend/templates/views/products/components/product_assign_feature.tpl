{$allow_enter_variant = $allow_add_feature && $feature|fn_allow_save_object:"product_features"}
{$product_id = $product_id|default:0}

{if $feature.feature_style == "ProductFeatureStyles::COLOR"|enum || $feature.filter_style == "ProductFilterStyles::COLOR"|enum}
    {$template_type = "color"}
    {$enable_images = false}
{elseif $feature.feature_style == "ProductFeatureStyles::BRAND"|enum}
    {$template_type = "image"}
    {$enable_images = true}
{else}
    {$template_type = "text"}
    {$enable_images = false}
{/if}

{if $feature.feature_type != "ProductFeatures::GROUP"|enum}
    {hook name="products:update_product_feature"}
        <div class="control-group control-group--hidden-input">
            <label class="control-label" for="feature_{$feature_id}">
                <a href="{"product_features.update?feature_id={$feature_id}"|fn_url}">
                    {$feature.internal_name}
                </a>
                <div>
                    <small>
                        {$feature.description}
                    </small>
                </div>
            </label>
            <div class="controls">
                <div class="product-assign-features__row">
                    {if $feature.prefix}<span>{$feature.prefix}</span>{/if}

                    {if $feature.feature_type == "ProductFeatures::TEXT_SELECTBOX"|enum
                        || $feature.feature_type == "ProductFeatures::NUMBER_SELECTBOX"|enum
                        || $feature.feature_type == "ProductFeatures::EXTENDED"|enum}
                        {$value_selected = false}
                        <input type="hidden"
                               name="product_data[product_features][{$feature_id}]"
                               id="feature_{$feature_id}"
                               value="{$selected|default:$feature.variant_id}"
                        />
                        <input type="hidden"
                               name="product_data[add_new_variant][{$feature_id}][variant]"
                               id="product_feature_{$feature_id}_add_new_variant"
                               value=""
                        />
                        {if $feature.variants[$feature.variant_id]}
                            {$item_ids=[$feature.variant_id => $feature.variants[$feature.variant_id]]}
                        {else}
                            {$item_ids=$feature.variants}
                        {/if}
                        {include file="views/product_features/components/variants_picker/picker.tpl"
                            feature_id=$feature.feature_id
                            input_name="product_data[product_features][{$feature_id}]"
                            item_ids=$item_ids|default:[]
                            multiple=false
                            template_type=$template_type
                            allow_clear=true
                            allow_add=$allow_enter_variant
                            new_value_holder_selector="#product_feature_{$feature_id}_add_new_variant"
                            enable_image=$enable_images
                        }
                    {elseif $feature.feature_type == "ProductFeatures::MULTIPLE_CHECKBOX"|enum}
                        <input type="hidden"
                               name="product_data[product_features][{$feature_id}]"
                               value=""
                        />
                        <input type="hidden"
                               name="product_data[add_new_variant][{$feature_id}][variant][]"
                               class="product_feature_{$feature_id}_add_new_variant"
                               value=""
                        />
                        {include file="views/product_features/components/variants_picker/picker.tpl"
                            multiple=true
                            feature_id=$feature.feature_id
                            input_name="product_data[product_features][{$feature_id}][]"
                            item_ids=$feature.variants|default:[]
                            template_type=$template_type
                            allow_clear=false
                            allow_add=$allow_enter_variant
                            new_value_holder_selector=".product_feature_{$feature_id}_add_new_variant"
                            enable_image=$enable_images
                        }
                    {elseif $feature.feature_type == "ProductFeatures::SINGLE_CHECKBOX"|enum}
                        <label class="checkbox">
                            <input type="hidden" name="product_data[product_features][{$feature_id}]" value="N" />
                            <input type="checkbox" name="product_data[product_features][{$feature_id}]" value="Y" id="feature_{$feature_id}" {if $feature.value == "Y"}checked="checked"{/if} /></label>
                    {elseif $feature.feature_type == "ProductFeatures::DATE"|enum}
                        {include file="common/calendar.tpl"
                            date_id="date_`$feature_id`"
                            date_name="product_data[product_features][$feature_id]"
                            date_val=$feature.value_int|default:""
                            extra="placeholder=\"-{__("none")}-\""
                        }
                    {else}
                        <input type="text"
                            name="product_data[product_features][{$feature_id}]"
                            value="{if $feature.feature_type == "ProductFeatures::NUMBER_FIELD"|enum}{if $feature.value_int != ""}{$feature.value_int|floatval}{/if}{else}{$feature.value}{/if}"
                            id="feature_{$feature_id}"
                            class="{if $feature.feature_type == "ProductFeatures::NUMBER_FIELD"|enum} cm-value-decimal{/if} input-large"
                            placeholder="-{__("none")}-"/>
                    {/if}
                    {if $feature.suffix}<span>{$feature.suffix}</span>{/if}
                    {if empty($product_id)}
                        {include file="buttons/button.tpl"
                            but_role="button-icon"
                            but_meta="btn cm-assign-features_delete-item"
                            but_icon="icon-trash product-update-features_delete-icon"
                            title=__("delete")
                        }
                    {/if}
                </div>
            </div>
        </div>
    {/hook}
{/if}