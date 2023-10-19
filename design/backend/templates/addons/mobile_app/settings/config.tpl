<div id="colors_variables">
    <style>
        {$mobile_app_styles nofilter}
    </style>
<!--colors_variables--></div>

{capture name="general"}
<div class="clearfix">
    <div class="span6">
        {include file="common/subheader.tpl" title=__("app_params")}

        <div class="control-group">
            <label class="control-label" for="m_settings_app_settings_utility_shopName">{__("mobile_app.shopName")}:</label>
            <div class="controls">
                <input type="text" name="m_settings[app_settings][utility][shopName]"
                    value="{$config_data.app_settings.utility.shopName}"
                    id="m_settings_app_settings_utility_shopName"
                />
                <p class="muted description">{__("ttc_mobile_app.shopname")}</p>
            </div>
        </div>

        <br /><br />

        <div class="control-group">
            <label class="control-label" for="m_settings_app_settings_utility_pushNotifications">{__("mobile_app.pushNotifications")}:</label>
            <div class="controls">
                <select
                    name="m_settings[app_settings][utility][pushNotifications]"
                    id="m_settings_app_settings_utility_pushNotifications"
                >
                    <option value="0" {if $config_data.app_settings.utility.pushNotifications == 0}selected{/if}>{__("no")}</option>
                    <option value="1" {if $config_data.app_settings.utility.pushNotifications == 1}selected{/if}>{__("yes")}</option>
                </select>
                <p class="muted description">{__("ttc_mobile_app.pushnotifications")}</p>
            </div>
        </div>

        <div class="control-group">
            <label class="control-label" for="m_settings_app_settings_utility_fcm_api_key">{__("mobile_app.fcm_api_key")}:</label>
            <div class="controls">
                <input type="text" name="m_settings[app_settings][utility][fcmApiKey]"
                    value="{$config_data.app_settings.utility.fcmApiKey}"
                    id="m_settings_app_settings_utility_fcm_api_key"
                />
                <p class="muted description">{__("mobile_app.fcm_api_key_tooltip", ["[bundle_id]" => $config_data.bundle_id|default:""]) nofilter}</p>
            </div>
        </div>

        <div class="control-group">
            <label class="control-label" for="m_settings_app_settings_utility_services_config">{__("mobile_app.services_config")}:</label>
            <div class="controls">
                {if $config_data.google_config_file_uploaded}
                    <a href="{"mobile_app.get_google_config_file"|fn_url}">google-services.json</a>
                    <a class="cm-post" href="{"mobile_app.delete_google_config_file&storefront_id=`$selected_storefront_id`"|fn_url}">
                        {include_ext file="common/icon.tpl"
                            class="icon-remove-sign cm-tooltip hand"
                            title=__("remove_this_item")
                        }
                    </a>
                {/if}
                {include file="common/fileuploader.tpl" var_name="mobile_app[google_services_config_file]" hide_server=true}
                <p class="muted description">{__("tt_mobile_app.services_config")}</p>
            </div>
        </div>

        <br /><br />

        <div class="control-group">
            <label for="config_data_app_settings_build_appName"
                   class="control-label cm-required"
            >{__("mobile_app.appName")}:</label>
            <div class="controls">
                <input type="text" name="m_settings[app_settings][build][appName]"
                    id="config_data_app_settings_build_appName"
                    value="{$config_data.app_settings.build.appName}"
                    maxlength="30"
                />
                <p class="muted description">{__("ttc_mobile_app.appname")}</p>
            </div>
        </div>

        <div class="control-group">
            <label class="control-label cm-required" for="config_data_app_settings_build_appShortDescription">{__("mobile_app.appShortDescription")}:</label>
            <div class="controls">
                <textarea
                    name="m_settings[app_settings][build][appShortDescription]"
                    cols="30"
                    rows="3"
                    maxlength="80"
                    data-target="appShortDescription"
                    id="config_data_app_settings_build_appShortDescription"
                >{$config_data.app_settings.build.appShortDescription}</textarea>
                <p class="muted description">{__("ttc_mobile_app.appshortdescription")}</p>
            </div>
        </div>

        <div class="control-group">
            <label class="control-label cm-required" for="config_data_app_settings_build_appFullDescription">{__("mobile_app.appFullDescription")}:</label>
            <div class="controls">
                <textarea
                    name="m_settings[app_settings][build][appFullDescription]"
                    cols="30"
                    rows="10"
                    maxlength="4000"
                    data-target="appFullDescription"
                    id="config_data_app_settings_build_appFullDescription"
                >{$config_data.app_settings.build.appFullDescription}</textarea>
                <p class="muted description">{__("ttc_mobile_app.appfulldescription")}</p>
            </div>
        </div>

        <div class="control-group">
            <label class="control-label cm-required cm-email" for="config_data_app_settings_build_supportEmail">{__("mobile_app.supportEmail")}:</label>
            <div class="controls">
                <input type="email" name="m_settings[app_settings][build][supportEmail]"
                    value="{$config_data.app_settings.build.supportEmail}"
                    id="config_data_app_settings_build_supportEmail"
                    data-target="supportEmail"
                />
                <p class="muted description">{__("ttc_mobile_app.supportemail")}</p>
            </div>
        </div>

        <div class="control-group">
            <label class="control-label cm-required" for="config_data_app_settings_build_privacyPolicyUrl">{__("mobile_app.privacyPolicyUrl")}:</label>
            <div class="controls">
                <input type="text" name="m_settings[app_settings][build][privacyPolicyUrl]"
                    value="{$config_data.app_settings.build.privacyPolicyUrl}"
                    id="config_data_app_settings_build_privacyPolicyUrl"
                    data-target="privacyPolicyUrl"
                />
                <p class="muted description">{__("ttc_mobile_app.privacypolicyurl")}</p>
            </div>
        </div>

        <div class="control-group">
            <label class="control-label cm-required" for="config_data_app_settings_build_termsOfServiceUrl">{__("mobile_app.terms_of_service_url")}:</label>
            <div class="controls">
                <input type="text" name="m_settings[app_settings][build][termsOfServiceUrl]"
                    value="{$config_data.app_settings.build.termsOfServiceUrl}"
                    id="config_data_app_settings_build_termsOfServiceUrl"
                    data-target="termsOfServiceUrl"
                />
                <p class="muted description">{__("ttc_mobile_app.terms_of_service_url")}</p>
            </div>
        </div>
    </div>

    <div class="span9 mobile-app__images-container">
        {include file="common/subheader.tpl" title=__("images_params")}
        <div class="control-group">
            <label class="control-label" for="config_data_app_settings_build_crop_when_resize">{__("mobile_app.crop_when_resize")}:</label>
            <div class="controls">
                <input type="hidden" name="m_settings[app_settings][images][crop_when_resize]" value="N"/>
                <input type="checkbox" name="m_settings[app_settings][images][crop_when_resize]"
                       value="Y"
                       id="config_data_app_settings_build_crop_when_resize"
                       data-target="crop_when_resize"
                       {if $config_data.app_settings.images.crop_when_resize == "Y"}
                           checked
                       {/if}
                />
                <p class="muted description">{__("tt_mobile_app.crop_when_resize")}</p>
            </div>
        </div>

        {foreach $image_types as $image_type_data}
            <div class="control-group">
                <label class="control-label">{__("mobile_app.`$image_type_data.name`")}</label>
                <div class="controls">
                    {include file="common/attach_images.tpl" image_name=$image_type_data.name image_object_type=$image_type_data.type image_pair=$app_images[$image_type_data.type] hide_alt=true hide_thumbnails=true no_thumbnail=true}
                    {if !$image_type_data.no_tooltip}
                        <p class="muted description">{__("tt_mobile_app.`$image_type_data.name`")}</p>
                    {/if}
                </div>
            </div>
        {/foreach}
    </div>

