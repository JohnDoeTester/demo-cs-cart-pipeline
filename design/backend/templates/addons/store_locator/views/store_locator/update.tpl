{$id = $store_location.store_location_id|default:"0"}
{$allow_save = $store_location|fn_allow_save_object:"store_locations" && fn_check_view_permissions("store_locator.update", "POST")}
{$show_save_btn = $allow_save scope = root}

{include file="addons/store_locator/pickers/map.tpl"}

{capture name="mainbox"}

{capture name="tabsbox"}

    <form action="{""|fn_url}" method="post" enctype="multipart/form-data" class="form-horizontal form-edit{if !$allow_save} cm-hide-inputs{/if}" name="store_locations_form{$suffix}">
        <input type="hidden" name="store_location_id" value="{$id}" />
        <input type="hidden" class="cm-no-hide-input" name="selected_section" value="{$smarty.request.selected_section|default:"detailed"}" />

        <div id="content_detailed">
            <fieldset>
                {hook name="store_locator:content_detailed"}

                <div class="control-group">
                    <label for="elm_name" class="cm-required control-label">{__("name")}:</label>
                    <div class="controls">
                        <input type="text" id="elm_name" name="store_location_data[name]" value="{$store_location.name}">
                    </div>
                </div>

                {include file="views/companies/components/company_field.tpl"
                    name="store_location_data[company_id]"
                    id="company_id_{$id}"
                    selected=$store_location.company_id
                    company_field_name=__("owner")
                    zero_company_id_name_lang_var=$zero_company_id_name_lang_var
                }

                <div class="control-group">
                    <label class="control-label" for="elm_position">{__("position")}:</label>
                    <div class="controls">
                        <input type="text" name="store_location_data[position]" id="elm_position" value="{$store_location.position}" size="3">
                    </div>
                </div>

                <div class="control-group">
                    <label for="elm_pickup_address" class="control-label">{__("address")}</label>
                    <div class="controls">
                        <input class="input-large" type="text" name="store_location_data[pickup_address]" id="elm_pickup_address" size="55" value="{$store_location.pickup_address}" />
                    </div>
                </div>

                <div class="control-group">
                    <label for="elm_pickup_phone" class="control-label">{__("phone")}</label>
                    <div class="controls">
                        <input class="input-large" type="text" name="store_location_data[pickup_phone]" id="elm_pickup_phone" size="55" value="{$store_location.pickup_phone}" />
                    </div>
                </div>

                <div class="control-group">
                    <label for="elm_pickup_work_time" class="control-label">{__("store_locator.work_time")}</label>
                    <div class="controls">
                        <input class="input-large" type="text" name="store_location_data[pickup_time]" id="elm_pickup_work_time" size="55" value="{$store_location.pickup_time}" />
                    </div>
                </div>

                <div class="control-group">
                    <label class="control-label" for="elm_description">{__("description")}:</label>
                    <div class="controls">
                        <textarea id="elm_description" name="store_location_data[description]" cols="55" rows="2" class="cm-wysiwyg input-textarea-long">{$store_location.description}</textarea>
                    </div>
                </div>

                <div class="control-group">
                    <label class="control-label" for="elm_country">{__("country")}:</label>
                    <div class="controls">
                        {assign var="countries" value=1|fn_get_simple_countries:$smarty.const.CART_LANGUAGE}
                        <select id="elm_country_{$id}" name="store_location_data[country]" class="select cm-country cm-location-{$id}">
                            <option value="">- {__("select_country")} -</option>
                            {foreach from=$countries item="country" key="code"}
                                <option {if $store_location.country == $code}selected="selected"{/if} value="{$code}" title="{$country}">{$country}</option>
                            {/foreach}
                        </select>
                    </div>
                </div>

                <div class="control-group">
                    <label class="control-label" for="elm_country">{__("state")}:</label>
                    <div class="controls">
                        <select id="elm_state_{$id}" class="cm-state cm-location-{$id}" name="store_location_data[state]">
                            <option value="">- {__("select_state")} -</option>
                            {foreach $states[$store_location.country] as $state_id => $state}
                                <option {if $state_id == $store_location.state}selected{/if} value="{$state_id}">{$state.state}</option>
                            {/foreach}
                        </select>
                        <input type="text"
                               id="elm_state_{$id}_d"
                               name="store_location_data[state]"
                               value="{$store_location.state}"
                               disabled="disabled"
                               class="cm-state cm-location-{$id} hidden"
                        />
                    </div>
                </div>

                <script>
                    (function(_, $) {
                        $.ceRebuildStates('init', {
                            default_country: '{$store_location.country|escape:javascript}',
                            states: {$states|json_encode nofilter}
                        });
                    }(Tygh, Tygh.$));
                </script>


                <div class="control-group">
                    <label class="control-label" for="elm_city">{__("city")}:</label>
                    <div class="controls">
                        <input type="text" name="store_location_data[city]" id="elm_city" value="{$store_location.city}">
                    </div>
                </div>

                <div class="control-group">
                    <label class="control-label cm-required">{__("coordinates")} ({__("latitude_short")} &times; {__("longitude_short")}):</label>
                    <label class="control-label cm-required hidden" for="elm_latitude">{__("latitude")}</label>
                    <label class="control-label cm-required hidden" for="elm_longitude">{__("longitude")}</label>
                    <div class="controls">
                        {hook name="store_locator:select_coordinates"}
                        <input type="text" name="store_location_data[latitude]" id="elm_latitude" value="{$store_location.latitude}" data-ca-latest-latitude="{$store_location.latitude}" class="input-small">
                        &times;
                        <input type="text" name="store_location_data[longitude]" id="elm_longitude" value="{$store_location.longitude}" data-ca-latest-longitude="{$store_location.longitude}" class="input-small">
                        {/hook}
                    </div>
                </div>

                {include file="views/localizations/components/select.tpl" data_from=$store_location.localization data_name="store_location_data[localization]"}

                {hook name="store_locator:detailed_content"}
                {/hook}

                {include file="common/select_status.tpl" input_name="store_location_data[status]" id="elm_status" obj_id=$store_location.location_id obj=$store_location}
                {/hook}
            </fieldset>
        </div>

        <div id="content_addons">
            {hook name="store_locator:addons_content"}
            {/hook}
        </div>

        <div id="content_pickup">

            {if !empty($store_location.pickup_surcharge)}
                {** TODO: delete it some day, when all clients have migrated to new rates calculation **}
                <div class="control-group cm-hide-inputs">
                    <label class="control-label" for="elm_pickup_surcharge">{__("surcharge")}:</label>
                    <div class="controls">
                        <input id="elm_pickup_surcharge" type="text" name="store_location_data[pickup_surcharge]" class="input-mini" value="{$store_location.pickup_surcharge}" size="4"> {$currencies.$primary_currency.symbol nofilter}
                        <p>{__("store_locator.surcharge_changes_hint")}</p>
                    </div>
                </div>
            {/if}

            {hook name="store_locator:content_pickup"}
            {if $destinations}
                <div class="control-group">
                    <label class="control-label">{__("store_locator.main_destination")}:</label>
                    <div class="controls">
                        <select name="store_location_data[main_destination_id]" id="main_destination">
                            <option value="">{__("none")}</option>
                            {foreach $destinations as $destination}
                                <option value="{$destination.destination_id}" {if $store_location.main_destination_id === $destination.destination_id}selected{/if}>{$destination.destination}</option>
                            {/foreach}
                        </select>
                        <p class="muted description">{__("tt_addons_store_locator_views_store_locator_update_store_locator.main_destination")}</p>
                    </div>
                </div>

                <div class="control-group store-locator__pickup-destinations-list{if !$store_location.main_destination_id} hidden{/if}">
                    <label class="control-label">{__("store_locator.show_to")}:</label>
                    <div class="controls">
                        {include file="common/adaptive_object_selection.tpl"
                            input_name="store_location_data[pickup_destinations_ids]"
                            input_id="destinations"
                            item_ids=$store_location.pickup_destinations_ids
                            items=$destinations
                            id_field="destination_id"
                            name_field="destination"
                            type="destinations"
                            class_prefix="store-locator__destination"
                            close_on_select="false"
                            list_mode=false
                        }
                        <p class="muted description">{__("tt_addons_store_locator_views_store_locator_update_store_locator.show_to")}</p>
                    </div>
                </div>
            {/if}
            {/hook}

        </div>
        {hook name="store_locator:tabs_content"}
        {/hook}

        {capture name="buttons"}
            {if !$id}
                {include file="buttons/save_cancel.tpl" but_name="dispatch[store_locator.update]" but_role="submit-link" but_target_form="store_locations_form{$suffix}"}
            {else}
                {if !$show_save_btn}
                    {assign var="hide_first_button" value=true}
                    {assign var="hide_second_button" value=true}
                {/if}
                {include file="buttons/save_cancel.tpl" but_name="dispatch[store_locator.update]" hide_first_button=$hide_first_button hide_second_button=$hide_second_button but_role="submit-link" but_target_form="store_locations_form{$suffix}" save=$id}
            {/if}
        {/capture}

    </form>

    {if $id}
        {hook name="store_locator:tabs_extra"}
        {/hook}
    {/if}

{/capture}

{include file="common/tabsbox.tpl" content=$smarty.capture.tabsbox track=true}
{/capture}

{include file="common/mainbox.tpl"
    title=($id) ? $store_location.name : __("new_store_location")
    content=$smarty.capture.mainbox
    select_languages=true
    buttons=$smarty.capture.buttons
}
{script src="js/addons/store_locator/destinations.js"}
