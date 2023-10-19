{if $searchanise_api_key && $searchanise_search_allowed == "YesNo::YES"|enum}

{$se_service_url = $smarty.const.SE_SERVICE_URL}

{literal}
<style>
div.snize-ac-results{
    margin-left:15px !important;
}
#search_input.snize-ac-loading{
    background-position:90% center !important;
}
</style>
{/literal}
<script>
    Searchanise = {ldelim}{rdelim};
    Searchanise.host = '{$se_service_url}';
    Searchanise.api_key = '{$searchanise_api_key}';

    Searchanise.AutoCmpParams = {ldelim}{rdelim};
    Searchanise.AutoCmpParams.restrictBy = {ldelim}{rdelim};
    Searchanise.AutoCmpParams.restrictBy.status = '{"ObjectStatuses::ACTIVE"|enum}';
    Searchanise.AutoCmpParams.restrictBy.empty_categories = '{"YesNo::NO"|enum}';
    Searchanise.AutoCmpParams.restrictBy.usergroup_ids = '{"|"|join:$auth.usergroup_ids}';
    Searchanise.AutoCmpParams.restrictBy.category_usergroup_ids = '{"|"|join:$auth.usergroup_ids}';
{if $addons.age_verification.status == "ObjectStatuses::ACTIVE"|enum}
    Searchanise.AutoCmpParams.restrictBy.age_limit = {if $smarty.session.auth.age && $smarty.const.AREA == "C"}'0,{$smarty.session.auth.age}'{else}',0'{/if};
{/if}
{if $addons.vendor_data_premoderation.status == "ObjectStatuses::ACTIVE"|enum}
    Searchanise.AutoCmpParams.restrictBy.approved = '{"YesNo::YES"|enum}'; {* Support for add-on "Vendor data premoderation". *}
{/if}
{if $addons.product_variations.status == "ObjectStatuses::ACTIVE"|enum}
    Searchanise.AutoCmpParams.restrictBy.product_type = '{"\Tygh\Addons\ProductVariations\Product\Type\Type::PRODUCT_TYPE_SIMPLE"|constant}';
{/if}
{if $addons.master_products.status == "ObjectStatuses::ACTIVE"|enum}
    Searchanise.AutoCmpParams.restrictBy.master_product_status = '{"ObjectStatuses::ACTIVE"|enum}';
    Searchanise.AutoCmpParams.restrictBy.company_id = '{if $company_id}{$company_id}{else}0{/if}';
{/if}
{if $searchanise_prices}
    Searchanise.AutoCmpParams.union = {ldelim}{rdelim};
    Searchanise.AutoCmpParams.union.price = {ldelim}{rdelim};
    Searchanise.AutoCmpParams.union.price.min = '{$searchanise_prices}';
{/if}
{if $settings.General.inventory_tracking !== "YesNo::NO"|enum && $settings.General.show_out_of_stock_products == "YesNo::NO"|enum && $smarty.const.AREA == "C"}
    Searchanise.AutoCmpParams.restrictBy.amount = '1,';
{/if}
{if "MULTIVENDOR"|fn_allowed_for}
    Searchanise.AutoCmpParams.restrictBy.active_company = '{"YesNo::YES"|enum}';
{/if}
{if $runtime.controller == "companies" && $runtime.mode == "products"}
    Searchanise.AutoCmpParams.restrictBy.company_id = '{$company_id}';
    Searchanise.AutoCmpParams.restrictBy.category_id = '{$category_data.category_id}';
{/if}
    Searchanise.options = {ldelim}{rdelim};
    Searchanise.options.PriceFormat = {ldelim}rate : {$currencies[$secondary_currency].coefficient}, decimals: {$currencies[$secondary_currency].decimals}, decimals_separator: '{$currencies[$secondary_currency].decimals_separator|escape:javascript nofilter}', thousands_separator: '{$currencies[$secondary_currency].thousands_separator|escape:javascript nofilter}', symbol: '{$currencies[$secondary_currency].symbol|escape:javascript nofilter}', after: {if $currencies[$secondary_currency].after == "YesNo::NO"|enum}false{else}true{/if}{rdelim};
    Searchanise.AdditionalSearchInputs = '#additional_search_input';
    Searchanise.SearchInput = '#search_input,form[name="search_form"] input[name="hint_q"],form[name="search_form"] input[name="q"]';
</script>

{hook name="index:searchanise_init"}{/hook}

<script>
    Tygh.$.ceEvent('on', 'ce.commoninit', function(context) {
        // Re-initialize Searchanise widget if its search input was updated after AJAX request
        if (typeof(Searchanise) !== 'undefined' && Searchanise.Loaded && typeof(Searchanise.SetOptions) === 'function' && Tygh.$(Searchanise.SearchInput, context).length) {
            Searchanise.SetOptions({ SearchInput: Tygh.$(Searchanise.SearchInput) });
            Searchanise.AutocompleteClose();
            Searchanise.Start();
        }
    });

    (function() {
        var __se = document.createElement('script');
        __se.src = '{$se_service_url}/widgets/v1.0/init.js';
        __se.setAttribute('async', 'true');
        var s = document.getElementsByTagName('script')[0]; s.parentNode.insertBefore(__se, s);
    })();
</script>

{/if}

{if !$config.tweaks.se_use_custom_frontend_sync_call}
<script>
    Tygh.$(document).ready(function() {
        Tygh.$.get('{'searchanise.async?no_session=Y&is_ajax=3'|fn_url:'C':'current' nofilter}');
    });
</script>
{/if}
