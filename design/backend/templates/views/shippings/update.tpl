{if $shipping}
    {$id=$shipping.shipping_id}
{else}
    {$id=0}
{/if}

{$storefront_owner_id = false}
{if $shipping.storefront_owner_id}
    {$storefront_owner_id = $shipping.storefront_owner_id}
{/if}

{$allow_save=$shipping|fn_allow_save_object:"shippings"}
{$manual = "M"}
{$realtime = "R"}

<script>
(function(_, $) {

    {* array_keys is required to keep the ordering of the list *}
    var services_data = {$services|array_values|json_encode nofilter};
    var service_id = {$shipping.service_id|default:0};

    $(document).ready(function() {

        $('#elm_carrier').on('change', function() {
            var self = $(this);

            var services = $('#elm_service');
            var option = self.find('option:selected');
            var options = '';

            if (option.data('caShippingModule') === '{$manual}') {
                $('#elm_service_group').addClass('hidden');
                $('#input_elm_rate_calculation').val('{$manual}');
                $('#configure').hide();
            } else if (option.data('caShippingModule') === 'store_locator') {
                $('#elm_service_group').addClass('hidden');
                $('#input_elm_rate_calculation').val('{$realtime}');
            } else {
                $('#elm_service_group').removeClass('hidden');
                $('#input_elm_rate_calculation').val('{$realtime}');
            }

            services.prop('length', 0);
            for (var k in services_data) {
                if (services_data[k]['module'] === option.data('caShippingModule')) {
                    options += '<option data-ca-shipping-code="' + services_data[k]['code'] +'" data-ca-shipping-module="' + services_data[k]['module'] + '" value="' + services_data[k]['service_id'] + '" ' + (services_data[k]['service_id'] == service_id ? 'selected="selected"' : '') + '>' + services_data[k]['description'] + '</option>';
                }
            }
            services.append(options);
            services.trigger('change');
        });

        $('#elm_service').on('change', function() {
            var self = $(this);
            var option = self.find('option:selected');
            var tabReload = {
                isRequired: true,
            };

            $.ceEvent('trigger', 'ce.shippings.service_changed', [self, option, tabReload]);

            if (tabReload.isRequired === false) {
                return;
            }

            var href = fn_url('shippings.configure?shipping_id={$id}&module=' + option.data('caShippingModule') + '&code=' + option.data('caShippingCode'));
            var tab = $('#configure');

            if (tab.find('a').prop('href') !== href) {

                // Check if configure is active tab.
                if($('[name="selected_section"]').val() === 'configure') {
                    setTimeout(function() {
                        $('#configure a').click();
                    }, 100);
                }

                $('#content_configure').remove();
                tab.find('a').prop('href', href);
            }

            if ($('#input_elm_rate_calculation').val() === "{$realtime}") {
                tab.show();
            }
        });

        if ($('#available_for_new_vendors').length !== 0) {
            var shippingId = $('[name="shipping_id"]').val();

            $(_.doc).on('change', '#shipping_data_' + shippingId, function() {
                var companyId = + $(this).val();

                $('#available_for_new_vendors').prop('disabled', companyId !== 0);
            });
        }

    });
}(Tygh, Tygh.$));
</script>


{capture name="mainbox"}

<form action="{""|fn_url}" method="post" name="shippings_form" enctype="multipart/form-data" class="form-horizontal form-edit">
<input type="hidden" name="shipping_id" value="{$id}" />

{if $id}
{capture name="tabsbox"}
<div class="hidden{if !$allow_save} cm-hide-inputs{/if}" id="content_general">
{/if}

{include file="common/subheader.tpl" title=__("information") target="#acc_information"}
<fieldset id="acc_information" class="collapse-visible collapse in">
<input name="shipping_data[rate_calculation]"
    id="input_elm_rate_calculation"
    type="hidden"
    {if $shipping.rate_calculation === $manual || !$shipping.rate_calculation}
        value="{$manual}"
    {else}
        value="{$realtime}"
    {/if}
/>

