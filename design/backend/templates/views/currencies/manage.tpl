{script src="js/tygh/tabs.js"}

{capture name="mainbox"}

    {assign var="r_url" value=$config.current_url|escape:url}
    <div class="items-container {if $is_allow_update_currencies}cm-sortable{/if} {if !""|fn_allow_save_object:"":true} cm-hide-inputs{/if}"
         data-ca-sortable-table="currencies" data-ca-sortable-id-name="currency_id" id="manage_currencies_list">
        {if $currencies_data}
        <div class="table-responsive-wrapper">
            <table class="table table-middle table--relative table-objects table-striped table-responsive table-responsive-w-titles">
                <tbody>
                {foreach from=$currencies_data item="currency"}
                    {if $currency.is_primary == "Y"}
                        {assign var="_href_delete" value=""}
                    {else}
                        {assign var="_href_delete" value="currencies.delete?currency_id=`$currency.currency_id`"}
                    {/if}
                    {assign var="currency_details" value="<span>`$currency.currency_code`</span>, {__("currency_rate")}: <span>`$currency.coefficient`</span>, {__("currency_sign")}: <span>`$currency.symbol`</span>"}

                    {capture name="tool_items"}
                        {hook name="currencies:list_extra_links"}{/hook}
                    {/capture}

                    {capture name="extra_data"}
                        {hook name="currencies:extra_data"}{/hook}
                    {/capture}

                    {include file="common/object_group.tpl"
                        id=$currency.currency_id
                        text=$currency.description
                        details=$currency_details
                        href="currencies.update?currency_id=`$currency.currency_id`&return_url=$r_url"
                        href_delete=$_href_delete
                        delete_data=$currency.currency_code
                        delete_target_id="manage_currencies_list"
                        header_text=$currency.description
                        table="currencies"
                        object_id_name="currency_id"
                        status=$currency.status
                        additional_class=($is_allow_update_currencies)?"cm-sortable-row cm-sortable-id-`$currency.currency_id`":""
                        no_table=true
                        non_editable=$runtime.company_id
                        is_view_link=true
                        draggable=$is_allow_update_currencies
                        hidden=true
                        update_controller="currencies"
                        st_result_ids="manage_currencies_list"
                        tool_items=$smarty.capture.tool_items
                        extra_data=$smarty.capture.extra_data
                    }
                {/foreach}
                </tbody>
            </table>
        </div>
        {else}
            <p class="no-items">{__("no_data")}</p>
        {/if}
    <!--manage_currencies_list--></div>

    <div class="buttons-container">
        {capture name="extra_tools"}
            {hook name="currencies:import_rates"}{/hook}
        {/capture}
    </div>
    {if
        $is_allow_update_currencies
        && ""|fn_allow_save_object:"":true
    }
        {capture name="adv_buttons"}
            {capture name="add_new_picker"}
                {include file="views/currencies/update.tpl" currency=[]}
            {/capture}

            {include file="common/popupbox.tpl" id="add_new_currency" text=__("new_currency") content=$smarty.capture.add_new_picker title=__("add_currency") act="general" icon="icon-plus"}
        {/capture}
    {/if}
{/capture}

