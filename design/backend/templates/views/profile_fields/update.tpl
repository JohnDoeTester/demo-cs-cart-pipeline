
{if $field}
    {assign var="id" value=$field.field_id}
{else}
    {assign var="id" value="0"}
{/if}

{script src="js/tygh/tabs.js"}

{literal}
<script>
function fn_check_field_type(value, tab_id)
{
    Tygh.$('#' + tab_id).toggleBy(!(value == 'R' || value == 'S'));
}
</script>
{/literal}

{if $field.is_default == "Y" || $field.section == "ProfileFieldSections::BILLING_ADDRESS"|enum}
    {assign var="block_fields" value=true}
{/if}

{capture name="blocked_changing_info"}
    {if $block_fields && $field.is_default != "Y"}
        {__("edit_corresponding_profile_field", ["[URL]" => fn_url("profile_fields.update&field_id=`$field.matching_id`")]) nofilter}
    {/if}
{/capture}

{if "ULTIMATE"|fn_allowed_for && $runtime.company_id}
    {assign var="hide_inputs" value="cm-hide-inputs"}
    {assign var="hide_multiple_buttons" value="hidden"}
{/if}

{capture name="mainbox"}

<form action="{""|fn_url}" enctype="multipart/form-data" method="post" name="add_fields_form" class="form-horizontal form-edit  {$hide_inputs}">

<div class="cm-j-tabs cm-track tabs">
    <ul class="nav nav-tabs">
        <li id="tab_new_profile{$id}" class="cm-js active"><a>{__("general")}</a></li>
        <li id="tab_variants{$id}"
            class="cm-js {if $field.is_default == "Y" || ($field.field_type != "ProfileFieldTypes::RADIO"|enum && $field.field_type != "ProfileFieldTypes::SELECT_BOX"|enum)}hidden{/if}"
        ><a>{__("variants")}</a></li>
    </ul>
