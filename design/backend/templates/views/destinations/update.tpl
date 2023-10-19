{$id = 0}
{if $destination}
    {$id = $destination.destination_id}
{/if}

{capture name="mainbox"}

    {capture name="tabsbox"}

        <form action="{""|fn_url}"
              enctype="multipart/form-data"
              method="post"
              name="destinations_form"
              class="form-horizontal form-edit {if ""|fn_check_form_permissions} cm-hide-inputs{/if}"
        >
            <div class="hidden" id="content_detailed">
                <input type="hidden" name="destination_id" value="{$id}" />
                <input type="hidden" name="selected_section" id="selected_section" value="{$smarty.request.selected_section}" />

                {hook name="destinations:update_name"}
                    <div class="control-group">
                        <label for="elm_destination_name" class="control-label cm-required">{__("name")}:</label>
                        <div class="controls">
                            <input type="text"
                                   name="destination_data[destination]"
                                   id="elm_destination_name"
                                   size="25"
                                   value="{$destination.destination}"
                                   class="input-large"
                            />
                        </div>
                    </div>
                {/hook}

                {include file="views/localizations/components/select.tpl"
                    data_name="destination_data[localization]"
                    data_from=$destination.localization
                }

                {include file="common/select_status.tpl"
                    input_name="destination_data[status]"
                    id="elm_destination_status"
                    obj=$destination
                }

                {if !$id}
                    <div class="control-group">
                        <label class="control-label">{__("add_in_all_realtime_shippings")}:</label>
                        <div class="controls">
                            <input type="checkbox" name="destination_data[add_in_all_realtime_shippings]" checked>
                        </div>
                    </div>
                {/if}

                {* Countries list *}
                {include file="common/double_selectboxes.tpl"
                    title=__("countries")
                    first_name="destination_data[countries]"
                    first_data=$destination_data.countries
                    second_name="all_countries"
                    second_data=$countries
                    class_name="destination-countries"
                }

                {* States list *}
                {include file="common/double_selectboxes.tpl"
                    title=__("states")
                    first_name="destination_data[states]"
                    first_data=$destination_data.states
                    second_name="all_states"
                    second_data=$states
                    class_name="destination-states"
                }

                {* Zipcodes list *}
                {include file="common/subheader.tpl" title=__("zipcodes")}
                <div class="table-wrapper">
                    <table width="100%">
                    <tr>
                        <td width="48%">
                            <textarea name="destination_data[zipcodes]"
                                      id="elm_destination_zipcodes"
                                      rows="8"
                                      class="input-full"
                            >{$destination_data.zipcodes}</textarea>
                        </td>
                        <td>&nbsp;</td>
                        <td width="48%">{__("text_zipcodes_wildcards") nofilter}</td>
                    </tr>
                    </table>
                </div>

                {* Cities list *}
                {include file="common/subheader.tpl" title=__("cities")}
                <div class="table-wrapper">
                    <table cellpadding="0" cellspacing="0" width="100%" border="0">
                    <tr>
                        <td width="48%">
                            <textarea name="destination_data[cities]"
                                      id="elm_destination_cities"
                                      rows="8"
                                      class="input-full"
                            >{$destination_data.cities}</textarea>
                        </td>
                        <td>&nbsp;</td>
                        <td width="48%">{__("text_cities_wildcards") nofilter}</td>
                    </tr>
                    </table>
                </div>

                {* Addresses list *}
                {include file="common/subheader.tpl" title=__("addresses")}
                <div class="table-wrapper">
                    <table cellpadding="0" cellspacing="0" width="100%" border="0">
                    <tr>
                        <td width="48%">
                            <textarea name="destination_data[addresses]"
                                      id="elm_destination_cities"
                                      rows="8"
                                      class="input-full"
                            >{$destination_data.addresses}</textarea>
                        </td>
                        <td>&nbsp;</td>
                        <td width="48%">{__("text_addresses_wildcards") nofilter}</td>
                    </tr>
                    </table>
                </div>
            </div>

            {hook name="destinations:tabs_content"}{/hook}

            {capture name="buttons"}
                {include file="buttons/save_cancel.tpl"
                    but_name="dispatch[destinations.update]"
                    but_target_form="destinations_form"
                    save=$id
                }
            {/capture}

        </form>

        {hook name="destinations:tabs_extra"}{/hook}

    {/capture}

    {include file="common/tabsbox.tpl"
        content=$smarty.capture.tabsbox
        group_name=$runtime.controller
        active_tab=$smarty.request.selected_section
        track=true
    }
{/capture}

{include file="common/mainbox.tpl"
    title=($id) ? $destination.destination : __("new_rate_area")
    content=$smarty.capture.mainbox
    buttons=$smarty.capture.buttons
    select_languages=true
}
