<div class="sidebar-row">
    <h6>{__("admin_search_title")}</h6>

    <form action="{""|fn_url}" name="product_features_search_form" method="get">

        {capture name="simple_search"}
            <div class="sidebar-field">
                <label>{__("category")}:</label>
                <div class="break clear correct-picker-but">
                    {if "categories"|fn_show_picker:$smarty.const.CATEGORY_THRESHOLD}
                        {$s_cid = $search.category_ids|default:0}
                        {include file="pickers/categories/picker.tpl" data_id="location_category" input_name="category_ids" item_ids=$s_cid hide_link=true hide_delete_button=true default_name=__("all_categories") extra=""}
                    {else}
                        {include file="common/select_category.tpl" name="category_ids" id=$search.category_ids}
                    {/if}
                </div>
            </div>
            <div class="sidebar-field">
                <label for="fname">{__("feature")}:</label>
                <input type="text" name="internal_name" id="fname" value="{$search.internal_name}" size="30" />
            </div>
            <div class="control-group">
                <label for="elm_parent_id" class="control-label">{__("group")}:</label>
                <div class="controls">
                    <select name="parent_id" id="elm_parent_id">
                        <option value="">--</option>
                        <option {if $search.parent_id === "0"}selected="selected"{/if} value="0">{__("ungroupped_features")}</option>
                        {foreach from=$group_features item="group_feature"}
                            <option value="{$group_feature.feature_id}"{if $group_feature.feature_id == $search.parent_id}selected="selected"{/if}>{$group_feature.internal_name}</option>
                        {/foreach}
                    </select>
                </div>
            </div>
        {/capture}

        {capture name="advanced_search"}
            <div class="group form-horizontal">
                <div class="control-group">
                    <label for="fname">{__("storefront_name")}:</label>
                    <input type="text" name="description" id="fname" value="{$search.description}" size="30" />
                </div>
            </div>
            <div class="group form-horizontal">

                {__("type")}

                <div class="table-wrapper">
                    <table width="100%">
                        <tr class="nowrap">
                            <td><label for="elm_checkbox_single" class="checkbox"><input id="elm_checkbox_single"  type="checkbox" name="feature_types[]" {if "ProductFeatures::SINGLE_CHECKBOX"|enum|in_array:$search.feature_types}checked="checked"{/if} value="{"ProductFeatures::SINGLE_CHECKBOX"|enum}"/>{__("checkbox")}:&nbsp;{__("single")}</label></td>
                            <td><label for="elm_checkbox_multiple" class="checkbox"><input id="elm_checkbox_multiple" type="checkbox" name="feature_types[]" {if "ProductFeatures::MULTIPLE_CHECKBOX"|enum|in_array:$search.feature_types}checked="checked"{/if} value="{"ProductFeatures::MULTIPLE_CHECKBOX"|enum}"/>{__("checkbox")}:&nbsp;{__("multiple")}</label></td>
                            <td><label for="elm_selectbox_text" class="checkbox"><input id="elm_selectbox_text"  type="checkbox" name="feature_types[]" {if "ProductFeatures::TEXT_SELECTBOX"|enum|in_array:$search.feature_types}checked="checked"{/if} value="{"ProductFeatures::TEXT_SELECTBOX"|enum}"/>{__("selectbox")}:&nbsp;{__("text")}</label></td>
                            <td><label for="elm_selectbox_number" class="checkbox"><input id="elm_selectbox_number"  type="checkbox" name="feature_types[]" {if "ProductFeatures::NUMBER_SELECTBOX"|enum|in_array:$search.feature_types}checked="checked"{/if} value="{"ProductFeatures::NUMBER_SELECTBOX"|enum}"/>{__("selectbox")}:&nbsp;{__("number")}</label></td>
                        </tr>
                        <tr>
                            <td><label for="elm_selectbox_brand_type" class="checkbox"><input id="elm_selectbox_brand_type"  type="checkbox" name="feature_types[]" {if "ProductFeatures::EXTENDED"|enum|in_array:$search.feature_types}checked="checked"{/if} value="{"ProductFeatures::EXTENDED"|enum}"/>{__("selectbox")}:&nbsp;{__("brand_type")}</label></td>
                            <td><label for="elm_others_text" class="checkbox"><input id="elm_others_text"  type="checkbox" name="feature_types[]" {if "ProductFeatures::TEXT_FIELD"|enum|in_array:$search.feature_types}checked="checked"{/if} value="{"ProductFeatures::TEXT_FIELD"|enum}"/>{__("others")}:&nbsp;{__("text")}</label></td>
                            <td><label for="elm_others_number" class="checkbox"><input id="elm_others_number"  type="checkbox" name="feature_types[]" {if "ProductFeatures::NUMBER_FIELD"|enum|in_array:$search.feature_types}checked="checked"{/if} value="{"ProductFeatures::NUMBER_FIELD"|enum}"/>{__("others")}:&nbsp;{__("number")}</label></td>
                            <td><label for="elm_others_date" class="checkbox"><input id="elm_others_date"  type="checkbox" name="feature_types[]" {if "ProductFeatures::DATE"|enum|in_array:$search.feature_types}checked="checked"{/if} value="{"ProductFeatures::DATE"|enum}"/>{__("others")}:&nbsp;{__("date")}</label></td>
                        </tr>
                    </table>
                </div>
            </div>

            <div class="row-fluid">
                <div class="group span6 form-horizontal">
                    <div class="control-group">
                        <label class="control-label" for="elm_updated_in_days">{__("updated_last")}</label>
                        <div class="controls">
                            <input type="text" name="updated_in_days" id="elm_updated_in_days" value="{$search.updated_in_days}" onfocus="this.select();" class="input-mini" />&nbsp;&nbsp;{__("day_or_days")}
                        </div>
                    </div>
                </div>

                {capture name="search_form_company"}
                    {hook name="product_features:search_form_company"}
                        {if $picker_selected_company|default:""|fn_string_not_empty}
                            <input type="hidden" name="company_id" value="{$picker_selected_company}" />
                        {else}
                            {include file="common/select_vendor.tpl"}
                        {/if}
                    {/hook}
                {/capture}

                {if $smarty.capture.search_form_company|trim}
                    <div class="group span6 form-horizontal">
                        {$smarty.capture.search_form_company nofilter}
                    </div>
                {/if}
            </div>

            {if "MULTIVENDOR"|fn_allowed_for}
                <div class="group form-horizontal">
                    <div class="control-group">
                        <label class="control-label" for="elm_vendor_features_only">{__("vendor_features_only")}</label>
                        <div class="controls">
                            <input type="hidden" name="vendor_features_only" value="N" />
                            <input type="checkbox" value="Y"{if $search.vendor_features_only == "YesNo::YES"|enum} checked="checked"{/if} name="vendor_features_only"  id="elm_vendor_features_only" />
                        </div>
                    </div>
                </div>
            {/if}

            <div class="group form-horizontal">
                <div class="control-group">
                    <label class="control-label" for="elm_display_on">{__("display_on")}:</label>
                    <div class="controls">
                        <select name="display_on" id="elm_display_on">
                            <option value="">--</option>
                            <option value="product" {if $search.display_on == "product"}selected="selected"{/if}>{__("product")}</option>
                            <option value="catalog" {if $search.display_on == "catalog"}selected="selected"{/if}>{__("catalog_pages")}</option>
                        </select>
                    </div>
                </div>

                {hook name="product_features:search_form"}
                {/hook}

            </div>
        {/capture}

        {include file="common/advanced_search.tpl" simple_search=$smarty.capture.simple_search advanced_search=$smarty.capture.advanced_search dispatch=$dispatch view_type="product_features" method="GET"}
    </form>
</div>