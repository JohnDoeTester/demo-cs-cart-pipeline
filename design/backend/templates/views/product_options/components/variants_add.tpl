{script src="js/tygh/backend/variants_add.js"}

{$highlight_variants_add = $highlight_variants_add|default:(!!$id && ($num !== 0))}
{$show_variants_add = $show_variants_add|default:(!$id || ($num === 0))}
{$vr = ""}
{math equation="x + 1" assign="num" x=$num|default:0}

<tbody class="hover cm-row-item
        {if $highlight_variants_add}well{/if}
        {if $option_type === "ProductOptionTypes::CHECKBOX"|enum || !$show_variants_add} hidden{/if}"
    data-ca-variants-list="containerAdd"
    id="box_add_variant_{$id}"
>
    {strip}
    <tr>
        <td class="cm-non-cb{if $option_type === "ProductOptionTypes::CHECKBOX"|enum} hidden{/if}"
            data-th="{__("position")}"
        >
            <input type="text"
                name="option_data[variants][{$num}][position]"
                value=""
                size="3"
                class="input-micro"
            />
        </td>
        <td class="cm-non-cb{if $option_type === "ProductOptionTypes::CHECKBOX"|enum} hidden{/if}"
            data-th="{__("name")}"
        >
            <input type="text"
                name="option_data[variants][{$num}][variant_name]"
                value=""
                placeholder="{__("type_to_create")}"
                class="input-full"
            />
        </td>
        <td class="nowrap" data-th="{__("modifier")}&nbsp;/&nbsp;{__("type")}">
            <input type="text"
                name="option_data[variants][{$num}][modifier]"
                value=""
                size="5"
                class="input-mini cm-numeric" data-a-sep
            />
            &nbsp;/&nbsp;
            <select class="input-xsmall" name="option_data[variants][{$num}][modifier_type]">
                <option value="A">{$currencies.$primary_currency.symbol nofilter}</option>
                <option value="P">%</option>
            </select>
        </td>
        <td class="cm-non-cb{if $option_type === "ProductOptionTypes::CHECKBOX"|enum} hidden{/if}"
            data-th="{__("status")}"
        >{include file="common/select_status.tpl"
                input_name="option_data[variants][`$num`][status]"
                display="select"
                meta="input-mini"
            }</td>
        <td class="nowrap">
            <span id="on_extra_option_variants_{$id}_{$num}"
                alt="{__("expand_collapse_list")}"
                title="{__("expand_collapse_list")}"
                class="btn btn-expand hand cm-combination-options-{$id}"
            >
                <span class="icon-caret-right"></span>
            </span>
            <span id="off_extra_option_variants_{$id}_{$num}"
                alt="{__("expand_collapse_list")}"
                title="{__("expand_collapse_list")}"
                class="btn btn-expand hand hidden cm-combination-options-{$id}"
            >
                <span class="icon-caret-down"></span>
            </span>
        </td>
        <td class="right cm-non-cb
            {if $option_type === "ProductOptionTypes::CHECKBOX"|enum} hidden{/if}"
        >
            {include file="buttons/multiple_buttons.tpl" item_id="add_variant_`$id`" tag_level="2"}
        </td>
    </tr>
    {/strip}
    {strip}
    <tr id="extra_option_variants_{$id}_{$num}" class="cm-ex-op hidden">
        <td colspan="7" data-th="{__("extra")}">
            {hook name="product_options:edit_product_options"}
            <div class="control-group cm-non-cb">
                <label class="control-label">{__("icon")}</label>
                <div class="controls">
                    {include file="common/attach_images.tpl"
                        image_name="variant_image"
                        image_key=$num
                        hide_titles=true
                        no_detailed=true
                        image_object_type="variant_image"
                        image_type="V"
                        prefix=$id
                    }
                </div>
            </div>

            <div class="control-group">
                <label class="control-label">{__("weight_modifier")}&nbsp;/&nbsp;{__("type")}:</label>
                <div class="controls flex-vertical-centered--on-mobile">
                    <input type="text" {""}
                        name="option_data[variants][{$num}][weight_modifier]" {""}
                        value="" {""}
                        size="5" {""}
                        class="input-mini"
                    />
                    &nbsp;/&nbsp;
                    <select name="option_data[variants][{$num}][weight_modifier_type]">
                        <option value="A">{$settings.General.weight_symbol}</option>
                        <option value="P">%</option>
                    </select>
                </div>
            </div>
            {/hook}
        </td>
    </tr>
    {/strip}
</tbody>
