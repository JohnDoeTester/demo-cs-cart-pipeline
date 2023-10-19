{include file = "addons/vendor_locations/components/search_address.tpl"
    vendor_location=$company_data.vendor_location
    id="elm_company_location"
    disabled=!$is_allowed_to_update_companies
    input_value_disabled=true
    class=(!$is_allowed_to_update_companies) ? "cm-no-hide-input" : ""
}
