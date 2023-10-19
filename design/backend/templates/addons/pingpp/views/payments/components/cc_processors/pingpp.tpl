{$id = $payment.id|default:0}
<div class="pingpp-configuration-form">
{if !$currencies.CNY}
    <div class="alert alert-block">
        <p class="">{__("pingpp.enable_cny_currency")}</p>
    </div>
{/if}

{include file="common/subheader.tpl" title=__("pingpp.general_settings")}

<div class="control-group">
    <label for="elm_app_id{$id}" class="control-label cm-required">{__("pingpp.app_id")}:</label>
    <div class="controls">
        <input id="elm_app_id{$id}"
               type="text"
               name="payment_data[processor_params][app_id]"
               value="{$processor_params.app_id}"/>
    </div>
</div>

<div class="control-group">
    <label for="elm_api_key{$id}" class="control-label cm-required">{__("pingpp.api_key")}:</label>
    <div class="controls">
        <input id="elm_api_key{$id}"
               type="text"
               name="payment_data[processor_params][api_key]"
               value="{$processor_params.api_key}"/>
    </div>
</div>

<div class="control-group">
    <label for="elm_order_prefix{$id}"
           class="control-label cm-regexp cm-required"
           data-ca-regexp="{literal}^[a-zA-Z0-9]{7,10}${/literal}"
           data-ca-message="{__("pingpp.order_prefix_characters")}"
    >{__("pingpp.order_prefix")}:</label>
    <div class="controls">
        <input id="elm_order_prefix{$id}"
               type="text"
               name="payment_data[processor_params][order_prefix]"
               value="{$processor_params.order_prefix}"
               maxlength="10"
        />
    </div>
</div>

{include file="common/subheader.tpl" title=__("pingpp.wx_settings")}

<div class="control-group">
    <label for="elm_app_id{$id}"
           class="control-label pingpp-wx-required {if $wx_enabled}cm-required{/if}"
    >{__("pingpp.wx_app_id")}:</label>
    <div class="controls">
        <input id="elm_app_id{$id}"
               type="text"
               name="payment_data[processor_params][wx_app_id]"
               value="{$processor_params.wx_app_id}"/>
    </div>
</div>

<div class="control-group">
    <label for="elm_api_key{$id}"
           class="control-label pingpp-wx-required {if $wx_enabled}cm-required{/if}"
    >{__("pingpp.wx_app_secret")}:</label>
    <div class="controls">
        <input id="elm_api_key{$id}"
               type="text"
               name="payment_data[processor_params][wx_app_secret]"
               value="{$processor_params.wx_app_secret}"/>
    </div>
</div>

{include file="common/subheader.tpl" title=__("pingpp.channels")}

{foreach $pingpp_channels as $channel => $definition}
    {$is_enabled = $processor_params.channels.$channel.is_enabled == "Y"}
    <div class="control-group pingpp-channel-wrapper">
        <label class="checkbox strong">
            <input type="hidden"
                   name="payment_data[processor_params][channels][{$channel}][is_enabled]"
                   value="N"
            />
            <input type="checkbox"
                   name="payment_data[processor_params][channels][{$channel}][is_enabled]"
                   {if $is_enabled}checked="checked"{/if}
                   value="Y"
                   id="sw_settings_{$channel}{$id}"
                   class="cm-combo-checkbox cm-combination"
                   data-ca-pingpp-channel="{$channel}"
            />
            {__("pingpp.channel.`$channel`")}
            <span class="pingpp-scopes">
                {foreach $definition.scope as $scope}
                    {include_ext file="common/icon.tpl"
                        class="pingpp-scope-icon-`$scope`"
                        title=__("pingpp.scope.`$scope`")
                    }
                    <input type="hidden"
                           name="payment_data[processor_params][channels][{$channel}][scopes][]" value="{$scope}"
                    />
                {/foreach}
            </span>
        </label>
    </div>
    {if $definition.settings}
        <div class="pingpp-channel-settings" id="settings_{$channel}{$id}" style="{if !$is_enabled}display: none;{/if}">
            {foreach $definition.settings as $setting_id => $setting}
                <div class="control-group">
                    <label class="control-label {if $is_enabled && $setting.type != "select"}cm-required{/if}"
                           for="elm_{$channel}_settings_{$setting_id}{$id}"
                    >{__("pingpp.channel.`$channel`.settings.`$setting_id`")}:</label>
                    <div class="controls">
                        {if $setting.type == "select"}
                            <select id="elm_{$channel}_settings_{$setting_id}{$id}"
                                    name="payment_data[processor_params][channels][{$channel}][settings][{$setting_id}]"
                            >
                                {foreach $setting.variants as $variant_id}
                                    <option value="{$variant_id}"
                                            {if $processor_params.channels.$channel.settings.$setting_id == $variant_id}selected="selected"{/if}
                                    >{__("pingpp.channel.`$channel`.settings.`$setting_id`.variants.`$variant_id`")}</option>
                                {/foreach}
                            </select>
                        {elseif $setting.type == "text" || $setting.type == "password"}
                            <input id="elm_{$channel}_settings_{$setting_id}{$id}"
                                   type="{$setting.type}"
                                   name="payment_data[processor_params][channels][{$channel}][settings][{$setting_id}]"
                                   value="{$processor_params.channels.$channel.settings.$setting_id}"
                                   rows="5"
                            />
                        {elseif $setting.type == "textarea"}
                            <textarea id="elm_{$channel}_settings_{$setting_id}{$id}"
                                      name="payment_data[processor_params][channels][{$channel}][settings][{$setting_id}]"
                            >{$processor_params.channels.$channel.settings.$setting_id}</textarea>
                        {/if}
                    </div>
                </div>
            {/foreach}
        </div>
    {/if}
{/foreach}
</div>
