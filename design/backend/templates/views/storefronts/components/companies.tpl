{*
int[]  $selected_companies Storefront company IDs
string $input_name         Input name
*}

{$input_name = $input_name|default:"storefront_data[company_ids]"}

{include file="pickers/companies/picker.tpl"
    show_add_button=true
    multiple=true
    item_ids=$selected_companies
    view_mode="list"
    input_name=$input_name
    checkbox_name=$input_name
    no_item_text=__("all_companies")
}