</div>
<div class="cm-tabs-content">
    <div id="content_tab_new_profile{$id}">
        <input type="hidden" name="field_data[field_id]" value="{$field.field_id}" />
        <input type="hidden" name="field_data[matching_id]" value="{$field.matching_id}" />
        <input type="hidden" name="field_id" value="{$id}" />

        <div class="control-group">
            <label for="elm_field_description" class="control-label cm-required">{__("name")}:</label>
            <div class="controls">
            <input id="elm_field_description" class="input-large" type="text" name="field_data[description]" value="{$field.description}" />
            </div>
        </div>

        {if $field.field_type == "ProfileFieldTypes::VENDOR_TERMS"|enum}
            <div class="control-group">
                <label for="elm_field_vendor_terms_content" class="control-label">{__("vendor_terms_field_terms_text")}:</label>
                <div class="controls">
                    <a href="{fn_url("languages.translations?q=vendor_terms_n_conditions_content")}" target="_blank">{__("edit")}</a>
                </div>
            </div>

            <div class="control-group">
                <label for="elm_field_vendor_terms_alert" class="control-label">{__("vendor_terms_field_alert")}:</label>
                <div class="controls">
                    <a href="{fn_url("languages.translations?q=vendor_terms_n_conditions_alert")}" target="_blank">{__("edit")}</a>
                </div>
            </div>
        {/if}

        {if !$field_name}
            <div class="control-group">
                <label for="elm_field_name" class="control-label cm-required">{__("code")}:</label>
                <div class="controls">
                    <input id="elm_field_name" class="input-text-short" type="text" name="field_data[field_name]" value="{$field.field_name}" />
                    <p class="muted description">{__("profile_field_name_tooltip")}</p>
                </div>
            </div>
        {else}
            <div class="control-group">
                <label class="control-label">{__("code")}:</label>
                <div class="controls">
                    <span class="shift-input">{$field_name}</span>
                </div>
                <input name="field_data[field_name]" type="hidden" value="{$field.field_name}" />
            </div>
        {/if}

        <div class="control-group">
            <label class="control-label" for="elm_field_position">{__("position")}:</label>
            <div class="controls">
            <input class="input-text-short" id="elm_field_position" type="text" size="3" name="field_data[position]" value="{$field.position}" />
            </div>
        </div>

        {if !$field.field_id && $profile_type}
            <input type="hidden" name="field_data[profile_type]" value="{$profile_type}" />
        {/if}

        <div class="control-group">
            <label class="control-label" for="elm_field_type">{__("type")}:</label>
            <div class="controls">
                {if $field.field_type !== "ProfileFieldTypes::STATE"|enum && $field.field_type !== "ProfileFieldTypes::COUNTRY"|enum && $field.field_type !== "ProfileFieldTypes::ADDRESS_TYPE"|enum}
                    <select id="elm_field_type" name="field_data[field_type]" onchange="fn_check_field_type(this.value, 'tab_variants{$id}');" {if $block_fields}disabled="disabled"{/if}>
                        {hook name="profile_fields:field_types"}
                        <option value="{"ProfileFieldTypes::PHONE"|enum}" {if $field.field_type == "ProfileFieldTypes::PHONE"|enum}selected="selected"{/if}>{__("phone")}</option>
                        <option value="{"ProfileFieldTypes::POSTAL_CODE"|enum}" {if $field.field_type == "ProfileFieldTypes::POSTAL_CODE"|enum}selected="selected"{/if}>{__("zip_postal_code")}</option>
                        <option value="{"ProfileFieldTypes::CHECKBOX"|enum}" {if $field.field_type == "ProfileFieldTypes::CHECKBOX"|enum}selected="selected"{/if}>{__("checkbox")}</option>
                        <option value="{"ProfileFieldTypes::DATE"|enum}" {if $field.field_type == "ProfileFieldTypes::DATE"|enum}selected="selected"{/if}>{__("date")}</option>
                        <option value="{"ProfileFieldTypes::INPUT"|enum}" {if $field.field_type == "ProfileFieldTypes::INPUT"|enum}selected="selected"{/if}>{__("input_field")}</option>
                        <option value="{"ProfileFieldTypes::RADIO"|enum}" {if $field.field_type == "ProfileFieldTypes::RADIO"|enum}selected="selected"{/if}>{__("radiogroup")}</option>
                        <option value="{"ProfileFieldTypes::SELECT_BOX"|enum}" {if $field.field_type == "ProfileFieldTypes::SELECT_BOX"|enum}selected="selected"{/if}>{__("selectbox")}</option>
                        <option value="{"ProfileFieldTypes::TEXT_AREA"|enum}" {if $field.field_type == "ProfileFieldTypes::TEXT_AREA"|enum}selected="selected"{/if}>{__("textarea")}</option>
                        <option value="{"ProfileFieldTypes::EMAIL"|enum}" {if $field.field_type == "ProfileFieldTypes::EMAIL"|enum}selected="selected"{/if}>{__("email")}</option>
                        <option value="{"ProfileFieldTypes::FILE"|enum}" {if $field.field_type == "ProfileFieldTypes::FILE"|enum}selected="selected"{/if}>{__("file")}</option>
                        {if $block_fields}
                            {* NOTE: Type "VENDOR_TERMS" cannot be created manually, but available for seller profile *}
                            <option value="{"ProfileFieldTypes::VENDOR_TERMS"|enum}" {if $field.field_type == "ProfileFieldTypes::VENDOR_TERMS"|enum}selected="selected"{/if}>{__("vendor_terms")}</option>
                        {/if}
                        {/hook}
                    </select>
                {else}
                    <select id="elm_field_type" name="field_data[field_type]" disabled="disabled">
                        {hook name="profile_fields:field_types"}
                        <option value="{"ProfileFieldTypes::STATE"|enum}" {if $field.field_type == "ProfileFieldTypes::STATE"|enum}selected="selected"{/if}>{__("states")}</option>
                        <option value="{"ProfileFieldTypes::COUNTRY"|enum}" {if $field.field_type == {"ProfileFieldTypes::COUNTRY"|enum}}selected="selected"{/if}>{__("country")}</option>
                        <option value="{"ProfileFieldTypes::ADDRESS_TYPE"|enum}" {if $field.field_type == "ProfileFieldTypes::ADDRESS_TYPE"|enum}selected="selected"{/if}>{__("address_type")}</option>
                        {/hook}
                    </select>
                {/if}
                {if $block_fields}
                    <input type="hidden" name="field_data[field_type]" value="{$field.field_type}" />
                    <div class="micro-note">{$smarty.capture.blocked_changing_info nofilter}</div>
                {/if}
            </div>
        </div>

        <div class="control-group">
            <label class="control-label" for="elm_field_section">{__("section")}:</label>
            <div class="controls">
            {if $id}
                <input type="hidden" name="field_data[section]" value="{$field.section}" />
                <span class="shift-input">
                    {if $field.section == "ProfileFieldSections::CONTACT_INFORMATION"|enum}
                        {__("contact_information")}
                    {elseif $field.section == "ProfileFieldSections::BILLING_ADDRESS"|enum || $field.section == "ProfileFieldSections::SHIPPING_ADDRESS"|enum}
                        {__("billing_address")}/{__("shipping_address")}
                    {/if}
                </span>
            {else}
                <select id="elm_field_section" name="field_data[section]">
                    {if in_array("ProfileFieldSections::CONTACT_INFORMATION"|enum, (array) $profile_types[$profile_type]["allowed_sections"])}
                    <option value="{"ProfileFieldSections::CONTACT_INFORMATION"|enum}"
                            {if $field.section == "ProfileFieldSections::CONTACT_INFORMATION"|enum}selected="selected"{/if}
                    >{__("contact_information")}</option>
                    {/if}
                    {if in_array("ProfileFieldSections::BILLING_AND_SHIPPING_ADDRESS"|enum, (array) $profile_types[$profile_type]["allowed_sections"])}
                    <option value="{"ProfileFieldSections::BILLING_AND_SHIPPING_ADDRESS"|enum}"
                            {if $field.section == "ProfileFieldSections::BILLING_AND_SHIPPING_ADDRESS"|enum}selected="selected"{/if}
                    >{__("billing_address")}/{__("shipping_address")}</option>
                    {/if}
                </select>
            {/if}
            </div>
        </div>

        <div class="control-group">
            <label class="control-label" for="elm_field_user_class">{__("user_class")}:</label>
            <div class="controls">
                <input id="elm_field_user_class" class="input-large" type="text" name="field_data[class]" value="{$field.class}" />
            </div>
        </div>

        <div class="control-group">
            <label class="control-label" for="elm_field_wrapper_class">{__("wrapper_class")}:</label>
            <div class="controls">
                <input id="elm_field_wrapper_class" class="input-large" type="text" name="field_data[wrapper_class]" value="{$field.wrapper_class}" />
            </div>
        </div>

        {foreach $profile_types[$profile_type]["allowed_areas"] as $area}
            {$_show = "`$area`_show"}
            {$_required = "`$area`_required"}
            <div class="control-group">
                <label class="control-label">{__($area)} ({__("show")}&nbsp;/&nbsp;{__("required")}):</label>
                <div class="controls">
                    <input type="hidden"
                        name="field_data[{$_show}]"
                        value="{if ($field.field_name === "email" && $field.is_default === "YesNo::YES"|enum) || ($field.field_name === "company" && $field.profile_type === "ProfileTypes::CODE_SELLER"|enum && $field.is_default === "YesNo::YES"|enum)}{"YesNo::YES"|enum}{else}{"YesNo::NO"|enum}{/if}"
                    />
                    <input type="checkbox"
                        name="field_data[{$_show}]"
                        value="{"YesNo::YES"|enum}"
                        id="sw_req_{$_required}"
                        class="cm-switch-availability"
                        {if $field.$_show === "YesNo::YES"|enum}
                            checked="checked"
                        {/if}
                        {if $field.field_name === "email" && $field.is_default === "YesNo::YES"|enum || ($field.field_name === "company" && $field.profile_type === "ProfileTypes::CODE_SELLER"|enum)}
                            disabled="disabled"
                        {/if}
                    />&nbsp;

                    <input type="hidden"
                        name="field_data[{$_required}]"
                        value="{if ($field.field_name === "email" && $field.is_default === "YesNo::YES"|enum) || ($field.field_name === "company" && $field.profile_type === "ProfileTypes::CODE_SELLER"|enum && $field.is_default === "YesNo::YES"|enum)}{"YesNo::YES"|enum}{else}{"YesNo::NO"|enum}{/if}"
                    />
                    <span id="req_{$_required}{if $field.field_name === "email"}_email{/if}">
                        <input type="checkbox"
                            name="field_data[{$_required}]"
                            value="{"YesNo::YES"|enum}"
                            class=""
                            {if $field.$_required === "YesNo::YES"|enum}
                                checked="checked"
                            {/if}
                            {if $field.$_show === "YesNo::NO"|enum || $field.field_name === "email" && $field.is_default === "YesNo::YES"|enum || ($field.field_name === "company" && $field.profile_type === "ProfileTypes::CODE_SELLER"|enum)}
                                disabled="disabled"
                            {/if}
                        />
                    </span>
                </div>
            </div>
        {/foreach}

        {if $field.profile_type === "ProfileTypes::CODE_SELLER"|enum || $profile_type === "ProfileTypes::CODE_SELLER"|enum}
            <div class="control-group">
                <label class="control-label">{__("show_on_storefront")}:</label>
                <div class="controls">
                    <input type="hidden" name="field_data[storefront_show]" value="{if $field.field_name === "company"  || $field.field_name === "company_description"}{"YesNo::YES"|enum}{else}{"YesNo::NO"|enum}{/if}"/>
                    <input type="checkbox" name="field_data[storefront_show]" value="{"YesNo::YES"|enum}" {if $field.storefront_show === "YesNo::YES"|enum || !$id}checked="checked"{/if} {if $field.field_name === "company" || $field.field_name === "company_description"}disabled="disabled"{/if}/>
                </div>
            </div>
        {/if}

        {hook name="profile_fields:profile_data"}
        {/hook}
    <!--content_tab_new_profile{$id}--></div>

    <div class="{if $field.is_default == "Y" || ($field.field_type != "ProfileFieldTypes::RADIO"|enum && $field.field_type != "ProfileFieldTypes::SELECT_BOX"|enum)}hidden{/if}" id="content_tab_variants{$id}">
        {if $block_fields}
            <div>{$smarty.capture.blocked_changing_info nofilter}</div>
        {/if}
        <div class="table-responsive-wrapper">
            <table class="table table-middle table--relative table-responsive">
            <tr id="field_values_{$id}" class="no-border td-no-bg">
                <td colspan="{$_colspan}">
                    <table width="1" class="table">
                        <thead>
                            <tr class="cm-first-sibling">
                                <th style="width: 8%">{__("position_short")}</th>
                                <th style="width: 68%">{__("description")}</th>
                                <th>&nbsp;</th>
                            </tr>
                        </thead>
                    {if $field}
                        {foreach name="values" from=$field.values key="value_id" item="value"}
                        <tr class="cm-first-sibling">
                            <td data-th="{__("position_short")}">
                                <input class="input-micro" size="3" type="text" name="field_data[values][{$value_id}][position]"
                                   value="{$smarty.foreach.values.iteration}" {if $block_fields}disabled{/if} />
                            </td>
                            <td data-th="{__("description")}">
                                <input class="span7" type="text" name="field_data[values][{$value_id}][description]"
                                   value="{$value}"  {if $block_fields}disabled{/if} />
                            </td>
                            <td data-th="{__("tools")}">
                                {if !$block_fields}{include file="buttons/multiple_buttons.tpl" only_delete="Y"}{/if}
                            </td>
                        </tr>
                        {/foreach}
                    {/if}
                    {if !$block_fields}
                    <tr id="box_elm_values_{$id}" {if $hide_multiple_buttons}class="{$hide_multiple_buttons}"{/if}>
                        <td data-th="{__("position_short")}"><input class="input-micro" size="3" type="text" name="field_data[add_values][0][position]" /></td>
                        <td data-th="{__("description")}"><input class="span7" type="text" name="field_data[add_values][0][description]" /></td>
                        <td data-th="{__("tools")}">{include file="buttons/multiple_buttons.tpl" item_id="elm_values_`$id`" tag_level=2}</td>
                    </tr>
                {/if}
                    </table>
                </td>
            </tr>
            </table>
        </div>
    <!--content_tab_variants{$id}--></div>
</div>
</form>

{capture name="buttons"}
    {include file="buttons/save_cancel.tpl" but_name="dispatch[profile_fields.update]" but_target_form="add_fields_form" save=$id}
{/capture}

{/capture}

{include file="common/mainbox.tpl"
    title=($id) ? $field.description : __("new_profile_field")
    content=$smarty.capture.mainbox
    select_languages=true
    buttons=$smarty.capture.buttons
}
