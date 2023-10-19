{script src="js/tygh/backend/select2_categories.js"}
<div class="object-categories-add {if $select2_multiple}object-categories-add--multiple{/if} cm-object-categories-add-container {$select2_wrapper_meta}">
    {$select_id=$select2_select_id|default:"categories_add"}
    {$category_ids = $select2_category_ids|default:[]|array_unique}
    {$enable_add = $select2_enable_add|default:"true"}
    {$select2_show_advanced = $select2_show_advanced|default:"true"}

    {if "MULTIVENDOR"|fn_allowed_for}
        {$zero_company_id_name_lang_var = "none"}
    {/if}

    {if $runtime.company_id || "MULTIVENDOR"|fn_allowed_for}
        {$company_id = $runtime.company_id}
    {/if}

    {if "MULTIVENDOR"|fn_allowed_for && $runtime.company_id}
        {$enable_add = "false"}
    {/if}

    {if !$company_id}
        {if $zero_company_id_name_lang_var}
            {$company_id = "0"}
        {else}
            {$company_id = fn_get_default_company_id()}
        {/if}
    {/if}
    {$company_name = $company_id|fn_get_company_name}

    <input type="hidden" name="{$select2_name}" value="" />

    {$new_category_field_name = ($is_multiple_update) ? ("products_data[`$product_id`][add_new_category][]") : ("product_data[add_new_category][]")}
    <input type="hidden" name="{$new_category_field_name}" value=""/>

    {if $select2_multiple}
        {$select2_name="`$select2_name`[]"}
    {/if}

    <select id="{$select_id}"
        class="cm-object-selector cm-object-categories-add {$select2_select_meta}"
        {if $select2_tabindex}
            tabindex="{$select2_tabindex}"
        {/if}
        {if $select2_multiple}
            multiple
        {/if}
        {if $select2_disabled}
            disabled
        {/if}
        name="{$select2_name}"
        data-ca-enable-images="{$select2_enable_images|default:"false"}"
        data-ca-enable-search="{$select2_enable_search|default:"true"}"
        data-ca-load-via-ajax="{$select2_load_via_ajax|default:"true"}"
        data-ca-page-size="{$select2_page_size|default:10}"
        data-ca-data-url="{$select2_data_url|default:"categories.get_categories_list"|fn_url nofilter}"
        data-ca-placeholder="{$select2_placeholder|default:__("type_to_search")}"
        data-ca-allow-clear="{$select2_allow_clear|default:"false"}"
        data-ca-close-on-select="{$select2_close_on_select|default:"false"}"
        data-ca-ajax-delay="{$select2_ajax_delay|default:250}"
        data-ca-allow-sorting="{$select2_allow_sorting|default:"false"}"
        data-ca-escape-html="{$select2_escape_html|default:"false"}"
        data-ca-dropdown-css-class="{$select2_dropdown_css_class|default:"select2-dropdown-below-categories-add"}"
        data-ca-required="{$select2_required|default:"false"}"
        data-ca-select-width="{$select2_width|default:"100%"}"
        data-ca-repaint-dropdown-on-change="{$select2_repaint_dropdown_on_change|default:"true"}"
        data-ca-picker-id="categories_{$select2_select_id}"
        data-ca-enable-add="{$enable_add}"
        data-ca-template-type="category"
        data-ca-template-selection-selector="#template_selection_category"
        data-ca-template-result-add-selector="#template_result_add_category"
        data-ca-new-value-holder-selector="[name='{$new_category_field_name}']"
        data-ca-new-value-allow-multiple="true"
    >
        {if $category_ids}
            {foreach $category_ids|array_unique as $category_id}
                <option value="{$category_id}"
                        selected="selected"
                ></option>
            {/foreach}
        {/if}
    </select>
    {if $select2_show_advanced && !$select2_disabled}
        {include file="pickers/categories/picker.tpl"
            company_ids=$runtime.company_id
            rnd=$select2_select_id
            data_id="categories"
            view_mode="button"
            but_meta="btn object-categories-add__picker"
            but_icon="icon-reorder"
            but_text=false
            multiple=true
        }
    {/if}

    {capture name="template_selection_category_pre"}
        <span class="select2-selection__choice__handler"></span>
    {/capture}

    {capture name="object_template_add_content"}
        {if !$runtime.simple_ultimate}
            <div class="select2__category-company">{$company_name}</div>
        {/if}
    {/capture}
    <template id="template_selection_category">
        {include file="common/select2/components/object_selection.tpl"
            content=$smarty.capture.object_template_add_content
            content_pre=$smarty.capture.template_selection_category_pre
        }
    </template>
    <template id="template_result_add_category">
        {include file="common/select2/components/object_result.tpl"
            content=$smarty.capture.object_template_add_content
            content_pre=$smarty.capture.template_selection_category_pre
            prefix=__("add")
            icon="icon-plus-sign"
            help=__("enter_category_name_and_path")
        }
    </template>
</div>
