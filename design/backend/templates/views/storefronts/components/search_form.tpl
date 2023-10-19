{*
string $class          Form class
string $dispatch       Form dispatch
array  $search         Storefronts search parameters
array  $all_languages  All languages
array  $all_currencies All currencies
array  $all_countries  All countries
bool   $in_popup       Whether a search form is show in popup
string $extra          Additional inputs for search form
string $select_mode    Storefront selection mode
*}
{if $in_popup}
    <div class="adv-search">
        <div class="group">
{else}
    <div class="sidebar-row">
        <h6>{__("admin_search_title")}</h6>
{/if}
<form name="storefronts_search_form"
      action="{""|fn_url}"
      method="get"
      class="{$class}"
>
    <input type="hidden"
           name="select_mode"
           value="{$select_mode}"
    />

    {capture name="simple_search"}
        {$extra nofilter}

        <div class="sidebar-field">
            <label for="elm_name"
            >{__("name")}</label>
            <input type="text"
                   name="name"
                   id="elm_name"
                   value="{$search.name}"
            />
        </div>
        <div class="sidebar-field">
            <label for="elm_url"
            >{__("url")}</label>
            <input type="text"
                   name="url"
                   id="elm_url"
                   value="{$search.url}"
            />
        </div>
        <div class="sidebar-field">
            <label for="elm_status"
            >{__("status")}</label>
            <select name="status"
                    id="elm_status"
            >
                <option value=""
                >{__("all")}</option>
                <option value="{"StorefrontStatuses::OPEN"|enum}"
                        {if $search.status === "StorefrontStatuses::OPEN"|enum}
                            selected
                        {/if}
                >{"ON"}</option>
                <option value="{"StorefrontStatuses::CLOSED"|enum}"
                        {if $search.status === "StorefrontStatuses::CLOSED"|enum}
                            selected
                        {/if}
                >{"OFF"}</option>
            </select>
        </div>
    {/capture}
    {capture name="advanced_search"}
        <div class="row-fluid">
            <div class="group span6 form-horizontal">
                <div class="control-group">
                    <label class="control-label"
                           for="elm_languages"
                    >{__("languages")}</label>
                    <div class="controls advanced_search__localization">
                        {include file="common/adaptive_object_selection.tpl"
                            input_name="language_ids"
                            input_id="elm_languages"
                            item_ids=$search.language_ids
                            items=$languages
                            id_field="lang_id"
                            name_field="name"
                            type="languages"
                            load_items_url="languages.selector"
                            class_prefix="localization"
                            close_on_select="false"
                        }
                    </div>
                </div>
            </div>
            <div class="group span6 form-horizontal">
                <div class="control-group">
                    <label class="control-label"
                           for="elm_currencies"
                    >{__("currencies")}</label>
                    <div class="controls advanced_search__localization">
                        {include file="common/adaptive_object_selection.tpl"
                            input_name="currency_ids"
                            input_id="elm_currencis"
                            item_ids=$search.currency_ids
                            items=$currencies
                            id_field="currency_id"
                            name_field="description"
                            type="currencies"
                            load_items_url="currencies.selector"
                            class_prefix="localization"
                            close_on_select="false"
                        }
                    </div>
                </div>
            </div>
        </div>
        <div class="row-fluid">
            <div class="group span12 form-horizontal">
                <div class="control-group">
                    <label for="elm_countries"
                           class="control-label"
                    >{__("countries")}</label>
                    <div class="controls">
                        <select name="country_codes[]"
                                multiple="multiple"
                                id="elm_countries"
                                size="10"
                        >
                            {foreach $all_countries as $country_code => $country}
                                <option value="{$country_code}"
                                        {if in_array($country_code, $search.country_codes)}
                                            selected
                                        {/if}
                                >{$country}</option>
                            {/foreach}
                        </select>
                    </div>
                </div>
            </div>
        </div>
        <div class="row-fluid">
            <div class="group span12 form-horizontal">
                <div class="control-group">
                    <label for="elm_companies"
                           class="control-label"
                    >{__("vendors")}</label>
                    <div class="controls">
                        {include file="pickers/companies/picker.tpl"
                            show_add_button=true
                            multiple=true
                            item_ids=$search.company_ids
                            view_mode="list"
                            input_name="company_ids"
                            checkbox_name="company_ids"
                            no_item_text=__("all_vendors")
                        }
                    </div>
                </div>
            </div>
        </div>
    {/capture}

    {include file="common/advanced_search.tpl"
        simple_search=$smarty.capture.simple_search
        advanced_search=$smarty.capture.advanced_search
        dispatch=$dispatch
        view_type="storefronts"
        in_popup=$in_popup
        not_saved=true
    }
</form>
{if $in_popup}
    </div></div>
{else}
    </div>
{/if}
