<div id="content_tab_payments_by_country_countries_{$id}">
    <select name="payment_data[country_selection_mode]" class="input-full">
        <option value="{"Addons\\PaymentsByCountry\\CountrySelectionMode::SHOW"|enum}" {if $payment.country_selection_mode == "Addons\\PaymentsByCountry\\CountrySelectionMode::SHOW"|enum}selected{/if}>{__("payments_by_country.show_payment_method_in_selected_countries")}</option>
        <option value="{"Addons\\PaymentsByCountry\\CountrySelectionMode::HIDE"|enum}" {if $payment.country_selection_mode == "Addons\\PaymentsByCountry\\CountrySelectionMode::HIDE"|enum || $id == 0}selected{/if}>{__("payments_by_country.hide_payment_method_in_selected_countries")}</option>
    </select>
    {$input_name = "payment_data[country_codes]"}

    {include file="common/double_selectboxes.tpl"
        title=""
        first_name=$input_name
        first_data=$selected_countries
        second_name="all_countries_{$id}"
        second_data=$all_countries
    }
    <!--content_tab_payments_by_country_countries_{$id}-->
</div>