</div>
{/capture}

{capture name="colors"}
<div id="color_presets">
    <div class="clearfix">
        <div class="control-group">
            <label class="control-label" for="m_settings_app_settings_color_preset">{__("mobile_app.color_preset")}:</label>
            <div class="controls">
                <select
                        name="mobile_app_color_preset"
                        id="m_settings_app_settings_color_preset"
                        class="js-mobile-app-color-preset-input"
                >
                    {foreach $color_presets as $color_preset => $color_preset_data}
                        <option value="{$color_preset}" {if $selected_preset === $color_preset}selected{/if}>{__($color_preset_data.name)}</option>
                    {/foreach}
                </select>
            </div>
        </div>
    </div>

    <div class="control-group mockup__presets-main-screens">
        {include file="addons/mobile_app/components/main.tpl" pickers_available=false}
        {include file="addons/mobile_app/components/categories.tpl" pickers_available=false}
        {include file="addons/mobile_app/components/product_screen.tpl" pickers_available=false}
    </div>

    <div id="mobile_app_custom_settings">
        {$isCollapsed = $selected_preset !== "C"}

        {include
            file="common/subheader.tpl"
            title=__("advanced")
            target="#mobile_app_screens_app"
            meta=($isCollapsed) ? "collapsed" : ""
        }

            <div id="mobile_app_screens_app" class="collapse collapse-visible{if !$isCollapsed} in{/if}">
            <div class="control-group">
                {include file="addons/mobile_app/components/categories.tpl"}

                {include file="addons/mobile_app/components/bottom_tabs.tpl"}

                {include file="addons/mobile_app/components/navbar.tpl"}

                {include file="addons/mobile_app/components/product_screen.tpl"}

                {include file="addons/mobile_app/components/main.tpl"}

                {include file="addons/mobile_app/components/profile.tpl"}
            </div>
        </div>
    </div>
