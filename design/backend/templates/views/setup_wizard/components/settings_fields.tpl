{if $item.update_for_all
    && ($settings.Stores.default_state_update_for_all === "not_active" || fn_allowed_for("MULTIVENDOR"))
    && $app['storefront.repository']->getCount(['cache' => true]) > 1
}
    {$disable_input = true}
{/if}

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
    {if $smarty.capture.sw_header_first === "true"}
        </fieldset>
        </div>
    {/if}
    {capture name="sw_header_first"}true{/capture}
{include file="common/subheader.tpl" title=$item.description target="#collapsable_`$html_id`"}
<div id="collapsable_{$html_id}" class="in collapse">
    <fieldset>
        {elseif $item.type !== "SettingTypes::HIDDEN"|enum && $item.type !== "SettingTypes::SELECTABLE_BOX"|enum}
        {* Settings with label*}
        <div id="container_{$html_id}" class="control-group{if $class} {$class}{/if} {if $item.type == "SettingTypes::PHONE"|enum}cm-mask-phone-group{/if} {$item.section_name} {if $parent_item && $parent_item.value != "YesNo::YES"|enum}hidden{/if}" {if $item.type == "SettingTypes::PHONE"|enum}data-ca-phone-mask-group-id="{$html_id}"{/if}>
            <label for="{$html_id}" class="control-label {if $highlight && $item.name|in_array:$highlight}highlight{/if} {if $item.type == "SettingTypes::PHONE"|enum} cm-mask-phone-label{/if}">{$item.description nofilter}{if $item.tooltip}{include file="common/tooltip.tpl" tooltip=$item.tooltip}{/if}:
                {if $label_extra}
                    <p>{__($label_extra)}</p>
                {/if}
            </label>

            <div class="controls">
                {if $item.type === "SettingTypes::PASSWORD"|enum}
                    <input id="{$html_id}" type="password" name="{$html_name}" size="30" value="{$item.value}" class="input-text" {if $disable_input}disabled="disabled"{/if} />
                {elseif $item.type === "SettingTypes::TEXTAREA"|enum}
                    <textarea id="{$html_id}" name="{$html_name}" rows="5" cols="19" class="input-large" {if $disable_input}disabled="disabled"{/if}>{$item.value}</textarea>
                {elseif $item.type === "SettingTypes::CHECKBOX"|enum}
                    {if $item.name === "global_tracking" || $item.name === "default_tracking"}
                        {$default_checked_value = "ProductTracking::TRACK"|enum}
                        {$default_unchecked_value = "ProductTracking::DO_NOT_TRACK"|enum}
                    {else}
                        {$default_checked_value = "YesNo::YES"|enum}
                        {$default_unchecked_value = "YesNo::NO"|enum}
                    {/if}
                    {$false_checkbox_value = $item.false_checkbox_value|default:$default_unchecked_value}
                    {$true_checkbox_value = $item.true_checkbox_value|default:$default_checked_value}
                    <input type="hidden" name="{$html_name}" value="{$false_checkbox_value}" {if $disable_input}disabled="disabled"{/if} />
                    {include file="common/switcher.tpl"
                        checked=($item.value == $true_checkbox_value)
                        input_name=$html_name
                        input_value=$true_checkbox_value
                        input_id=$html_id
                        input_disabled=$disable_input
                    }
                {elseif $item.type === "SettingTypes::SELECTBOX"|enum}
                    <select id="{$html_id}" name="{$html_name}" {if $disable_input}disabled="disabled"{/if}>
                        {foreach $item.variants as $k => $v}
                            <option value="{$k}" {if $item.value === $k}selected="selected"{/if}>{$v}</option>
                        {/foreach}
                    </select>
                {elseif $item.type === "SettingTypes::RADIOGROUP"|enum}
                    <div class="select-field" id="{$html_id}">
                        {foreach $item.variants as $k => $v}
                            <label for="variant_{$item.name}_{$k}" class="radio">
                                <input type="radio" name="{$html_name}" value="{$k}" {if $item.value === $k}checked="checked"{/if} id="variant_{$item.name}_{$k}" {if $disable_input}disabled="disabled"{/if}> {$v}
                            </label>
                        {foreachelse}
                            {__("no_items")}
                        {/foreach}
                    </div>
                {elseif $item.type === "SettingTypes::MULTIPLE_SELECT"|enum}
                    <select id="{$html_id}" name="{$html_name}[]" multiple="multiple" {if $disable_input}disabled="disabled"{/if}>
                        {foreach $item.variants as $k => $v}
                            <option value="{$k}" {if $item.value.$k === "YesNo::YES"|enum}selected="selected"{/if}>{$v}</option>
                        {/foreach}
                    </select>
                    {__("multiple_selectbox_notice") nofilter}
                {elseif $item.type === "SettingTypes::MULTIPLE_CHECKBOXES"|enum}
                    <div class="select-field" id="{$html_id}">
                        <input type="hidden" name="{$html_name}" value="N" {if $disable_input}disabled="disabled"{/if} />
                        {foreach $item.variants as $k => $v}
                            <label for="variant_{$item.name}_{$k}" class="checkbox">
                                <input type="checkbox" name="{$html_name}[]" id="variant_{$item.name}_{$k}" value="{$k}" {if $item.value.$k == "YesNo::YES"|enum}checked="checked"{/if} {if $disable_input}disabled="disabled"{/if}>
                                {$v}
                            </label>
                        {foreachelse}
                            {__("no_items")}
                        {/foreach}
                    </div>
                {elseif $item.type === "SettingTypes::COUNTRY"|enum}
                    <select class="cm-country cm-location-sw-billing" id="{$html_id}" name="{$html_name}" {if $disable_input}disabled="disabled"{/if}>
                        <option value="">- {__("select_country")} -</option>
                        {$countries=""|fn_get_simple_countries}
                        {foreach $countries as $code => $country}
                            <option value="{$code}" {if $code === $item.value}selected="selected"{/if}>{$country}</option>
                        {/foreach}
                    </select>
                {elseif $item.type === "SettingTypes::STATE"|enum}
                    <select class="cm-state cm-location-sw-billing" id="{$html_id}" name="{$html_name}" {if $disable_input}disabled="disabled"{/if}>
                        <option value="">- {__("select_state")} -</option>
                    </select>
                    <input type="text" id="{$html_id}_d" name="{$html_name}" value="{$item.value}" size="32" maxlength="64" disabled="disabled" class="cm-state cm-location-sw-billing hidden" />
                {elseif $item.type === "SettingTypes::FILE"|enum}
                    <div class="input-append">
                        <input id="file_{$html_id}" type="text" name="{$html_name}" value="{$item.value}" size="30" {if $disable_input}disabled="disabled"{/if}>
                        <button id="{$html_id}" type="button" class="btn" onclick="Tygh.fileuploader.init('box_server_upload', this.id);" {if $disable_input}disabled="disabled"{/if}>{__("browse")}</button>
                    </div>
                {elseif $item.type === "SettingTypes::MULTIPLE_CHECKBOXES_FOR_SELECTBOX"|enum}
                    <div id="{$html_id}">
                        {foreach $item.variants as $k => $v}
                            <label for="variant_{$item.name}_{$k}" class="checkbox">
                                <input type="checkbox" class="cm-combo-checkbox" id="variant_{$item.name}_{$k}" name="{$html_name}[]" value="{$k}" {if $item.value.$k === "YesNo::YES"|enum}checked="checked"{/if} {if $disable_input}disabled="disabled"{/if}>
                                {$v}
                            </label>
                        {foreachelse}
                            {__("no_items")}
                        {/foreach}
                    </div>
                {elseif $item.type === "SettingTypes::SELECTBOX_WITH_SOURCE"|enum}
                    <select id="{$html_id}" name="{$html_name}" class="cm-combo-select" {if $disable_input}disabled="disabled"{/if}>
                        {foreach $item.variants as $k => $v}
                            <option value="{$k}" {if $item.value === $k}selected="selected"{/if}>{$v}</option>
                        {/foreach}
                    </select>
                {elseif $item.type === "SettingTypes::PHONE"|enum}
                    {$attrs = []}
                    {if $disable_input}
                        {$attrs = [disabled => "disabled"]}
                    {/if}
                    {if $placeholder}
                        {$placeholder_text = __($placeholder)}
                    {/if}
                    {include file="components/phone.tpl"
                        id=$html_id
                        name=$html_name
                        value=$item.value
                        placeholder=$placeholder_text
                        attrs=$attrs
                        show_control_group=false
                        show_controls=false
                        width="full"
                    }
                {else}
                    <input id="{$html_id}" type="text" name="{$html_name}" size="30" value="{$item.value}" class="{if $item.type === "SettingTypes::NUMBER"|enum} cm-value-integer{/if}" {if $disable_input}disabled="disabled"{/if} {if $placeholder}placeholder="{__($placeholder)}"{/if} />
                {/if}
                {if $field_extra_description}
                    <p>{if $field_extra_link}<a href="{$field_extra_link|fn_url}" target="_blank">{/if}{__($field_extra_description)}{if $field_extra_link}</a>{/if}</p>
                {/if}
                <div class="right update-for-all">
                    {include file="buttons/update_for_all.tpl" display=$item.update_for_all object_id=$item.object_id name="update_all_vendors[`$item.object_id`]" hide_element=$html_id}
                </div>
            </div>
        </div>
        {elseif $item.type == "SettingTypes::SELECTABLE_BOX"|enum}
        <div class="control-group">
            {include file="common/selectable_box.tpl" addon=$section name=$html_name id=$html_id fields=$item.variants selected_fields=$item.value}
        </div>
        {/if}
        {if $total === $index && $smarty.capture.sw_header_first === "true"}
    </fieldset>
</div>
{/if}
