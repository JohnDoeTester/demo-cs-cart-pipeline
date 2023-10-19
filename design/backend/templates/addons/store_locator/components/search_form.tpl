<div class="sidebar-row">
    <h6>{__("admin_search_title")}</h6>
    <form action="{""|fn_url}" name="stores_search_form" method="get" class="{$form_meta}">
        {hook name="store_locator:search"}
            <div class="sidebar-field">
                <label for="elm_rate_area">{__("rate_area")}</label>
                <select id="elm_rate_area" name="main_destination_id">
                    <option value="" {if empty($search.main_destination_id)} selected="selected"{/if}>{__("store_locator.any_rate_area")}</option>
                    {foreach from=$destinations item="rate_area" key="code"}
                        <option {if $code == $search.main_destination_id}selected="selected"{/if} value="{$code}">{$rate_area.destination}</option>
                    {/foreach}
                </select>
            </div>
            {if "MULTIVENDOR"|fn_allowed_for && !$runtime.company_id}
                <div class="sidebar-field">
                    <label for="elm_owner">{__("owner")}</label>
                    {include file="views/companies/components/picker/picker.tpl"
                        input_name="company_id"
                        show_advanced=false
                        show_empty_variant=true
                        item_ids=($search.company_id) ? [$search.company_id] : []
                        empty_variant_text=__("store_locator.any")
                        predefined_variants=["marketplace" => __("marketplace")]
                    }
                </div>
            {/if}
            <div class="sidebar-field">
                <label for="elm_city">{__("city")}</label>
                <input type="text" name="city" id="elm_city" value="{$search.city}">
            </div>
        {/hook}

        <div class="sidebar-field">
            <input class="btn" type="submit" name="dispatch[{$dispatch}]" value="{__("admin_search_button")}">
        </div>
    </form>
</div>