<!--color_presets--></div>
{/capture}

{capture name="apple_pay"}
    <div class="clearfix">
        <div class="span6">
            <div class="control-group">
                <label class="control-label" for="m_settings_app_settings_apple_pay">{__("mobile_app.apple_pay")}:</label>
                <input type="hidden" name="m_settings[app_settings][apple_pay][applePay]" value="off"/>
                <div class="controls">
                    {include file="common/switcher.tpl"
                        id="m_settings_app_settings_apple_pay"
                        checked=$config_data.app_settings.apple_pay.applePay === "on"
                        input_name="m_settings[app_settings][apple_pay][applePay]"
                    }
                </div>
            </div>

            <div class="control-group">
                <label class="control-label" for="m_settings_app_settings_apple_pay_merchant_identifier">{__("mobile_app.apple_pay_merchant_identifier")}:</label>
                <div class="controls">
                    <input type="text" name="m_settings[app_settings][apple_pay][applePayMerchantIdentifier]" value="{$config_data.app_settings.apple_pay.applePayMerchantIdentifier}"/>
                    <p class="muted description">{__("mobile_app.apple_pay_merchant_identifier_tooltip") nofilter}</p>
                </div>
            </div>

            <div class="control-group">
                <label class="control-label" for="m_settings_app_settings_apple_pay_merchant_name">{__("mobile_app.apple_pay_merchant_name")}:</label>
                <div class="controls">
                    <input type="text" name="m_settings[app_settings][apple_pay][applePayMerchantName]" value="{$config_data.app_settings.apple_pay.applePayMerchantName}"/>
                    <p class="muted description">{__("mobile_app.apple_pay_merchant_name_tooltip")}</p>
                </div>
            </div>

            <div class="control-group">
                <label class="control-label" for="m_settings_app_settings_apple_pay_supported_networks">{__("mobile_app.apple_pay_supported_networks")}:</label>
                <div class="controls">
                    <select class="input-full" name="m_settings[app_settings][apple_pay][applePaySupportedNetworks][]" id="m_settings_app_settings_apple_pay_supported_networks" multiple="multiple" size="15">
                        {foreach $apple_pay_supported_networks as $code => $name}
                            <option value="{$code}" {if in_array($code, $config_data.app_settings.apple_pay.applePaySupportedNetworks|default:[])}selected{/if}>{$name}</option>
                        {/foreach}
                    </select>
                </div>
            </div>
        </div>
    </div>
{/capture}

{capture name="google_pay"}
    <div class="clearfix">
        <div class="span6">
            <div class="control-group">
                <label class="control-label" for="m_settings_app_settings_google_pay">{__("mobile_app.google_pay")}:</label>
                <input type="hidden" name="m_settings[app_settings][google_pay][googlePay]" value="off"/>
                <div class="controls">
                    {include file="common/switcher.tpl"
                        id="m_settings_app_settings_google_pay"
                        checked=$config_data.app_settings.google_pay.googlePay === "on"
                        input_name="m_settings[app_settings][google_pay][googlePay]"
                    }
                </div>
            </div>

            <div class="control-group">
                <label class="control-label" for="m_settings_app_settings_google_pay_api_key">{__("mobile_app.google_pay_api_key")}:</label>
                <div class="controls">
                    <input type="text" name="m_settings[app_settings][google_pay][googlePayApiKey]" value="{$config_data.app_settings.google_pay.googlePayApiKey}"/>
                </div>
            </div>

            <div class="control-group">
                <label class="control-label" for="m_settings_app_settings_google_pay_supported_networks">{__("mobile_app.google_pay_supported_networks")}:</label>
                <div class="controls">
                    <select class="input-full" name="m_settings[app_settings][google_pay][googlePaySupportedNetworks][]" id="m_settings_app_settings_google_pay_supported_networks" multiple="multiple" size="15">
                        {foreach $google_pay_supported_networks as $code => $name}
                            <option value="{$code}" {if in_array($code, $config_data.app_settings.google_pay.googlePaySupportedNetworks|default:[])}selected{/if}>{$name}</option>
                        {/foreach}
                    </select>
                </div>
            </div>
        </div>
    </div>
{/capture}

