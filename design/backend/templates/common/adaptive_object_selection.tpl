{*
    $is_disabled                  bool   Whether picker is blocked
    $label_text                   string Input field label
    $placeholder                  string Input field placeholder
    $load_items_url               string URL to load data from
    $allow_add                    bool   Whether it's allowed to create new variants for picker
    $template_result_new_selector string `templateResult` selector
    $template_result_selector     string `templateSelection` selector
    $items                        array  All variants
    $item_ids                     array  Selected picker variants
    $type                         string Picker type
    $storefront_id                int    Storefront ID
    $input_name                   string Select element name
    $input_id                     string Select element id
    $close_on_select              bool   If hide picker when selected
    $class_prefix                 string Class prefix
    $items_limit                  int    Sets the limit for choosing a template
    $id_field                     string Indicates which field contains the id value
    $name_field                   string Indicates which field contains the name value
*}

{$items_limit = $items_limit|default:5}

{if $items|@count > $items_limit}
    {include file="common/multiple_select_picker.tpl"
        items=$items
        item_ids = $item_ids
        storefront_id = $storefront_id
        type = $type
        id_field=$id_field
        name_field=$name_field
        load_items_url=$load_items_url
        input_name="`$input_name`[]"
        class_prefix=$class_prefix
        close_on_select=$close_on_select
        is_disabled=$is_disabled
        template_result_selector=$template_result_selector
        template_result_new_selector=$template_result_new_selector
        allow_add=$allow_add
    }
{else}
    {include file="common/multiple_checkboxes.tpl"
        input_name=$input_name
        input_id=$input_id
        item_ids=$item_ids
        items=$items
        id_field=$id_field
        name_field=$name_field
    }
{/if}