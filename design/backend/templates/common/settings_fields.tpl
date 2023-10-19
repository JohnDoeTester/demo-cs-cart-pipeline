{if ($item.options)}
    {$label_attrs = $item.options.label_attributes|default:[]}
    {$input_attrs = $item.options.input_attributes|default:[]}
    {$select_attrs = $item.options.select_attributes|default:[]}
    {$button_attrs = $item.options.button_attributes|default:[]}
{/if}
{$default_attrs = $default_attrs|default:[]}
{if $item.update_for_all
    && ($settings.Stores.default_state_update_for_all === "not_active" || fn_allowed_for("MULTIVENDOR"))
    && $app['storefront.repository']->getCount(['cache' => true]) > 1
}
    {$disable_input = true}
{/if}

{if $disable_input}
    {$default_attrs.disabled = "disabled"}
{/if}
{$SHORT_DIVIDER = "──"}

{* Global/Individual settings *}
{if isset($item.global_setting)}
    {$global_selector = true}

    {* Select settings *}
    {* Default option *}
    {$default_option = true}
    {if $item.has_global_value}
        {$default_option_hidden = true}
    {/if}
    {$default_option_text = __("select_default")}

    {if $global_selector && !$item.has_global_value}
        {* Options *}
        {$option_suffix = __('default')}
        {$option_suffix = "(`$option_suffix`)"}

        {* Inputs *}
        {$input_placeholder = __("default")}
    {/if}
{/if}

{* Default option settings for select *}
{$default_option = $default_option|default:false}
{$default_option_hidden = $default_option_hidden|default:false}
{$default_option_text = $default_option_text|default:__("select_selectbox_option")}

{if $parent_item}
<script>
(function($, _) {
    $('#{$parent_item_html_id}').on('click', function() {
        $('#container_{$html_id}').toggle();
    });
}(Tygh.$, Tygh));
</script>
{/if}