<div id="elm_rate_calculation">
    {if !$allow_save}
        <input type="hidden" class="cm-no-hide-input" name="shipping_data[service_id]" value="{$shipping.service_id}" />
    {/if}
    <div class="control-group">
        <label class="control-label">{__("rate_calculation")}:</label>
        <div class="controls">
        <select name="shipping_data[carrier]" id="elm_carrier">
           <optgroup label="{__("rate_calculation_manual_by_rate_area")}">
                <option data-ca-shipping-module="{$manual}" {if $shipping.rate_calculation === $manual}selected="selected"{/if}>{__("rate_calculation_by_customer_address")}</option>
                {foreach $carriers as $carrier_key => $carrier}
                    {if ($carrier_key === "store_locator")}
                        <option data-ca-shipping-module="store_locator"
                            {if $id && $services[$shipping.service_id].module === "store_locator"}selected="selected"{/if}
                        >
                            {__("store_locator.pickup_from_store")}
                        </option>
                    {/if}
                {/foreach}
            </optgroup>
            <optgroup label="{__("rate_calculation_realtime_automatic")}">
                {foreach $carriers as $carrier_key => $carrier}
                    {if ($carrier_key  !== "store_locator")}
                        <option data-ca-shipping-module="{$carrier_key}" {if $id && $services[$shipping.service_id].module === $carrier_key}selected="selected"{/if}>{$carrier}</option>
                    {/if}
                {/foreach}
            </optgroup>
        </select>
        {if fn_check_permissions("addons", "manage", "admin")}
            <div class="well well-small help-block">
                {__("tools_addons_additional_shipping_methods_msg", ["[url]" => "addons.manage?type=not_installed"|fn_url]) nofilter}
            </div>
        {/if}
        </div>
    </div>

    <div class="control-group {if $shipping.rate_calculation === $manual || !$shipping.rate_calculation || ($id && $services[$shipping.service_id].module === "store_locator")}hidden{/if}" id="elm_service_group">
        <label class="control-label">{__("shipping_service")}:</label>
        <div class="controls">
            <select name="shipping_data[service_id]" id="elm_service">
                {foreach $services as $service}
                    {if $service.module === $services[$shipping.service_id].module}
                        <option data-ca-shipping-code="{$service.code}"
                            data-ca-shipping-module="{$service.module}"
                            value="{$service.service_id}"
                            {if $service.service_id === $shipping.service_id} selected="selected"{/if}
                        >
                            {$service.description}
                        </option>
                    {/if}
                {/foreach}
            </select>
        </div>
    </div>
</div>

<div class="control-group">
    <label class="control-label cm-required" for="ship_descr_shipping">{__("name")}:</label>
    <div class="controls">
        <input type="text" name="shipping_data[shipping]" id="ship_descr_shipping" size="30" value="{$shipping.shipping}" class="input-large" />
    </div>
</div>

{include file="common/select_status.tpl" input_name="shipping_data[status]" id="elm_shipping_status" obj=$shipping}

<div class="control-group">
    <label class="control-label">{__("icon")}:</label>
    <div class="controls">
        {include file="common/attach_images.tpl" image_name="shipping" image_object_type="shipping" image_pair=$shipping.icon no_detailed="Y" hide_titles="Y" image_object_id=$id}
    </div>
</div>

<div class="control-group">
    <label class="control-label" for="elm_delivery_time">{__("delivery_time")}:</label>
    <div class="controls">
        <input type="text" class="input-medium" name="shipping_data[delivery_time]" id="elm_delivery_time" size="30" value="{$shipping.delivery_time}" />
        <p class="muted description">{__("tt_views_shippings_update_delivery_time")}</p>
    </div>
</div>

<div class="control-group">
    <label class="control-label" for="elm_payment_instructions_{$id}">{__("description")}:</label>
    <div class="controls">
        <textarea id="elm_payment_instructions_{$id}" name="shipping_data[description]" cols="55" rows="8" class="cm-wysiwyg input-textarea-long">{$shipping.description}</textarea>
    </div>
</div>

</fieldset>

{include file="common/subheader.tpl" title=__("availability") target="#acc_availability"}
<fieldset id="acc_availability" class="collapse in">

<div class="control-group">
    <label class="control-label">{__("usergroups")}:</label>
    <div class="controls">
        {include file="common/select_usergroups.tpl"
            id="elm_ship_data_usergroup_id"
            name="shipping_data[usergroup_ids]"
            usergroups=$usergroups
            usergroup_ids=$shipping.usergroup_ids
            input_extra=""
            list_mode=false
        }
    </div>
</div>

{include file="views/localizations/components/select.tpl" data_name="shipping_data[localization]" data_from=$shipping.localization}

{hook name="shippings:update_shipping_vendor"}
{if $allow_save}
    {if "MULTIVENDOR"|fn_allowed_for}
        {$zero_company_id_name_lang_var="marketplace"}
        {$company_field_name = __("owner")}
    {/if}
    {include file="views/companies/components/company_field.tpl"
        name="shipping_data[company_id]"
        id="shipping_data_`$id`"
        selected=$shipping.company_id
        company_field_name=$company_field_name
        zero_company_id_name_lang_var=$zero_company_id_name_lang_var
    }
{/if}
{/hook}

