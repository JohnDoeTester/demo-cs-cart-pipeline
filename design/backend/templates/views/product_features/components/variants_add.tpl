{script src="js/tygh/backend/variants_add.js"}

{$highlight_variants_add = $highlight_variants_add|default:(!!$id && ($num !== 0))}
{$show_variants_add = $show_variants_add|default:(!$id || ($num === 0))}
{$var = array()}
{$num = ($num|default:0) + 1}

<tbody class="hover
        {if $highlight_variants_add}well{/if}
        {if !$show_variants_add}hidden{/if}"
    data-ca-variants-list="containerAdd"
    id="box_add_variants_for_existing_{$id}"
>
    <tr>
        {hook name="product_features:variants_list_clone"}
            <td class="cm-extended-feature {if $feature_type != "ProductFeatures::EXTENDED"|enum}hidden{/if}" data-th="&nbsp;">
                <span id="on_extra_feature_{$id}_{$num}" alt="{__("expand_collapse_list")}" title="{__("expand_collapse_list")}" class="hand hidden cm-combination-features-{$id}"><span class="icon-caret-right"></span></span>
                <span id="off_extra_feature_{$id}_{$num}" alt="{__("expand_collapse_list")}" title="{__("expand_collapse_list")}" class="hand cm-combination-features-{$id}"><span class="icon-caret-down"></span></span>
            </td>
            <td data-th="{__("position_short")}">
                <input type="text" name="feature_data[variants][{$num}][position]" value="" size="4" class="input-micro" /></td>
            <td data-th="{__("variant")}">
                <div class="input-prepend input-prepend--full">
                    <div class="colorpicker--wrapper">
                        {include file="common/colorpicker.tpl"
                            cp_name="feature_data[variants][{$num}][color]"
                            cp_id="feature_value_color_picker_{$num}"
                            cp_value="#ffffff"
                            show_picker=true
                            cp_meta="js-feature-variant-conditional-column"
                            cp_attrs=["data-ca-column-for-feature-style" => "ProductFeatureStyles::COLOR"|enum, "data-ca-column-for-filter-style" => "ProductFilterStyles::COLOR"|enum]
                        }
                    </div>
                    <input type="text" name="feature_data[variants][{$num}][variant]" placeholder="{__("type_to_create")}" value="" class="input-full cm-feature-value {if $feature_type === "ProductFeatures::NUMBER_SELECTBOX"|enum}cm-value-decimal{/if}">
                </div>
            </td>
        {/hook}
        <td data-th="&nbsp;">&nbsp;</td>
        <td class="right" data-th="&nbsp;">
            {include file="buttons/multiple_buttons.tpl" item_id="add_variants_for_existing_`$id`" tag_level=2}
        </td>
    </tr>
    <tr {if $feature_type != "ProductFeatures::EXTENDED"|enum}class="hidden"{/if} id="extra_feature_{$id}_{$num}">
        <td colspan="6" data-th="{__("information")}">

            <div class="control-group">
                <label class="control-label" for="elm_image_{$id}_{$num}">{__("image")}</label>
                <div class="controls">
                {include file="common/attach_images.tpl" image_name="variant_image" image_key=$num hide_titles=true no_detailed=true image_object_type="feature_variant" image_type="V" image_pair="" prefix=$id}
                </div>
            </div>

            <div class="control-group">
                <label class="control-label" for="elm_description_{$id}_{$num}">{__("description")}</label>
                <div class="controls">
                <textarea id="elm_description_{$id}_{$num}" name="feature_data[variants][{$num}][description]" cols="55" rows="8" class="cm-wysiwyg input-textarea-long"></textarea>
                </div>
            </div>

            <div class="control-group">
                <label class="control-label" for="elm_page_title_{$id}_{$num}">{__("page_title")}</label>
                <div class="controls">
                <input type="text" name="feature_data[variants][{$num}][page_title]" id="elm_page_title_{$id}_{$num}" size="55" value="" class="input-large" />
                </div>
            </div>

            <div class="control-group">
                <label class="control-label" for="elm_url_{$id}_{$num}">{__("url")}</label>
                <div class="controls">
                <input type="text" name="feature_data[variants][{$num}][url]" id="elm_url_{$id}_{$num}" size="55" value="" class="input-large" />
                </div>
            </div>

            <div class="control-group">
                <label class="control-label" for="elm_meta_description_{$id}_{$num}">{__("meta_description")}</label>
                <div class="controls">
                <textarea name="feature_data[variants][{$num}][meta_description]" id="elm_meta_description_{$id}_{$num}" cols="55" rows="2" class="input-textarea-long"></textarea>
                </div>
            </div>

            <div class="control-group">
                <label class="control-label" for="elm_meta_keywords_{$id}_{$num}">{__("meta_keywords")}</label>
                <div class="controls">
                <textarea name="feature_data[variants][{$num}][meta_keywords]" id="elm_meta_keywords_{$id}_{$num}" cols="55" rows="2" class="input-textarea-long"></textarea>
                </div>
            </div>
            {hook name="product_features:extended_feature"}{/hook}
        </td>
    </tr>
</tbody>