{* Settings without label*}
{if $item.type === "SettingTypes::INFO"|enum}
    <div>{$item.info nofilter}</div>
{elseif $item.type === "SettingTypes::TEMPLATE"|enum}
    <div>{include file="addons/`$smarty.request.addon`/settings/`$item.value`"}</div>
{elseif $item.type === "SettingTypes::PERMANENT_TEMPLATE"|enum}
    <div>{include file="addons/`$smarty.request.addon`/settings/`$item.value`" skip_addon_check=true}</div>
{elseif $item.type === "SettingTypes::HEADER"|enum}
    {if $smarty.capture.header_first == 'true'}
            </fieldset>
        </div>
    {/if}
    {capture name="header_first"}true{/capture}
    {include file="common/subheader.tpl" title=$item.description target="#collapsable_`$html_id`"}
    <div id="collapsable_{$html_id}" class="in collapse">
        <fieldset>
{elseif $item.type !== "SettingTypes::HIDDEN"|enum && $item.type !== "SettingTypes::SELECTABLE_BOX"|enum}
    {* Settings with label*}
    <div id="container_{$html_id}" class="control-group{if $class} {$class}{/if} {$item.section_name}{if $parent_item && $parent_item.value != "YesNo::YES"|enum} hidden{/if}{if $highlight && $item.name|in_array:$highlight} row-highlight{/if} {if $item.type == "SettingTypes::PHONE"|enum}cm-mask-phone-group{/if}" {if $item.type == "SettingTypes::PHONE"|enum}data-ca-phone-mask-group-id="{$html_id}"{/if}>
        {$default_label_attrs = [
            "for"   => $html_id,
            "class" => "control-label {if $item.type == "SettingTypes::PHONE"|enum} cm-mask-phone-label{/if}"
        ]}
        {$extended_label_attrs = [
            "class" => ($highlight && $item.name|in_array:$highlight) ? "highlight" : ""
        ]}
        <label {$label_attrs|render_tag_attrs:$default_label_attrs:$extended_label_attrs nofilter}>{$item.description nofilter}:
            {hook name="settings_fields:setting_description"}
            {if $item.tooltip}<div class="muted description">{$item.tooltip nofilter}</div>{/if}
            {/hook}
        </label>

        <div class="controls">
            {capture name="setting_field"}
            {if $item.type === "SettingTypes::PASSWORD"|enum}
                {$default_attrs = array_merge($default_attrs, [
                    "id"          => "{$html_id}",
                    "name"        => "{$html_name}",
                    "type"        => "password",
                    "size"        => "30",
                    "class"       => "input-text",
                    "placeholder" => "{$input_placeholder}",
                    "value"       => "{$item.value}"
                ])}
                <input {$input_attrs|render_tag_attrs:$default_attrs nofilter}/>
            {elseif $item.type === "SettingTypes::TEXTAREA"|enum}
                {$default_attrs = array_merge($default_attrs, [
                    "id"          => "{$html_id}",
                    "name"        => "{$html_name}",
                    "type"        => "password",
                    "class"       => "input-large",
                    "placeholder" => "{$input_placeholder}",
                    "rows"        => "5",
                    "cols"        => "19"
                ])}
                <textarea {$input_attrs|render_tag_attrs:$default_attrs nofilter}>{$item.value}</textarea>
            {elseif $item.type === "SettingTypes::CHECKBOX"|enum}
                {$default_checked_value = "YesNo::YES"|enum}
                {$default_unchecked_value = "YesNo::NO"|enum}
                {$checked_value = $input_attrs.checked_value|default:$default_checked_value}
                {$unchecked_value = $input_attrs.unchecked_value|default:$default_unchecked_value}
                <input type="hidden" name="{$html_name}" value="{$unchecked_value}" {if $disable_input}disabled="disabled"{/if} />
                {$default_attrs = array_merge($default_attrs, [
                    "id"    => "{$html_id}",
                    "name"  => "{$html_name}",
                    "type"  => "checkbox",
                    "value" => $checked_value
                ])}
                {if $item.value == $checked_value}
                    {$default_attrs.checked = "checked"}
                {/if}
                <input {$input_attrs|render_tag_attrs:$default_attrs nofilter} />
            {elseif $item.type === "SettingTypes::SELECTBOX"|enum}
                {$default_attrs = array_merge($default_attrs, [
                    "id"   => "{$html_id}",
                    "name" => "{$html_name}"
                ])}
                <select {$input_attrs|render_tag_attrs:$default_attrs nofilter}>
                    {if $default_option}
                        <option value="" disabled {if $default_option_hidden}class="hidden"{/if} data-ca-type="defaultOption">
                            {$SHORT_DIVIDER} {$default_option_text} {$SHORT_DIVIDER}
                        </option>
                    {/if}
                    {foreach $item.variants as $k => $v}
                        <option
                            value="{$k}"
                            {if $item.value == $k}selected="selected"{/if}
                            data-ca-value="{$v}"
                        >
                            {$v} {$option_suffix}
                        </option>
                    {/foreach}
                </select>
            {elseif $item.type === "SettingTypes::RADIOGROUP"|enum}
                <div class="select-field" id="{$html_id}">
                    {foreach $item.variants as $k => $v}
                        <label for="variant_{$item.name}_{$k}" class="radio">
                            {$attrs = array_merge($default_attrs, [
                                "type"  => "radio",
                                "name"  => "{$html_name}",
                                "value" => "{$k}",
                                "id"    => "variant_{$item.name}_{$k}"
                            ])}
                            {if $item.value == $k}
                                {$attrs.checked = "checked"}
                            {/if}
                            <input {$input_attrs|render_tag_attrs:$attrs nofilter}> {$v}
                        </label>
                    {foreachelse}
                        {__("no_items")}
                    {/foreach}
                </div>
            {elseif $item.type === "SettingTypes::MULTIPLE_SELECT"|enum}
                <input type="hidden" name="{$html_name}" value="" {if $disable_input}disabled="disabled"{/if} />
                {$default_attrs = array_merge($default_attrs, [
                    "id"       => "{$html_id}",
                    "name"     => "{$html_name}[]",
                    "multiple" => "multiple"
                ])}
                <select {$input_attrs|render_tag_attrs:$default_attrs nofilter}>
                    {foreach $item.variants as $k => $v}
                        <option value="{$k}" {if $item.value && $item.value.$k === "YesNo::YES"|enum}selected="selected"{/if}>{$v}</option>
                    {/foreach}
                </select>
                <div class="muted description">{__("multiple_selectbox_notice") nofilter}</div>
            {elseif $item.type === "SettingTypes::MULTIPLE_CHECKBOXES"|enum}
                <div class="select-field" id="{$html_id}">
                    <input type="hidden" name="{$html_name}" value="{"YesNo::NO"|enum}" {if $disable_input}disabled="disabled"{/if} />
                    {include file="common/adaptive_object_selection.tpl"
                        input_name=$html_name
                        input_id="variant_{$item.name}"
                        item_ids=$item.value_ids
                        items=$item.variants
                        type=$html_name
                        class_prefix=$html_name
                        close_on_select="false"
                        list_mode=true
                        is_default_array_check=false
                    }
                </div>
            {elseif $item.type === "SettingTypes::COUNTRY"|enum}
                {$default_attrs = array_merge($default_attrs, [
                    "id"    => "{$html_id}",
                    "name"  => "{$html_name}",
                    "class" => "cm-country cm-location-billing"
                ])}
                <select {$input_attrs|render_tag_attrs:$default_attrs nofilter}>
                    <option value="">- {__("select_country")} -</option>
                    {assign var="countries" value=""|fn_get_simple_countries}
                    {foreach $countries as $code => $country}
                        <option value="{$code}" {if $code == $item.value}selected="selected"{/if}>{$country}</option>
                    {/foreach}
                </select>
            {elseif $item.type === "SettingTypes::STATE"|enum}
                {$default_select_attrs = array_merge($default_attrs, [
                    "id"    => "{$html_id}",
                    "name"  => "{$html_name}",
                    "class" => "cm-state cm-location-billing"
                ])}
                <select {$select_attrs|render_tag_attrs:$default_select_attrs nofilter}>
                    <option value="">- {__("select_state")} -</option>
                </select>
                {$default_input_attrs = array_merge($default_attrs, [
                    "id"          => "{$html_id}_d",
                    "name"        => "{$html_name}",
                    "value"       => "{$item.value}",
                    "type"        => "text",
                    "size"        => "32",
                    "maxlength"   => "64",
                    "placeholder" => "{$input_placeholder}",
                    "disabled"    => "disabled",
                    "class"       => "cm-state cm-location-billing"
                ])}
                {$extended_input_attrs = [
                    "class" => "hidden"
                ]}
                <input {$input_attrs|render_tag_attrs:$default_input_attrs:$extended_input_attrs nofilter} />
            {elseif $item.type === "SettingTypes::FILE"|enum}
                <div class="input-append">
                    {$default_input_attrs = array_merge($default_attrs, [
                        "id"          => "file_{$html_id}",
                        "name"        => "{$html_name}",
                        "value"       => "{$item.value}",
                        "type"        => "text",
                        "placeholder" => "{$input_placeholder}",
                        "size"        => "30"
                    ])}
                    <input {$input_attrs|render_tag_attrs:$default_input_attrs nofilter}>
                    {$default_button_attrs = array_merge($default_attrs, [
                        "id"      => "{$html_id}",
                        "class"   => "btn",
                        "type"    => "button",
                        "onclick" => "Tygh.fileuploader.init('box_server_upload', this.id);"
                    ])}
                    <button {$button_attrs|render_tag_attrs:$default_button_attrs nofilter}>{__("browse")}</button>
                </div>
            {elseif $item.type === "SettingTypes::MULTIPLE_CHECKBOXES_FOR_SELECTBOX"|enum}
                <div class="cm-combo-checkbox-group" id="{$html_id}">
                    {foreach $item.variants as $variant_key => $variant_name}
                        <label for="variant_{$item.name}_{$variant_key}" class="checkbox">
                            {$attrs = array_merge($default_attrs, [
                                "id"    => "variant_{$item.name}_{$variant_key}",
                                "name"  => "{$html_name}[]",
                                "class" => "cm-combo-checkbox",
                                "value" => "{$variant_key}",
                                "type"  => "checkbox"
                            ])}
                            {if $item.value[$variant_key] == "YesNo::YES"|enum}
                                {$attrs.checked = "checked"}
                            {/if}
                            <input {$input_attrs|render_tag_attrs:$attrs nofilter}>
                            {$variant_name}
                        </label>
                    {foreachelse}
                        {__("no_items")}
                    {/foreach}
                </div>
            {elseif $item.type === "SettingTypes::SELECTBOX_WITH_SOURCE"|enum}
                {$default_attrs = array_merge($default_attrs, [
                    "id"    => "{$html_id}",
                    "name"  => "{$html_name}",
                    "class" => "cm-combo-select"
                ])}
                <select {$input_attrs|render_tag_attrs:$default_attrs nofilter}>
                    {foreach $item.variants as $variant_key => $variant_name}
                        <option value="{$variant_key}" {if $item.value == $variant_key}selected="selected"{/if}>{$variant_name}</option>
                    {/foreach}
                </select>
            {elseif $item.type === "SettingTypes::UNEDITABLE"|enum}
                {$item.value}
            {elseif $item.type === "SettingTypes::PHONE"|enum}
                {if $disable_input}
                    {$attrs = [disabled => "disabled"]}
                {/if}
                {include file="components/phone.tpl"
                    id=$html_id
                    name=$html_name
                    value=$item.value
                    placeholder=$input_placeholder
                    show_control_group=false
                    show_controls=false
                    attrs=$attrs
                }
            {else}
                {$default_attrs = array_merge($default_attrs, [
                    "id"          => "{$html_id}",
                    "type"        => "text",
                    "name"        => "{$html_name}",
                    "size"        => "30",
                    "class"       => "{if $item.type === "SettingTypes::NUMBER"|enum} cm-value-integer{/if}",
                    "placeholder" => "{$input_placeholder}",
                    "value"       => "{$item.value}"
                ])}
                <input {$input_attrs|render_tag_attrs:$default_attrs nofilter}/>
            {/if}
            {/capture}
            {capture name="update_for_all"}
                {if $item.global_setting}
                    {$settings_ids = [$item.object_id, $item.global_setting.object_id]}
                    {$settings_input_names = [$item.object_id => "update_all_vendors[`$item.object_id`]", $item.global_setting.object_id => "update_all_vendors[`$item.global_setting.object_id`]"]}
                {else}
                    {$settings_ids = [$item.object_id]}
                    {$settings_input_names = [$item.object_id => "update_all_vendors[`$item.object_id`]"]}
                {/if}

                {include file="buttons/update_for_all.tpl"
                    display=$item.update_for_all
                    hide_element=$html_id
                    object_ids=$settings_ids
                    names=$settings_input_names
                    static_position=$global_selector
                    component="settings.`$item.name`"
                }
            {/capture}
            {if $global_selector}
                {include file="components/global_individual.tpl"
                    content=$smarty.capture.setting_field
                    extra=$smarty.capture.update_for_all
                    html_id=$html_id
                    html_name=$html_name
                    global_setting=$item.global_setting
                    disable_input=$disable_input
                }
            {else}
                {$smarty.capture.setting_field nofilter}
                {$smarty.capture.update_for_all nofilter}
            {/if}
        </div>
    </div>
{elseif $item.type === "SettingTypes::SELECTABLE_BOX"|enum}
    <div class="control-group">
        {include file="common/selectable_box.tpl" addon=$section_name name=$html_name id=$html_id fields=$item.variants selected_fields=$item.value}
    </div>
{/if}
{if $total == $index && $smarty.capture.header_first == 'true'}
    </fieldset>
        </div>
{/if}
