{if ($settings.General.display_options_modifiers == "Y" && ($auth.user_id  || ($settings.Checkout.allow_anonymous_shopping != "hide_price_and_add_to_cart" && !$auth.user_id)))}
    {$show_modifiers = true}
{/if}

<input type="hidden" name="appearance[details_page]" value="{$details_page}" />
{foreach $product.detailed_params as $param => $value}
    <input type="hidden" name="additional_info[{$param}]" value="{$value}" />
{/foreach}

{if $product_options}

{if $obj_prefix}
    <input type="hidden" name="appearance[obj_prefix]" value="{$obj_prefix}" />
{/if}

{if $location == "cart" || $product.object_id}
    <input type="hidden" name="{$name}[{$id}][object_id]" value="{$id|default:$obj_id}" />
{/if}

{if $extra_id}
    <input type="hidden" name="extra_id" value="{$extra_id}" />
{/if}

{* Simultaneous options *}
{if $product.options_type == "ProductOptionsApplyOrder::SEQUENTIAL"|enum && $location == "cart"}
    {$disabled = true}
{/if}

<div id="option_{$id}_AOC">
    <div class="cm-picker-product-options ty-product-options" id="opt_{$obj_prefix}{$id}">
        {foreach $product_options as $po}

        {$selected_variant = ""}

        <div class="ty-control-group ty-product-options__item {if !$capture_options_vs_qty}product-list-field{/if} clearfix"
             id="opt_{$obj_prefix}{$id}_{$po.option_id}"
        >
            {if !($po.option_type && (
                    $po.option_type == "ProductOptionTypes::SELECTBOX"|enum
                    || $po.option_type == "ProductOptionTypes::RADIO_GROUP"|enum
                    || $po.option_type == "ProductOptionTypes::CHECKBOX"|enum
                )
                && !$po.variants
                && $po.missing_variants_handling == "H"
            )}
                <label id="option_description_{$id}_{$po.option_id}"
                       {if $po.option_type !== "ProductOptionTypes::FILE"|enum}
                           for="option_{$obj_prefix}{$id}_{$po.option_id}"
                       {/if}
                       class="ty-control-group__label ty-product-options__item-label {if $po.required == "Y"}cm-required{/if} {if $po.regexp}cm-regexp{/if} {if $po.description|trim}ty-product-options__item-label--tooltip{/if}"
                       {if $po.regexp}
                           data-ca-regexp="{$po.regexp}"
                           data-ca-message="{$po.incorrect_message}"
                       {/if}
                >
                    {$po.option_name}{if $po.description|trim}{include file="common/tooltip.tpl"
                        tooltip=$po.description
                        wrapper_class="ty-tooltip-block--options ty-tooltip-block--options-desktop"
                    }{/if}:
                </label>
                {if $po.description|trim}
                    {include file="common/tooltip.tpl"
                        tooltip=$po.description
                        wrapper_class="ty-tooltip-block--options ty-tooltip-block--options-mobile"
                    }
                {/if}
            {if $po.option_type == "ProductOptionTypes::SELECTBOX"|enum} {*Selectbox*}
                {if $po.variants}
                    {if ($po.disabled || $disabled) && !$po.not_required}
                        <input type="hidden"
                               value="{$po.value}"
                               name="{$name}[{$id}][product_options][{$po.option_id}]"
                               id="option_{$obj_prefix}{$id}_{$po.option_id}"
                        />
                    {/if}
                    <select name="{$name}[{$id}][product_options][{$po.option_id}]"
                            {if !$po.disabled && !$disabled}
                                id="option_{$obj_prefix}{$id}_{$po.option_id}"
                            {/if}
                            {if $product.options_update}
                                onchange="fn_change_options('{$obj_prefix}{$id}', '{$id}', '{$po.option_id}');"
                            {else}
                                onchange="fn_change_variant_image('{$obj_prefix}{$id}', '{$po.option_id}');"
                            {/if}
                            {if $product.exclude_from_calculate && !$product.aoc || $po.disabled || $disabled}
                                disabled="disabled"
                                class="disabled"
                            {/if}
                    >
                        {if $product.options_type == "ProductOptionsApplyOrder::SEQUENTIAL"|enum}
                            {if !$runtime.checkout || $po.disabled || $disabled || ($runtime.checkout && !$po.value)}
                                <option value="">
                                    {if $po.disabled || $disabled}
                                        {__("select_option_above")}
                                    {else}
                                        {__("please_select_one")}
                                    {/if}
                                </option>
                            {/if}
                        {elseif $product.options_type == "ProductOptionsApplyOrder::SIMULTANEOUS"|enum}
                            {if !$po.value}
                                <option value="">
                                    {if $po.disabled || $disabled}
                                        {__("select_option_above")}
                                    {else}
                                        {__("please_select_one")}
                                    {/if}
                                </option>
                            {/if}
                        {/if}
                        {foreach $po.variants as $vr}
                            {if !($po.disabled || $disabled) || (($po.disabled || $disabled) && $po.value && $po.value == $vr.variant_id)}
                                {capture name="modifier"}
                                    {include file="common/modifier.tpl"
                                            mod_type=$vr.modifier_type
                                            mod_value=$vr.modifier
                                            display_sign=true
                                    }
                                {/capture}
                                <option value="{$vr.variant_id}"
                                        {if $po.value == $vr.variant_id}
                                            {$selected_variant = $vr.variant_id}
                                            selected="selected"
                                        {/if}
                                >
                                    {$vr.variant_name}
                                    {if $show_modifiers}
                                        {hook name="products:options_modifiers"}
                                            {if $vr.modifier|floatval}
                                                ({$smarty.capture.modifier|strip_tags|strip:'' nofilter})
                                            {/if}
                                        {/hook}
                                    {/if}
                                </option>
                            {/if}
                        {/foreach}
                    </select>
                {else}
                    <input type="hidden"
                           name="{$name}[{$id}][product_options][{$po.option_id}]"
                           value="{$po.value}"
                           id="option_{$obj_prefix}{$id}_{$po.option_id}"
                    />
                    <span>{__("na")}</span>
                {/if}
            {elseif $po.option_type == "ProductOptionTypes::RADIO_GROUP"|enum} {*Radiobutton*}
                {if $po.variants}
                    <input type="hidden"
                           name="{$name}[{$id}][product_options][{$po.option_id}]"
                           value="{$po.value}"
                           id="option_{$obj_prefix}{$id}_{$po.option_id}"
                           {if $po.disabled && ($po.not_required || $po.required != "Y")}
                               disabled="disabled"
                           {/if}
                    />
                    <ul id="option_{$obj_prefix}{$id}_{$po.option_id}_group" class="ty-product-options__elem">
                        {if !$po.disabled && !$disabled}
                            {foreach $po.variants as $vr}
                                <li>
                                    <label id="option_description_{$id}_{$po.option_id}_{$vr.variant_id}"
                                           class="ty-product-options__box option-items"
                                    >
                                        <input type="radio"
                                               class="radio"
                                               name="{$name}[{$id}][product_options][{$po.option_id}]"
                                               value="{$vr.variant_id}"
                                               {if $po.value == $vr.variant_id }
                                                   {$selected_variant = $vr.variant_id}
                                                   checked="checked"
                                               {/if}
                                               {if $product.options_update}
                                                   onclick="fn_change_options('{$obj_prefix}{$id}', '{$id}', '{$po.option_id}');"
                                               {else}
                                                   onclick="fn_change_variant_image('{$obj_prefix}{$id}', '{$po.option_id}', '{$vr.variant_id}');"
                                               {/if}
                                               {if $product.exclude_from_calculate && !$product.aoc || $po.disabled || $disabled}
                                                   disabled="disabled"
                                               {/if}
                                        />
                                        {strip}
                                        {$vr.variant_name}&nbsp;
                                        {if  $show_modifiers}
                                            {hook name="products:options_modifiers"}
                                                {if $vr.modifier|floatval}
                                                    ({include file="common/modifier.tpl"
                                                            mod_type=$vr.modifier_type
                                                            mod_value=$vr.modifier display_sign=true
                                                    })
                                                {/if}
                                            {/hook}
                                        {/if}
                                        {/strip}
                                    </label>
                                </li>
                            {/foreach}
                        {elseif $po.value}
                            {$po.variants[$po.value].variant_name}
                        {/if}
                    </ul>
                    {if !$po.value && $product.options_type == "ProductOptionsApplyOrder::SEQUENTIAL"|enum && !($po.disabled || $disabled)}
                        <p class="ty-product-options__description ty-clear-both">
                            {__("please_select_one")}
                        </p>
                    {elseif !$po.value && $product.options_type == "ProductOptionsApplyOrder::SEQUENTIAL"|enum && ($po.disabled || $disabled)}
                        <p class="ty-product-options__description ty-clear-both">
                            {__("select_option_above")}
                        </p>
                    {/if}
                {else}
                    <input type="hidden"
                           name="{$name}[{$id}][product_options][{$po.option_id}]"
                           value="{$po.value}"
                           id="option_{$obj_prefix}{$id}_{$po.option_id}"
                    />
                    <span>{__("na")}</span>
                {/if}

            {elseif $po.option_type == "ProductOptionTypes::CHECKBOX"|enum} {*Checkbox*}
                {$default_variant_disabled = false}
                {foreach $po.variants as $vr}
                    {if $vr.position == 0}
                        {$default_variant_disabled = ($po.disabled || $vr.disabled) || ($disabled && $po.value != $vr.variant_id)}
                        <input id="unchecked_option_{$obj_prefix}{$id}_{$po.option_id}"
                               type="hidden"
                               name="{$name}[{$id}][product_options][{$po.option_id}]"
                               value="{$vr.variant_id}"
                               {if $default_variant_disabled}
                                   disabled="disabled"
                               {/if}
                        />
                    {else}
                        <label class="ty-product-options__box option-items">
                            <span class="cm-field-container">
                                <input id="option_{$obj_prefix}{$id}_{$po.option_id}"
                                       type="checkbox"
                                       name="{$name}[{$id}][product_options][{$po.option_id}]"
                                       value="{$vr.variant_id}"
                                       class="checkbox"
                                       {if $po.value == $vr.variant_id}
                                           checked="checked"
                                       {/if}
                                       {if $product.exclude_from_calculate && !$product.aoc || $vr.disabled || $default_variant_disabled || $disabled}
                                           disabled="disabled"
                                       {/if}
                                       {if $product.options_update}
                                           onclick="fn_change_options('{$obj_prefix}{$id}', '{$id}', '{$po.option_id}');"
                                       {else}
                                           onchange="fn_change_variant_image('{$obj_prefix}{$id}', '{$po.option_id}');"
                                       {/if}
                                />
                                {if $show_modifiers}
                                    {hook name="products:options_modifiers"}
                                        {if $vr.modifier|floatval}
                                            ({include file="common/modifier.tpl"
                                                    mod_type=$vr.modifier_type
                                                    mod_value=$vr.modifier
                                                    display_sign=true
                                            })
                                        {/if}
                                    {/hook}
                                {/if}
                            </span>
                        </label>

                        {if $default_variant_disabled}
                            <input id="checked_option_{$obj_prefix}{$id}_{$po.option_id}"
                                   type="hidden"
                                   name="{$name}[{$id}][product_options][{$po.option_id}]"
                                   value="{$vr.variant_id}"
                                   {if $po.disabled || $vr.disabled}
                                       disabled="disabled"
                                   {/if}
                            />
                        {/if}
                    {/if}
                {foreachelse}
                    <label class="ty-product-options__box option-items">
                        <input type="checkbox"
                               class="checkbox"
                               disabled="disabled"
                        />
                        {if $show_modifiers}
                            {hook name="products:options_modifiers"}
                                {if $vr.modifier|floatval}
                                    ({include file="common/modifier.tpl"
                                            mod_type=$vr.modifier_type
                                            mod_value=$vr.modifier
                                            display_sign=true
                                    })
                                {/if}
                            {/hook}
                        {/if}
                    </label>
                {/foreach}

            {elseif $po.option_type == "ProductOptionTypes::INPUT"|enum} {*Input*}
                <input id="option_{$obj_prefix}{$id}_{$po.option_id}"
                       type="text"
                       name="{$name}[{$id}][product_options][{$po.option_id}]"
                       value="{$po.value|default:$po.inner_hint}"
                       {if $product.exclude_from_calculate && !$product.aoc}
                           disabled="disabled"
                       {/if}
                       class="ty-valign ty-input-text {if $po.inner_hint}cm-hint{/if} {if $product.exclude_from_calculate && !$product.aoc}disabled{/if} {if $location == "cart"}cm-cart-contents-updatable-field{/if}"
                       {if $po.inner_hint}
                           title="{$po.inner_hint}"
                       {/if}
                />
            {elseif $po.option_type == "ProductOptionTypes::TEXT"|enum} {*Textarea*}
                <textarea id="option_{$obj_prefix}{$id}_{$po.option_id}"
                          class="ty-product-options__textarea {if $po.inner_hint}cm-hint{/if} {if $product.exclude_from_calculate && !$product.aoc}disabled{/if} {if $location == "cart"}cm-cart-contents-updatable-field{/if}"
                          rows="3"
                          name="{$name}[{$id}][product_options][{$po.option_id}]"
                          {if $product.exclude_from_calculate && !$product.aoc}
                              disabled="disabled"
                          {/if}
                          {if $po.inner_hint}
                              title="{$po.inner_hint}"
                          {/if}
                >{$po.value|default:$po.inner_hint}</textarea>
            {elseif $po.option_type == "ProductOptionTypes::FILE"|enum} {*File*}
                <div class="ty-product-options__elem ty-product-options__fileuploader">
                    {include file="common/fileuploader.tpl"
                            images=$product.extra.custom_files[$po.option_id]
                            var_name="`$name`[`$po.option_id``$id`]"
                            multiupload=$po.multiupload
                            hidden_name="`$name`[custom_files][`$po.option_id``$id`]"
                            hidden_value="`$id`_`$po.option_id`"
                            label_id="option_`$obj_prefix``$id`_`$po.option_id`"
                            prefix=$obj_prefix
                    }
                </div>
            {/if}

                {if $po.comment}
                    <div class="ty-product-options__description">{$po.comment}</div>
                {/if}
            {/if}

            {capture name="variant_images"}
                {if !$po.disabled && !$disabled}
                    {foreach $po.variants as $var}
                        {if $var.image_pair.image_id}
                            {if $var.variant_id == $selected_variant}
                                {$_class = "product-variant-image-selected"}
                            {else}
                                {$_class = "product-variant-image-unselected"}
                            {/if}
                            {include file="common/image.tpl"
                                    class="$_class ty-product-options__image"
                                    images=$var.image_pair
                                    image_width=$settings.Thumbnails.product_variant_mini_icon_width
                                    image_height=$settings.Thumbnails.product_variant_mini_icon_height
                                    obj_id="variant_image_`$obj_prefix``$id`_`$po.option_id`_`$var.variant_id`"
                                    image_onclick="fn_set_option_value('`$obj_prefix``$id`', '`$po.option_id`', '`$var.variant_id`'); void(0);"
                            }
                        {/if}
                    {/foreach}
                {/if}
            {/capture}
            {if $smarty.capture.variant_images|trim}
                <div class="ty-product-variant-image ty-clear-both">
                    {$smarty.capture.variant_images nofilter}
                </div>
            {/if}
        </div>
        {/foreach}
    </div>
</div>
{if $product.show_exception_warning == "Y"}
    <p id="warning_{$obj_prefix}{$id}" class="ty-product-options__no-combinations">{__("nocombination")}</p>
{/if}
{/if}

{if !$no_script}
<script>
(function(_, $) {
    $.ceEvent('on', 'ce.formpre_{$form_name|default:"product_form_`$obj_prefix``$id`"}', function(frm, elm) {
        if ($('#warning_{$obj_prefix}{$id}').length) {
            $.ceNotification('show', {
                type: 'W',
                title: _.tr('warning'),
                message: _.tr('cannot_buy')
            });

            return false;
        }

        return true;
    });
}(Tygh, Tygh.$));
</script>
{/if}
