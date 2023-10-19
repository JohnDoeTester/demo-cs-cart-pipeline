{script src="js/tygh/backend/shippings.js"}
<div class="row-fluid">
    <div class="span6 pull-right">
        <div class="well well-small" id="rates">
            <input type="hidden" name="result_ids" value="rates">
            <h3>{__("calculated_rate")}</h3>
            <table class="table">
                <tr>
                    <td><p>{__("rate_area")}:</p></td>
                    <td>
                        {if $shipping.rate_info.destination_id && $shipping.rate_info.destination_name}
                            <a href="{"destinations.update?destination_id=`$shipping.rate_info.destination_id`"|fn_url}" target="_blank">
                                {$shipping.rate_info.destination_name}
                            </a>
                        {else}
                            {__("NA")}
                        {/if}
                    </td>
                </tr>
                <tr>
                    <td><p>{__("delivery_time")}:</p></td>
                    <td><p>{$rates.service_delivery_time|default:__("NA")}</p></td>
                </tr>
                <tr>
                    <td><b>{__("cost")}</b>:</td>
                    <td>
                        {if $rates.price}
                            <b>{include file="common/price.tpl" value=$rates.price}</b>
                        {/if}
                    </td>
                </tr>
                <tr {if !$rates.error}class="hidden"{/if}>
                    <td class="error" colspan="2">
                        <b>{__("error")}</b>
                        <b>{$rates.error}</b>
                    </td>
                </tr>
                <tr>
                    <td colspan="2">
                        {include file="buttons/button.tpl"
                        but_role="action"
                        but_name="dispatch[shippings.test]"
                        but_text=__("recalculate_rates")
                        but_meta="cm-submit cm-ajax cm-rates-calculate"
                        but_icon="icon-refresh"
                        }
                    </td>
                </tr>
            </table>
            <!--rates--></div>
        </div>
    <div class="span6">
        <div class="control-group">
            <label for="elm_weight_cost" class="control-label">{__("weight")} ({$settings.General.weight_symbol})</label>
            <div class="controls">
                <input id="elm_weight_cost" type="text" class="input-medium cm-rate-calculation" name="shipping_data[test_weight]" value="1" />
                <div>
                    {$weights = [1, 5, 10, 50, 100]}
                    {foreach $weights as $weight}
                        {include file="buttons/button.tpl" but_text=$weight but_meta="label cm-btn-weight" but_role="button-icon" but_external_click_id="elm_weight_cost" but_id="btn_weight_`$weight`"}
                    {/foreach}
                    <p class="muted description">{__("rates_calculated_info", [1, "[price]" => $app["formatter"]->asPrice(100, $primary_currency)])}</p>
                </div>
            </div>
        </div>
        {hook name="shippings:calculate_cost"}
        {include file="common/subheader.tpl" title=__("recipient") target="#recipient_info"}
        <fieldset id="recipient_info" class="collapse-visible collapse in">
            <div id="container_field__company_country" class="control-group">
                <label for="field__company_country" class="control-label">{__("country")}</label>
                <div class="controls">
                    <select id="field__recipient_country" class="cm-country cm-rate-calculation cm-location-recipient" name="recipient[country]">
                        <option value="">- {__("select_country")} -</option>
                        {foreach $countries as $code => $country}
                            <option value="{$code}" {if $code === $recipient.country}selected="selected"{/if}>{$country}</option>
                        {/foreach}
                    </select>
                </div>
            </div>
            <div id="container_field__company_state" class="control-group">
                <label for="field__company_state" class="control-label">{__("state")}</label>
                <div class="controls">
                    <select class="cm-state cm-rate-calculation cm-location-recipient" name="recipient[state]" id="field__recipient_state">
                        <option value="">- {__("select_state")} -</option>
                        {foreach $states[$recipient.country] as $state}
                            <option value="{$state.code}" {if $state.code === $recipient.state}selected="selected"{/if}>{$state.state}</option>
                        {/foreach}
                    </select>
                    <input type="text" id="field__recipient_state_d" name="recipient[state]" value="{$recipient.state}" disabled="disabled" class="cm-state cm-location-recipient hidden" />
                </div>
            </div>
            <div id="container_field__company_city" class="control-group">
                <label for="field__company_city" class="control-label">{__("city")}</label>
                <div class="controls">
                    <input type="text" size="30" value="{$recipient.city}" name="recipient[city]" id="field__company_city" class="cm-rate-calculation"/>
                </div>
            </div>
            <div id="container_field__company_zipcode" class="control-group">
                <label for="field__company_zipcode" class="control-label">{__("zip_postal_code")}</label>
                <div class="controls">
                    <input type="text" value="{$recipient.zipcode}" name="recipient[zipcode]" id="field__company_zipcode" class="cm-rate-calculation"/>
                </div>
            </div>
            <div id="container_field__company_address" class="control-group">
                <label for="field__company_address" class="control-label">{__("address")}</label>
                <div class="controls">
                    <input type="text" value="{$recipient.address}" name="recipient[address]" id="field__company_address" class="cm-rate-calculation"/>
                </div>
            </div>
        </fieldset>
        {include file="common/subheader.tpl" title=__("sender") target="#sender_info"}
        <fieldset id="sender_info" class="collapse-visible collapse in">
            <div id="container_field__company_country" class="control-group">
                <label for="field__sender_country" class="control-label">{__("country")}</label>
                <div class="controls">
                    <select id="field__sender_country" class="cm-country cm-rate-calculation cm-location-sender" name="sender[country]">
                        <option value="">- {__("select_country")} -</option>
                        {foreach $countries as $code => $country}
                            <option value="{$code}" {if $code === $sender.country}selected="selected"{/if}>{$country}</option>
                        {/foreach}
                    </select>
                </div>
            </div>
            <div id="container_field__company_state" class="control-group">
                <label for="field__company_state" class="control-label">{__("state")}</label>
                <div class="controls">
                    <select class="cm-state cm-rate-calculation cm-location-sender" name="sender[state]" id="field__sender_state">
                        <option value="">- {__("select_state")} -</option>
                        {foreach $states[$sender.country] as $state}
                            <option value="{$state.code}" {if $state.code === $sender.state}selected="selected"{/if}>{$state.state}</option>
                        {/foreach}
                    </select>
                    <input type="text" id="field__sender_state_d" name="sender[state]" value="{$sender.state}" disabled="disabled" class="cm-state cm-location-sender hidden" />
                </div>
            </div>
            <div id="container_field__company_city" class="control-group">
                <label for="field__company_city" class="control-label">{__("city")}</label>
                <div class="controls">
                    <input type="text" size="30" value="{$sender.city}" name="sender[city]" id="field__company_city" class="cm-rate-calculation"/>
                </div>
            </div>
            <div id="container_field__company_zipcode" class="control-group">
                <label for="field__company_zipcode" class="control-label">{__("zip_postal_code")}</label>
                <div class="controls">
                    <input type="text" value="{$sender.zipcode}" name="sender[zipcode]" id="field__company_zipcode" class="cm-rate-calculation"/>
                </div>
            </div>
            <div id="container_field__company_address" class="control-group">
                <label for="field__company_address" class="control-label">{__("address")}</label>
                <div class="controls">
                    <input type="text" value="{$sender.address}" name="sender[address]" id="field__company_address" class="cm-rate-calculation"/>
                </div>
            </div>
        </fieldset>
        {/hook}
    </div>
</div>
<script>
    (function (_, $) {
        $.ceEvent('one', 'ce.commoninit', function (context) {
            $.ceRebuildStates('init', {
                default_country: '{$settings.Checkout.default_country|escape:javascript}',
                states: {$states|json_encode nofilter}
            });
            $('.cm-country.cm-location-recipient').ceRebuildStates();
            $('.cm-country.cm-location-sender').ceRebuildStates();
        });
    }(Tygh, Tygh.$));
</script>