<div class="control-group">
    <label class="control-label" for="elm_min_weight">{__("weight_limit")}&nbsp;({$settings.General.weight_symbol}):</label>
    <div class="controls">
        <input type="text" name="shipping_data[min_weight]" id="elm_min_weight" size="4" value="{$shipping.min_weight}" class="input-mini" />&nbsp;-&nbsp;<input type="text" name="shipping_data[max_weight]" size="4" value="{if $shipping.max_weight != "0.00"}{$shipping.max_weight}{/if}" class="input-mini right" />
    </div>
</div>

{if "MULTIVENDOR"|fn_allowed_for && $allow_save && $auth.user_type === "UserTypes::ADMIN"|enum}
    {hook name="shippings:available_for_new_vendors"}
        <div class="control-group">
            <label class="control-label" for="available_for_new_vendors">{__("use_for_all_new_vendors")}:</label>
            <div class="controls">
                <input type="hidden" name="shipping_data[available_for_new_vendors]" value={"YesNo::NO"|enum} />
                <input 
                    type="checkbox" 
                    name="shipping_data[available_for_new_vendors]" 
                    id="available_for_new_vendors" 
                    value={"YesNo::YES"|enum} 
                    {if $shipping.available_for_new_vendors === "YesNo::YES"|enum}checked="checked"{/if} 
                    {if $shipping.company_id}disabled {/if}
                />
            </div>
        </div>
    {/hook}
{/if}

{hook name="shippings:update"}
{/hook}

{capture name="buttons"}
    {if $id}
        {capture name="tools_list"}
            {hook name="shippings:update_tools_list"}
                <li>{btn type="list" text=__("add_shipping_method") href="shippings.add"}</li>
                <li>{btn type="list" text=__("shipping_methods") href="shippings.manage"}</li>
                {if $allow_save}
                    {if
                        $is_allow_apply_shippings_to_vendors
                        && "MULTIVENDOR"|fn_allowed_for && !$runtime.company_id
                    }
                        <li>{btn type="list" text=__("apply_shipping_for_all_vendors") href="shippings.apply_to_vendors?shipping_id={$id}" class="cm-confirm cm-post" data=['data-ca-confirm-text' => __("apply_shipping_for_all_vendors_confirm")]}</li>
                    {/if}
                    <li class="divider"></li>
                    <li>{btn type="list" text=__("delete") class="cm-confirm" href="shippings.delete?shipping_id=$id" method="POST"}</li>
                {/if}
            {/hook}
        {/capture}
        {dropdown content=$smarty.capture.tools_list}
    {/if}

    {if !$hide_for_vendor}
        {include file="buttons/save_cancel.tpl" but_name="dispatch[shippings.update]" but_target_form="shippings_form" save=$id}
    {else}
        {include file="buttons/save_cancel.tpl" but_name="dispatch[shippings.update]" hide_first_button=true hide_second_button=true but_target_form="shippings_form" save=$id}
    {/if}
{/capture}

{if $id}
    <input type="hidden" name="selected_section" value="general" />
    <!--content_general--></div>

    <div class="hidden {if !$allow_save} cm-hide-inputs{/if}" id="content_configure">
    <!--content_configure--></div>

    <div class="hidden {if !$allow_save} cm-hide-inputs{/if}" id="content_shipping_charges">
    {include file="views/shippings/components/rates.tpl" id=$id shipping=$shipping view_only=!$allow_save}
    <!--content_shipping_charges--></div>

    <div class="hidden {if !$allow_save} cm-hide-inputs{/if}" id="content_additional_settings">
        {include file="views/shippings/additional_settings.tpl"}
    <!--content_additional_settings--></div>

    <div class="hidden" id="content_rate_calculation">
        {include file="views/shippings/calculate_cost.tpl"}
    <!--content_rate_calculation--></div>

    {if fn_allowed_for("MULTIVENDOR:ULTIMATE")|| $is_sharing_enabled}
        <div class="hidden {if !$allow_save} cm-hide-inputs{/if}" id="content_storefronts">
            {$add_storefront_text = __("add_storefronts")}
            {include file="pickers/storefronts/picker.tpl"
                multiple=true
                input_name="shipping_data[storefront_ids]"
                item_ids=$shipping.storefront_ids
                data_id="storefront_ids"
                but_meta="pull-right"
                no_item_text=__("all_storefronts")
                but_text=$add_storefront_text
                view_only=($is_sharing_enabled && $runtime.company_id)
            }
        <!--content_storefronts--></div>
    {/if}

    {hook name="shippings:tabs_content"}
    {/hook}

    {/capture}
    {include file="common/tabsbox.tpl" content=$smarty.capture.tabsbox active_tab=$smarty.request.selected_section track=true}
{/if}

</form>
{/capture}{*mainbox*}

{include file="common/mainbox.tpl"
    title=($id) ? $shipping.shipping : __("new_shipping_method")
    content=$smarty.capture.mainbox
    buttons=$smarty.capture.buttons
    select_languages=true
}