{capture name="sidebar"}
    {hook name="currencies:manage_sidebar"}
    <div class="sidebar-row exchange-rates">
        <h6 class="hidden exchange-rates__header">{__("exchange_rate")}</h6>
        <ul class="unstyled currencies-rate" id="currencies_stock_exchange">
        </ul>
        <span class="hidden exchange-rates__error">{__("error_exchange_rate")}</span>
    </div>
    <script>

        var exchangeRate = {

            primary_currency: "{$primary_currency}",

            api_key: '{$config.alpha_vantage_api_key}',

            init: function() {

                // Check if primary_currency is valid else use USD as default value
                exchangeRate.getRate(exchangeRate.primary_currency, 'USD', exchangeRate.getAllCurrency);

                $.ceEvent('on', 'ce.form_confirm', function(elm) {
                    var code = elm.data('caParams');

                    if(code !== 'EUR' && code !== 'GBP' && code !== 'CHF') {
                        $('li[data-ca-currency-code="' + code + '"]').remove();
                    }
                });
            },

            getAllCurrency: function(data){
                var currencies = ['USD', 'EUR', 'GBP', 'CHF'];

                if (data.RealtimeCurrencyExchangeRate !== undefined) {
                    var default_rate = data.RealtimeCurrencyExchangeRate.ExchangeRate !== undefined
                        ? parseFloat(data.RealtimeCurrencyExchangeRate.ExchangeRate)
                        : 0
                } else {
                    $('.exchange-rates__header, .exchange-rates__error').removeClass('hidden');
                }

                if (typeof(default_rate) !== "number" || default_rate === 0) {
                    exchangeRate.primary_currency = 'USD';
                }

                {foreach $currencies_data as $currency}
                    {if $currency.currency_code != "EUR"
                        && $currency.currency_code != "GBP"
                        && $currency.currency_code != "CHF"
                        && $currency.currency_code != "USD"
                    }
                        currencies.push("{$currency.currency_code}");
                    {/if}
                {/foreach}

                $.each(currencies, function(index, value) {
                    exchangeRate.getRate(value, exchangeRate.primary_currency);
                });
            },

            getRate: function (from, to, callback) {
                callback = callback || exchangeRate.parseExchangeRate;

                var url = 'https://www.alphavantage.co/query?function=CURRENCY_EXCHANGE_RATE' +
                    '&from_currency=' + from +
                    '&to_currency=' + to +
                    '&apikey=' + exchangeRate.api_key +
                    '&ts={$smarty.const.TIME}';

                $.ajax({
                    type: "GET",
                    url: url,
                    success: function(data, textStatus, jqxhr) {
                        if (callback) {
                            data = exchangeRate.normalizeData(data);
                            callback(data, textStatus, jqxhr);
                        }
                    },
                    dataType: "json",
                    cache: false
                });
            },

            normalizeData: function(data) {
                var normalized_obj = new Object();

                for (var key in data) {
                    var property_value = data[key],
                        properety_name = key.replace(/[\s_]+/g, '').replace(/^\d+\./, '');

                    if (typeof(data[key]) === 'object') {
                        property_value = exchangeRate.normalizeData(data[key]);
                    }

                    normalized_obj[properety_name] = property_value;
                }

                return normalized_obj;
            },

            parseExchangeRate: function(data) {
                if (data.RealtimeCurrencyExchangeRate !== undefined) {
                    var name = data.RealtimeCurrencyExchangeRate.FromCurrencyCode !== undefined
                        ? data.RealtimeCurrencyExchangeRate.FromCurrencyCode
                        : null;
                    var rate = data.RealtimeCurrencyExchangeRate.ExchangeRate !== undefined
                        ? parseFloat(data.RealtimeCurrencyExchangeRate.ExchangeRate)
                        : null;
                }

                var container = Tygh.$('#currencies_stock_exchange');

                if (rate && name && name !== exchangeRate.primary_currency) {
                    function asc_sort(a, b){
                        return ($(b).text()) < ($(a).text()) ? 1 : -1;
                    }
                    container.append('<li data-ca-currency-code="' + name + '">' + name + '/' + exchangeRate.primary_currency + '<span class="pull-right muted">' + rate + '</span></li>');
                    container.find('li').sort(asc_sort).appendTo(container);
                    $('.exchange-rates__header').removeClass('hidden');
                }
            }
        };

        exchangeRate.init();
    </script>
    {/hook}
    {capture name="important"}
        {hook name="currencies:important_text"}
            <p>{__('important_currency_text', ['[link]' => 'settings.manage?section_id=Appearance'|fn_url]) nofilter}</p>
        {/hook}
    {/capture}
    {include file="common/sidebox.tpl" content=$smarty.capture.important title=__('important')}
{/capture}

{capture name="buttons"}
    {capture name="tools_list"}
        {hook name="currencies:manage_tools_list"}
        {/hook}
    {/capture}
    {dropdown content=$smarty.capture.tools_list}
{/capture}

{include file="common/mainbox.tpl" title=__("currencies") content=$smarty.capture.mainbox sidebar=$smarty.capture.sidebar select_languages=true buttons=$smarty.capture.buttons adv_buttons=$smarty.capture.adv_buttons}
