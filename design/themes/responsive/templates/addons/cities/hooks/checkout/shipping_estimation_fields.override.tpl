{$customer_loc = $customer_loc|default:$cart.user_data}
{$_state = $customer_loc.s_state}
{$_country = $customer_loc.s_country}
{$_city = $customer_loc.s_city}

{if !isset($cart.user_data.s_country)}
    {$_country = $settings.Checkout.default_country}
{/if}

{if !isset($cart.user_data.s_state) && $_country == $settings.Checkout.default_country}
    {$_state = $settings.Checkout.default_state}
{/if}

<div class="ty-control-group">
    <label class="ty-control-group__label cm-required" for="{$prefix}elm_country{$id_suffix}">{__("country")}</label>
    <select id="{$prefix}elm_country{$id_suffix}" class="cm-country cm-location-estimation{$class_suffix} ty-input-text-medium" name="customer_location[country]">
        <option value="">- {__("select_country")} -</option>
        {foreach $countries as $code => $country}
            <option value="{$code}" {if $_country == $code}selected="selected"{/if}>{$country}</option>
        {/foreach}
    </select>
</div>

<div class="ty-control-group">
    <label class="ty-control-group__label" for="{$prefix}elm_state{$id_suffix}">{__("state")}</label>
    <select class="cm-state cm-location-estimation{$class_suffix} {if !$states[$_country]}hidden{/if} ty-input-text-medium" id="{$prefix}elm_state{$id_suffix}{if !$states[$_country]}_d{/if}" name="customer_location[state]">
        <option value="">- {__("select_state")} -</option>
        {foreach $states[$_country] as $state}
            <option value="{$state.code}" {if $state.code == $_state}selected="selected"{/if}>{$state.state}</option>
        {/foreach}
    </select>
    <input type="text" class="cm-state cm-location-estimation{$class_suffix} ty-input-text-medium {if $states[$_country]}hidden{/if}" id="{$prefix}elm_state{$id_suffix}{if $states[$_country]}_d{/if}" name="customer_location[state]" size="20" maxlength="64" value="{$_state}" {if $states[$_country]}disabled="disabled"{/if} />
</div>

<div id="change_city{$id_suffix}">
    {if $cities}
        <div class="ty-control-group">
            <label class="ty-control-group__label" for="{$prefix}elm_city{$id_suffix}">{__("city")}</label>
            <select class="cm-location-estimation{$class_suffix} ty-input-text-medium" id="{$prefix}elm_city{$id_suffix}" name="customer_location[city]">
                <option label="" value="">-- {__("select_city")} --</option>
                {foreach $cities as $city}
                    {$city_found = $city_found|default:false || $city.city == $_city}
                    <option value="{$city.city}"
                            {if $city.city == $_city}selected="selected"{/if}
                    >{$city.city}</option>
                {/foreach}
                <option value="client_city"
                    {if !$city_found}selected="selected"{/if}
                    >-- {__("other_town")} --
                </option>
            </select>
        </div>

        <div id="client_city" class="ty-control-group {if $city_found}hidden{/if}">
            <label class="ty-control-group__label"
                   for="{$prefix}elm_city_text{$id_suffix}"
            >{__("other_town")}</label>
            <input type="text"
                   class="ty-input-text-medium"
                   id="{$prefix}elm_city_text{$id_suffix}"
                   name="customer_location[city]"
                   value="{$_city}"
                   {if $city_found}disabled="disabled"{/if}
            />
        </div>
    {else}
        <div class="ty-control-group">
            <label  class="ty-control-group__label">{__("city")}</label>
            <input type="text" class="ty-input-text-medium" id="{$prefix}elm_city{$id_suffix}" name="customer_location[city]" {if $cart.user_data.s_city}value="{$cart.user_data.s_city}"{elseif $client_city}value="{$client_city}"{/if} autocomplete="on" />
        </div>
    {/if}
<!--change_city{$id_suffix}--></div>

<div class="ty-control-group">
    <label class="ty-control-group__label" for="{$prefix}elm_zipcode{$id_suffix}">{__("zip_postal_code")}</label>
    <input type="text" class="ty-input-text-medium" id="{$prefix}elm_zipcode{$id_suffix}" name="customer_location[zipcode]" size="20" value="{$cart.user_data.s_zipcode}" />
</div>

<script  class="cm-ajax-force">
    //<![CDATA[

    (function(_, $) {

        function fn_get_cities() {
            var country = $("#{$prefix}elm_country{$id_suffix}").length
                ? $("#{$prefix}elm_country{$id_suffix}").val()
                : '';
            var state = $("#{$prefix}elm_state{$id_suffix}").length
                ? $("#{$prefix}elm_state{$id_suffix}").val()
                : '';
            var city = $("#{$prefix}elm_city{$id_suffix}").val();
            var city_text = $("#elm_city_text").val();

            var url = fn_url('city.shipping_estimation_city');

            var data = {
                check_country: country,
                check_state: state,
                check_city: city,
                city_text: city_text,
                additional_id: '{$additional_id}'
            };

            $.ceAjax('request', url, {
                result_ids: 'change_city{$id_suffix}',
                method: 'get',
                data: data,
                callback: function(response) {
                    $('#elm_city_text{$id_suffix}').attr('disabled', 'disabled').val('');
                    $('#client_city').addClass('hidden');
                    $('#{$prefix}elm_city{$id_suffix}').val('');
                }
            });
        }

        $.ceEvent('on', 'ce.commoninit', function (context) {

            var $city_input =  $('#elm_city_text{$id_suffix}', context),
                $city_input_wrapper = $('#client_city', context);

            $('#but_get_rates{$id_suffix}', context).click(function() {
                var $dialog = $(this).closest('.ui-dialog');
                var zIndex  = $dialog.css('z-index');

                if($dialog.length) {
                    $('.notification-container').css('z-index', zIndex + 1);
                }
            });

            $('#{$prefix}elm_country{$id_suffix}', context).change(function() {
                fn_get_cities();
            });

            $('#{$prefix}elm_state{$id_suffix}', context).change(function() {
                fn_get_cities();
            });

            $('#{$prefix}elm_city{$id_suffix}', context).change(function() {
                var inp = $(this).val();

                if (inp === 'client_city') {
                    $city_input.removeAttr('disabled').val('');
                    $city_input_wrapper.removeClass('hidden');
                } else {
                    $city_input.attr('disabled', 'disabled').val('');
                    $city_input_wrapper.addClass('hidden');
                }

                $.ceDialog('get_last').ceDialog('reload');
            });
        });

    }(Tygh, Tygh.$));
    //]]>
</script>
