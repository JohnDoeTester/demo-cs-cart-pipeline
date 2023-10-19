{script src="js/tygh/backend/product_options/quick_add.js"}

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

<div class="options-create__block {$meta} {if $enable_popover}well{/if}"
    data-ca-options-create-elem="block"
    data-ca-options-create-variants-selector="#elm_variants_{$form_id}"
    data-ca-options-create-variants-data-selector="[data-ca-options-create-elem='variantsData']"
    data-ca-options-create-request-form="quick_add_option_form_{$form_id}"
    data-ca-options-create-event-id="{$event_id|default:"product_option_save"}"
>
    {if $show_header}
        <div class="options-create__header">
            <h4 class="subheader options-create__subheader">{__("new_option")}</h4>
            {if $enable_popover}
                <button type="button" class="close flex-vertical-centered cm-inline-dialog-closer" data-ca-options-create-elem="close">
                    {include_ext file="common/icon.tpl" class="icon-remove"}
                </button>
            {/if}
        </div>
    {/if}
    <form action="{""|fn_url}"
        method="post"
        name="quick_add_option_form_{$form_id}"
        id="quick_add_option_form_{$form_id}"
        {if $action_context}data-ca-ajax-done-event="ce.{$action_context}.product_option_save"{/if}
        class="cm-ajax form-horizontal form-edit" enctype="multipart/form-data"
    >
        {if "ULTIMATE"|fn_allowed_for && $runtime.company_id && $shared_product == "Y"}
            {$cm_no_hide_input="cm-no-hide-input"}
        {/if}
        <input type="hidden" name="option_id" value="0" class="{$cm_no_hide_input}" />
        {if $smarty.request.product_id}
            <input class="cm-no-hide-input" type="hidden" name="option_data[product_id]" value="{$smarty.request.product_id}" />
            <input class="cm-no-hide-input" type="hidden" name="option_data[is_global]" value="1">
            {if "ULTIMATE"|fn_allowed_for}
                {$disable_company_picker=true}
                {if !$company_id}
                    {$company_id=$product_company_id}
                {/if}
            {/if}
        {/if}

        {* Option type *}
        <input type="hidden" name="option_data[option_type]" value="{"ProductOptionTypes::SELECTBOX"|enum}" class="cm-no-hide-input" />
        <input class="cm-no-hide-input" type="hidden" value="{$object}" name="object">

        {* Option name and storefront name *}
        {include file="components/copy_on_type.tpl"
            source_value=$option_data.internal_option_name
            source_name="option_data[internal_option_name]"
            target_value=$option_data.option_name
            target_name="option_data[option_name]"
            type="option_name"
        }

        {* Option company id *}
        {if "MULTIVENDOR"|fn_allowed_for}
            {$zero_company_id_name_lang_var="none"}
        {/if}
        {$selected=$option_data.company_id|default:$company_id}
        {$required = $required|default:false}
        {if $runtime.company_id && (!$selected || "MULTIVENDOR"|fn_allowed_for) &&  !$disable_company_picker}
            {$selected = $runtime.company_id}
        {/if}
        {if !$selected}
            {if $zero_company_id_name_lang_var}
                {$selected = ($required) ? "" : "0"}
            {else}
                {$selected = fn_get_default_company_id()}
            {/if}
        {/if}
        <input type="hidden" class="cm-no-failed-msg" name="option_data[company_id]" value="{$selected}">
        {* /Option company id *}

        {* Option variants *}
        <div class="control-group">
            <label class="control-label" for="elm_variants_{$id}">{__("variants")}</label>
            <div class="controls">
                {$picker_id = uniqid()}
                <div class="object-picker object-picker--product-options-variants-add" data-object-picker="object_picker_{$picker_id}">
                    <div class="object-picker__select-group object-picker__select-group--product-options-variants-add">
                        <div class="object-picker__simple object-picker__simple--product-options-variants-add">
                            <select multiple
                                    id="elm_variants_{$form_id}"
                                    class="cm-object-picker object-picker__select object-picker__select--product-options-variants-add"
                                    data-ca-object-picker-object-type="productOptionsVariants"
                                    data-ca-object-picker-placeholder="{__("type_to_create")}"
                                    data-ca-object-picker-allow-clear="true"
                                    data-ca-object-picker-has-strict-compliance-matcher="true"
                                    data-ca-object-picker-enable-create-object="true"
                                    data-ca-object-picker-token-separators="[',']"
                                    data-ca-object-picker-container-css-class="object-picker__selection-simple object-picker__selection-simple--full-width object-picker__selection-simple--product-options-variants-add"
                                    data-ca-object-picker-show-dropdown="false"
                                    data-ca-object-picker-select-on-close="true"
                                    data-ca-object-picker-has-removable-items="true"
                            ></select>
                            <div class="hidden" data-ca-options-create-elem="variantsData"></div>
                            <p class="muted description">{__("use_comma_enter_to_separate_variants")}</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        {* /Option variants *}

        <div class="options-create__footer">
            {btn type="text"
                id="add_new_option_{$form_id}"
                text=__("advanced_option_creation")
                title=__("new_option")
                href="product_options.add"|fn_url
                class="btn cm-dialog-opener cm-dialog-destroy-on-close"
                target_id="add_product_option_popup_{$form_id}"
                data=[
                    "data-ca-target-id" => "add_product_option_popup",
                    "data-ca-dialog-content-request-form" => "quick_add_option_form_{$form_id}",
                    "data-ca-dialog-action-context" => $action_context
                ]
            }

            {include file="buttons/button.tpl" but_role="submit" but_text=__("create") but_name="dispatch[product_options.update]"}
        </div>
    </form>
</div>
