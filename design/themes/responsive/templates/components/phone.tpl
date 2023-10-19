{if $name}{strip}
    {*
    Example
    ---
    {include file="components/phone.tpl"
        id="example_id"
        name="example_name"
        value=$example_value
    }
    *}
    {$rnd = rand()}

    {* Checkout style *}
    {$checkout = $checkout|default:false}
    {if $checkout}
        {$id = $id|default:$rnd}
        {$show_controls = $show_controls|default:false}
        {$default_style = $default_style|default:false}
        {$required = $required|default:false}
        {$control_group_class = "litecheckout__field `$control_group_class`"|default:"litecheckout__field"}
        {$label_position = $label_position|default:"after"}
        {$label_class = "litecheckout__label `$label_class`"|default:"litecheckout__label"}
        {$label_text = $label_text|default:__("phone_mask_label")}
        {$class = "litecheckout__input litecheckout__input--mask-phone `$class`"}
        {$btn_group = "ty-btn-group--litecheckout `$btn_group`"}
        {$button_class = "ty-select-block__a--litecheckout `$button_class`"}
        {$input_phone_container_class = "ty-input-append--litecheckout `$input_phone_container_class`"}
        {$placeholder = ($placeholder) ? $placeholder : " "}
    {/if}

    {* Import *}
    {$id = $id|default:$rnd}
    {$is_phone_number_with_country_selection = ($settings.Appearance.phone_validation_mode === "phone_number_with_country_selection")}
    {$phone_mask_type_class = "ty-input-phone--`$settings.Appearance.phone_validation_mode`"|replace:"_":"-"}
    {$show_control_group = $show_control_group|default:true}
    {$show_controls = $show_controls|default:true}
    {$required = $required|default:false}
    {$name = $name|default:""}
    {$value = $value|default:""}
    {$title = $title|default:false}
    {$class = $class|default:""}
    {$attrs = $attrs|default:false}
    {$extra = $extra|default:""}
    {$control_group_class = $control_group_class|default:""}
    {$label_position = $label_position|default:"default"}
    {$label_class = $label_class|default:""}
    {$label_text = $label_text|default:__("phone_mask_label")}
    {$input_phone_container_class = $input_phone_container_class|default:""}
    {$btn_group = $btn_group|default:""}
    {$button_class = $button_class|default:""}
    {$default_style = $default_style|default:true}
    {$placeholder = $placeholder}
    {$autocomplete = $autocomplete|default:false}
    {$aria_label = $aria_label|default:false}
    {$attrs_string = $attrs_string|default:false}
    {$show_colon = $show_colon|default:false}
    {if $width === "full"}
        {* Empty *}
    {elseif $width === "large"}
        {$input_phone_container_class = "`$input_phone_container_class` ty-input-phone-container--large"}
    {else}
        {$input_phone_container_class = "`$input_phone_container_class` ty-input-phone-container--default"}
    {/if}

    {* Get countries list *}
    {$country_code = $profile_data.s_country|default:$settings.Checkout.default_country}
    {$countries = 1|fn_get_simple_phone_country_codes}
    {foreach $countries as $id => $country}
        {$countries[$id].symbol = "+`$country.phone_code`"}
    {/foreach}
    {* Set country code by phone *}
    {foreach $countries as $id => $country}
        {if $value|substr:0:($country.symbol|strlen) === $country.symbol}
            {$country_code = $country.country_code}
            {if $country.is_main_for_phone_code}
                {break}
            {/if}
        {/if}
    {/foreach}
    {if $placeholder !== false}
        {$placeholder = ($placeholder || $placeholder === "") ? $placeholder : $countries[$country_code|lower].symbol}
    {/if}
    {capture name="label"}
        <label for="{$id}" class="cm-mask-phone-label {if $default_style}ty-control-group__title{/if} {if $required}cm-required cm-trim{/if} {$label_class}">
            {$label_text}{if $show_colon}:{/if}
        </label>
    {/capture}
    {if $show_control_group}
    <div class="cm-mask-phone-group {if $default_style}ty-control-group{/if} {$control_group_class}" data-ca-phone-mask-group-id="{$id}">
        {if $label_position === "default"}
            {$smarty.capture.label nofilter}
        {/if}
    {/if}

    {if $show_controls}
        <div class="controls">
    {/if}
    <div class="cm-field-container {if $is_phone_number_with_country_selection}ty-input-append ty-input-append--phone-mask{/if} {$input_phone_container_class}">
        <input {""}
            type="tel" {""}
            id="{$id}" {""}
            name="{$name}" {""}
            value="{$value}" {""}
            class="cm-mask-phone ty-input-phone ty-input-fill {$phone_mask_type_class} {if $default_style}ty-input-text{/if} {$class}" {""}
            {if $title}
                title="{$title}" {""}
            {/if}
            {if $placeholder !== false}
                placeholder="{$placeholder}" {""}
            {/if}
            {if $autocomplete}
                autocomplete="{$autocomplete}" {""}
            {/if}
            {if $aria_label}
                aria-label="{$aria_label}" {""}
            {/if}
            data-ca-phone-mask-type="{$settings.Appearance.phone_validation_mode}" {""}
            data-ca-phone-mask-country="{$country_code}" {""}
            data-ca-phone-mask-symbol="{$countries[$country_code|lower].symbol}" {""}
            data-ca-check-filter="[data-ca-phone-mask-group-id='{$id}']" {""}
            {if $attrs}
                {$attrs|render_tag_attrs nofilter} {""}
            {/if}
            {if $attrs_string}
                {$attrs_string nofilter}
            {/if}
        />
        {if $label_position === "after"}
            {$smarty.capture.label nofilter}
        {/if}
        {$extra nofilter}
        {if $is_phone_number_with_country_selection}
            {include file="common/select_object.tpl"
                style="graphic"
                show_button_text=false
                show_button_symbol=false
                link_tpl=$config.current_url|fn_link_attach:"phone_country="
                suffix="phone_code_`$id`"
                items=$countries
                selected_id=$country_code|lower
                display_icons=true
                key_name="name"
                dropdown_menu_item_link_class="cm-phone-number-with-country-selection-li-link"
                assign="select_countries"
                class="cm-phone-number-with-country-selection-btn-group ty-btn-group--phone-mask ty-dropleft `$btn_group`"
                button_class="ty-select-block__a--phone-mask `$button_class`"
                show_group=true
            }
            {$select_countries|strip|replace:"> <":"><" nofilter}
        {/if}
    </div>
    {if $show_controls}
        </div>
    {/if}
    {if $show_control_group}
    </div>
    {/if}
{/strip}{/if}