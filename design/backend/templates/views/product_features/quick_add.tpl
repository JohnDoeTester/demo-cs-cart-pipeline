{script src="js/tygh/backend/product_features/quick_add.js"}

{*
    $meta                           string                  Block class
    $show_header                    bool                    Show block header
    $show_purposes                  bool                    Show purposes
    $enable_popover                 bool                    Enable popover
    $category_id                    string                  Product category ID
    $category_tabindex              number                  Category tabindex
    $feature                        array                   Feature data
    $form_id                        string                  Quick form unique ID
    $event_id                       string                  Js event id that will be fired after feature created
    $is_name_focus                  bool                    Is feature name focus
    $is_variants_focus              bool                    Is variants focus
*}

{$show_header = $show_header|default:true}
{$enable_popover = $enable_popover|default:true}
{$form_id = $form_id|default:uniqid()}
{$action_context = $action_context|default:$smarty.request._action_context}
{if $feature.description}
    {$is_variants_focus = true}
{else}
    {$is_name_focus = true}
{/if}

<div class="features-create__block {$meta} {if $enable_popover}well{/if}"
    data-ca-features-create-elem="block"
    data-ca-features-create-variants-selector="#elm_variants_{$form_id}"
    data-ca-features-create-variants-data-selector="[data-ca-features-create-elem='variantsData']"
    data-ca-features-create-request-form="quick_add_feature_form_{$form_id}"
    data-ca-features-create-event-id="{$event_id|default:"product_feature_created"}"
    data-ca-features-create-confirm-text="{__("text_tab_changed")|escape nofilter}"