{capture name="translations"}
    <div class="clearfix">
        {include file="common/subheader.tpl" title=__("translations")}
        <div class="control-group">
            <a href="{fn_url("languages.translations?name=mobile_app.mobile_")}"
               target="_blank"
            >{__("mobile_app.edit_app_translations")}</a>
        </div>

        {include file="common/subheader.tpl" title=__("mobile_app.upload_existing_translations")}
        {foreach $languages as $language}
            <div class="control-group">
                <label class="control-label">
                    {$language.name}
                </label>
                <div class="controls">
                    {include file="common/fileuploader.tpl"
                        var_name="mobile_app_translations[{$language.lang_code}]"
                        allowed_ext="json"
                        hide_server=true
                        target_form="update_addon_mobile_app_form"
                    }
                </div>
            </div>
        {/foreach}
    </div>
{/capture}

{capture name="promotion"}
    <div class="clearfix">
        <div class="control-group">
            <div class="well well-small help-block">
                {__("mobile_app.promotion_note", ["[icon_url]" => fn_url("addons.update?addon=mobile_app")]) nofilter}
            </div>
        </div>
        <div class="control-group">
            <label for="elm_app_store_app_id" class="control-label">
                {__("mobile_app.app_store_app_id")}
            </label>
            <div class="controls">
                <input type="text"
                       name="m_settings[promotion][app_store_app_id]"
                       form="update_addon_mobile_app_form"
                       value="{$config_data.promotion.app_store_app_id}"
                       id="elm_app_store_app_id"
                />
                <p class="muted description">{__("mobile_app.app_store_app_id_tooltip") nofilter}</p>
            </div>
        </div>
        <div class="control-group">
            <label for="elm_google_play_app_id" class="control-label">
                {__("mobile_app.google_play_app_id")}
            </label>
            <div class="controls">
                <input type="text"
                       name="m_settings[promotion][google_play_app_id]"
                       form="update_addon_mobile_app_form"
                       value="{$config_data.promotion.google_play_app_id}"
                       id="elm_google_play_app_id"
                />
                <p class="muted description">{__("mobile_app.google_play_app_id_tooltip") nofilter}</p>
            </div>
        </div>
    </div>
{/capture}

<div id="mobile_app_configurator">
    <input type="hidden" name="setting_id" value="{$setting_id}"/>

    {__("mobile_app.section.configure_app_description")}

    <hr>

    <div class="cm-j-tabs cm-track tabs" data-ca-tabs-input-name>
        <ul class="nav nav-tabs">
            <li id="mobile_app_tab_general" class="cm-js {if $active_tab === "mobile_app_tab_general"}active{/if}">
                <a>{__("general")}</a>
            </li>
            <li id="mobile_app_tab_colors" class="cm-js {if $active_tab === "mobile_app_tab_colors"}active{/if}">
                <a>{__("mobile_app.colors")}</a>
            </li>
            <li id="mobile_app_tab_apple_pay" class="cm-js hidden {if $active_tab === "mobile_app_tab_apple_pay"}active{/if}">
                <a>{__("mobile_app.apple_pay")}</a>
            </li>
            <li id="mobile_app_tab_google_pay" class="cm-js hidden {if $active_tab === "mobile_app_tab_google_pay"}active{/if}">
                <a>{__("mobile_app.google_pay")}</a>
            </li>

        </ul>
    </div>
    <div class="cm-tabs-content">
        <div id="content_mobile_app_tab_general" class="hidden">
            {$smarty.capture.general nofilter}
        </div>
        <div id="content_mobile_app_tab_colors" class="hidden">
            {$smarty.capture.colors nofilter}
        </div>
        <div id="content_mobile_app_tab_apple_pay" class="hidden">
            {$smarty.capture.apple_pay nofilter}
        </div>
        <div id="content_mobile_app_tab_google_pay" class="hidden">
            {$smarty.capture.google_pay nofilter}
        </div>

    </div>
</div>
