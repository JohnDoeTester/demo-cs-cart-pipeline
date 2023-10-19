{$is_enabled = $app["storefront.switcher.is_enabled"]}
{$is_available_for_disaptch = $app['storefront.switcher.is_available_for_dispatch']}

{if $is_enabled && $is_available_for_disaptch}
    {if (fn_allowed_for("MULTIVENDOR:ULTIMATE"))}
        {$selected_storefront_id = $selected_storefront_id|default:$app["storefront.switcher.selected_storefront_id"]}
        {$storefront_switcher_param_name = $storefront_switcher_param_name|default:"s_storefront"}
        {$storefront_switcher_data_name = "storefront_id"}
    {else}
        {$selected_storefront_id = $app["storefront.switcher.selected_storefront_id"]}
        {$storefront_switcher_param_name = "switch_company_id"}
        {$storefront_switcher_data_name = "company_id"}
    {/if}

    {$show_all_storefront = $show_all_storefront|default:true}

    {$preset_data = $app["storefront.switcher.preset_data.factory"]|call_user_func:$selected_storefront_id}

    {capture name="storefronts_list"}
        {if $show_all_storefront}
            <a href="{$config.current_url|fn_link_attach:"`$storefront_switcher_param_name`=0`$storefront_picker_link_suffix`"|fn_url}"
                class="storefront__picker-logo-link"
                title="{__("show_all_storefronts")}">

                <div class="storefront__picker-logo-wrapper
                    {if !$selected_storefront_id}
                        storefront__picker-logo-wrapper--active
                    {/if}">
                    <div class="storefront__picker-logo-text
                        {if __("all_storefronts_short")|count_characters > 3}storefront__picker-logo-text--small{/if}
                        {if !$selected_storefront_id}storefront__picker-logo-text--active{/if}">
                        {__("all_storefronts_short")}
                    </div>
                </div>
            </a>
        {/if}
        {foreach $preset_data.storefronts as $storefront}
            {$_storefront_picker_logo_img_class = $storefront_picker_logo_img_class}
            {if $storefront.is_selected}
                {$storefront_picker_logo_img_class = "storefront__picker-logo-img--active `$_storefront_picker_logo_img_class`"}
            {/if}

            <a href="{$config.current_url|fn_link_attach:"`$storefront_switcher_param_name`=`$storefront[$storefront_switcher_data_name]``$storefront_picker_link_suffix`"|fn_url}"
                class="storefront__picker-logo-link {if $storefront.is_selected}storefront__picker-logo-link--active{/if}"
                title="{__("select_storefront", ["[store]" => $storefront.name])}">

                <div class="storefront__picker-logo-wrapper
                    {if $storefront.is_selected}storefront__picker-logo-wrapper--active{/if}">
                    {include file="common/image.tpl"
                        image=$storefront.images
                        image_height="64"
                        image_css_class="storefront__picker-logo-img storefront__picker-logo-img--inactive `$storefront_picker_logo_img_class`"
                        show_detailed_link=false
                    }
                </div>
            </a>
            {if $storefront.is_selected}
                {$storefront_picker_logo_img_class = $_storefront_picker_logo_img_class}
            {/if}
        {/foreach}
    {/capture}

    {if $runtime.is_multiple_storefronts}
        <div class="storefront__picker-logo-list js-storefront-switcher"
            data-ca-switcher-param-name="{$storefront_switcher_param_name}"
            data-ca-switcher-data-name="{$storefront_switcher_data_name}">

            {$smarty.capture.storefronts_list nofilter}
            <div class="dropdown storefront__picker-dropdown {if $runtime.storefronts_count > $preset_data.threshold}storefront__picker-dropdown--threshold{/if}">
                <a class="dropdown-toggle storefront__picker-logo-link storefront__picker-logo-link--dropdown-toggle"
                    data-toggle="dropdown"
                    data-ca-dropdown-object-picker-autoopen=".object-picker__select--storefronts"
                    title="{__("show_all_storefronts_with_count", ["[count]" => $runtime.storefronts_count])}">
                    {if $selected_storefront_id}
                        {foreach $preset_data.storefronts as $storefront}
                            {if $storefront.is_selected}
                                <div class="storefront__picker-logo-wrapper storefront__picker-logo-wrapper--mobile">
                                    {include file="common/image.tpl"
                                        image=$storefront.images
                                        image_height="64"
                                        image_css_class="storefront__picker-logo-img `$storefront_picker_logo_img_class`"
                                        show_detailed_link=false
                                    }
                                </div>
                            {/if}
                        {/foreach}
                    {else}
                        <div class="storefront__picker-logo-wrapper storefront__picker-logo-wrapper--mobile">
                            <div class="storefront__picker-logo-text
                                {if __("all_storefronts_short")|count_characters > 3}storefront__picker-logo-text--small{/if}">
                                {__("all_storefronts_short")}
                            </div>
                        </div>
                    {/if}
                    <div class="storefront__picker-logo-wrapper storefront__picker-logo-wrapper--desktop">
                        <div class="storefront__picker-logo-text">
                            +{($runtime.storefronts_count - $preset_data.threshold)}
                        </div>
                    </div>
                </a>
                <ul class="dropdown-menu storefront__picker-dropdown-menu" id="storefront_picker_dropdown_menu">
                    {include file="views/storefronts/components/picker/picker.tpl"
                        input_name=""
                        item_ids=[$selected_storefront_id]
                        show_empty_variant=$show_all_storefront
                        dropdown_parent_selector="#storefront_picker_dropdown_menu"
                        empty_variant_text=__("all_storefronts")
                        show_advanced=false
                        dropdown_css_class="storefront__picker-dropdown-picker"
                    }
                </ul>
            </div>
        </div>
    {/if}
{/if}