>
    {if $show_header}
        <div class="features-create__header">
            <h4 class="subheader features-create__subheader">{__("new_feature")}</h4>
            {if $enable_popover}
                <button type="button" class="close flex-vertical-centered cm-inline-dialog-closer" data-ca-features-create-elem="close">
                    {include_ext file="common/icon.tpl" class="icon-remove"}
                </button>
            {/if}
        </div>
    {/if}
    <form action="{""|fn_url}"
        method="post"
        name="quick_add_feature_form_{$form_id}"
        id="quick_add_feature_form_{$form_id}"
        {if $action_context}data-ca-ajax-done-event="ce.{$action_context}.product_feature_save"{/if}
        class="cm-ajax form-horizontal form-edit" enctype="multipart/form-data"
    >
        <input type="hidden" name="feature_id" value="0" />
        <input type="hidden" name="feature_data[feature_id]" value="0" />
        <input type="hidden" name="feature_data[parent_id]" value="0">

        {* Feature name and storefront name *}
        {include file="components/copy_on_type.tpl"
            id=$form_id
            source_value=$feature.internal_name
            source_name="feature_data[internal_name]"
            target_value=$feature.description
            target_name="feature_data[description]"
            type="feature_name"
            is_source_focus=$is_name_focus
        }
        {* /Feature name and storefront name *}

        {* Feature company id *}
        {if "MULTIVENDOR"|fn_allowed_for || $runtime.company_id}
            {$company_id = $runtime.company_id}
        {else}
            {$company_id = fn_get_default_company_id()}
        {/if}
        <input type="hidden" name="feature_data[company_id]" value="{$company_id}">
        {* /Feature company id *}

        <input type="hidden" name="feature_data[feature_type]" value="{"ProductFeatures::TEXT_SELECTBOX"|enum}">

        {if $show_purposes}
            {* Show feature purposes *}
            <div class="control-group">
                <label class="control-label cm-required cm-multiple-radios" for="elm_feature_purpose_{$form_id}">{__("product_feature.purpose")}</label>
                <div class="controls">
                    <ul class="unstyled">
                        {foreach $purposes|@array_reverse as $purpose => $purpose_data}
                            <li>
                                <label for="elm_feature_purpose_{$form_id}_{$purpose}" class="radio inline">{strip}
                                    {__("product_feature.purpose.{$purpose}")}
                                    <input{/strip}
                                        type="radio"
                                        name="feature_data[purpose]"
                                        value="{$purpose}"
                                        id="elm_feature_purpose_{$form_id}_{$purpose}"
                                        {if $purpose_data@first}checked="checked"{/if}>
                                </label>
                            </li>
                        {/foreach}
                    </ul>
                </div>
            </div>
        {else}
            {* Feature parameters *}
            <input type="hidden" name="feature_data[purpose]" value="find_products">
            <input type="hidden" name="feature_data[filter_style]" value="{"ProductFilterStyles::CHECKBOX"|enum}">
            {* /Feature parameters *}
        {/if}

        {* /Feature purpose *}

        {* Feature variants *}
        <div class="control-group">
            <label class="control-label" for="elm_variants_{$form_id}">{__("variants")}</label>
            <div class="controls">
                {$picker_id = uniqid()}
                <div class="object-picker object-picker--product-features-variants-add" data-object-picker="object_picker_{$picker_id}">
                    <div class="object-picker__select-group object-picker__select-group--product-features-variants-add">
                        <div class="object-picker__simple object-picker__simple--product-features-variants-add">
                            <select multiple
                                    id="elm_variants_{$form_id}"
                                    class="cm-object-picker object-picker__select object-picker__select--product-features-variants-add"
                                    data-ca-object-picker-object-type="productFeaturesVariants"
                                    data-ca-object-picker-placeholder="{__("type_to_create")}"
                                    data-ca-object-picker-allow-clear="true"
                                    data-ca-object-picker-has-strict-compliance-matcher="true"
                                    data-ca-object-picker-enable-create-object="true"
                                    data-ca-object-picker-token-separators="[',']"
                                    data-ca-object-picker-container-css-class="object-picker__selection-simple object-picker__selection-simple--full-width object-picker__selection-simple--product-features-variants-add"
                                    data-ca-object-picker-show-dropdown="false"
                                    data-ca-object-picker-select-on-close="true"
                                    data-ca-object-picker-autofocus="{$is_variants_focus}"
                                    data-ca-object-picker-has-removable-items="true"
                            ></select>
                            <div class="hidden" data-ca-features-create-elem="variantsData"></div>
                            <p class="muted description">{__("use_comma_enter_to_separate_variants")}</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        {* /Feature variants *}

        {* Feature category *}
        <div class="control-group">
            <label class="control-label" for="elm_feature_category_{$form_id}">{__("feature_category")}</label>
            <div class="controls">
                {$rnd = uniqid()}
                {include file="common/select2/categories.tpl"
                    select2_tabindex=$category_tabindex
                    select2_select_id="product_features_categories_path_`$rnd`"
                    select2_name="feature_data[categories_path]"
                    select2_category_ids=[$category_id]
                    select2_wrapper_meta="cm-field-container"
                    select2_select_meta="input-large"
                    select2_required="true"
                    select2_show_advanced=false
                    select2_close_on_select=true
                    select2_data_url="categories.get_categories_list?{http_build_query(["restricted_by_ids" => $category_ids|default:[]|array_values])}"|fn_url
                    select2_allow_clear=true
                    select2_enable_add=false
                }
            </div>
        </div>
        {* /Feature category *}

        <div class="features-create__footer">
            {btn type="text"
                id="advanced_feature_creation_{$form_id}"
                text=__("advanced_feature_creation")
                title=__("new_feature")
                href="{"product_features.add"|fn_url}"
                class="btn cm-dialog-opener cm-dialog-destroy-on-close"
                target_id="add_product_feature_popup_{$form_id}"
                data=[
                    "data-ca-target-id" => "add_product_feature_popup",
                    "data-ca-dialog-content-request-form" => "quick_add_feature_form_{$form_id}",
                    "data-ca-dialog-action-context" => $action_context
                ]
            }
            {include file="buttons/button.tpl" 
                but_role="submit" 
                but_text=__("create") 
                but_name="dispatch[product_features.update]"
                but_meta="cm-internal-submit"
            }
        </div>
    </form>
</div>
