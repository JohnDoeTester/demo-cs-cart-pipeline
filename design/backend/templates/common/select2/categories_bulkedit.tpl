{script src="js/tygh/backend/categories.js"}

{include file="views/categories/components/picker/picker.tpl"
    input_name="product_data[category_ids][]"
    simple_class="cm-field-container"
    multiple=true
    id="{$select_id}"
    data-ca-picker-id="categories_{$select2_select_id}"
    tabindex=$tabindex
    item_ids=$bulk_edit_ids_flat
    meta="object-categories-add object-categories-add--multiple object-categories-add--bulk-edit cm-object-categories-add-container"
    select_class="cm-bulk-edit-object-categories-add `$select_class`"
    show_advanced=true
    allow_add=false
    allow_sorting=true
    result_class="object-picker__result--inline"
    selection_class="object-picker__selection--product-categories"
    required=true
    close_on_select=false
    allow_multiple_created_objects=true
    created_object_holder_selector="[name='product_data[add_new_category][]']"
    is_bulk_edit=true
    has_selection_controls=true
    has_removable_items=false
    is_tristate_checkbox=true
}