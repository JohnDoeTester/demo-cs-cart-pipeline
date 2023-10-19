{*
array  $id                 Storefront ID
array  $all_countries      All countries
int[]  $selected_countries Storefront country IDs
string $input_name         Input name
*}

{$input_name = $input_name|default:"storefront_data[country_codes]"}

<input type="hidden"
       name="{$input_name}"
       value=""
/>

{include file="common/double_selectboxes.tpl"
    title=__("countries")
    first_name=$input_name
    first_data=$selected_countries
    second_name="all_countries"
    second_data=$all_countries
}
