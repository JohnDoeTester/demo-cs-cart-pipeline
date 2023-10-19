{if $option_data.option_id}
    {assign var="id" value=$option_data.option_id}
{else}
    {assign var="id" value=0}
{/if}

{if "ULTIMATE"|fn_allowed_for}
    {if !$runtime.company_id && $shared_product == "Y"}
        {assign var="show_update_for_all" value=true}
    {/if}

    {if $runtime.company_id && $shared_product == "Y"}
        {assign var="cm_no_hide_input" value="cm-no-hide-input"}
    {/if}
{/if}

{$action_context = $action_context|default:$smarty.request._action_context}
{$allow_save = $option_data|fn_allow_save_object:"product_options" && fn_check_permissions("product_options", "update", "admin", "POST")}
<div id="content_group_product_option_{$id}">

<form action="{""|fn_url}" 
    method="post" 
    name="option_form_{$id}" 
    class="{if $ajax_mode}cm-ajax {/if}form-horizontal form-edit form-highlight cm-disable-empty-files {if !$allow_save}cm-hide-inputs{/if}" 
    enctype="multipart/form-data"
    {if $action_context}data-ca-ajax-done-event="ce.{$action_context}.product_option_save"{/if}
>
<input type="hidden" name="option_id" value="{$id}" class="{$cm_no_hide_input}" />

{if "MULTIVENDOR"|fn_allowed_for}
    {if !$allow_save}
        {assign var="disable_company_picker" value=true}
    {/if}
{/if}

{if $smarty.request.product_id}
    <input class="cm-no-hide-input" type="hidden" name="option_data[product_id]" value="{$smarty.request.product_id}" />
    <input class="cm-no-hide-input" type="hidden" name="option_data[is_global]" value="1">
    {if "ULTIMATE"|fn_allowed_for}
        {assign var="disable_company_picker" value=true}
        {if !$company_id}
            {assign var="company_id" value=$product_company_id}
        {/if}
    {/if}
{/if}

<div class="tabs cm-j-tabs">
    <ul class="nav nav-tabs">
        <li id="tab_option_details_{$id}" class="cm-js active"><a>{__("general")}</a></li>
        {if $option_data.option_type == "ProductOptionTypes::SELECTBOX"|enum
            || $option_data.option_type == "ProductOptionTypes::RADIO_GROUP"|enum
            || $option_data.option_type == "ProductOptionTypes::CHECKBOX"|enum
            || !$option_data
            || !$option_data.option_type
        }
            <li id="tab_option_variants_{$id}" class="cm-js">
                <a data-ca-variants-text="{__("variants")}"
                    data-ca-modifiers-text="{__("modifiers")}"
                >
                    {if $option_data.option_type === "ProductOptionTypes::CHECKBOX"|enum}
                        {__("modifiers")}
                    {else}
                        {__("variants")}
                    {/if}
                </a>
            </li>
        {/if}
    </ul>
</div>
<div class="cm-tabs-content" id="tabs_content_{$id}">
    <div id="content_tab_option_details_{$id}">
    <fieldset>
        <input class="cm-no-hide-input" type="hidden" value="{$object}" name="object">
        {include file="components/copy_on_type.tpl"
            source_value=$option_data.internal_option_name
            source_name="option_data[internal_option_name]"
            target_value=$option_data.option_name
            target_name="option_data[option_name]"
            type="option_name"
        }

        <div class="control-group">
            <label class="control-label" for="elm_position_{$id}">{__("position")}</label>
            <div class="controls">
                <input type="text" name="option_data[position]" id="elm_position_{$id}" value="{$option_data.position}" size="3" class="input-small" />
            </div>
        </div>

        {if "MULTIVENDOR"|fn_allowed_for}
            {assign var="zero_company_id_name_lang_var" value="none"}
        {/if}
        {include file="views/companies/components/company_field.tpl"
            name="option_data[company_id]"
            id="elm_option_data_`$id`"
            disable_company_picker=$disable_company_picker
            selected=$option_data.company_id|default:$company_id
            zero_company_id_name_lang_var=$zero_company_id_name_lang_var
        }

        {if "ULTIMATE"|fn_allowed_for && $runtime.company_id && $shared_product == "Y"}
            <input type="hidden" name="option_data[option_type]" value="{$option_data.option_type}" class="cm-no-hide-input" />
        {/if}
        <div class="control-group">
            <label class="control-label" for="elm_option_type_{$id}">{__("type")}</label>
            <div class="controls">
            {include file="views/product_options/components/option_types.tpl"  name="option_data[option_type]" option_id=$id value=$option_data.option_type display="select" tag_id="elm_option_type_`$id`" check=true}
            </div>
        </div>

        <div class="control-group">
            <label class="control-label" for="elm_option_description_{$id}">{__("description")}</label>
            <div class="controls">
            <textarea id="elm_option_description_{$id}" name="option_data[description]" cols="55" rows="8" class="cm-wysiwyg input-textarea-long">{$option_data.description}</textarea>
            </div>
        </div>

        <div class="control-group">
            <label class="control-label" for="elm_option_comment_{$id}">{__("comment")}</label>
            <div class="controls">
            <input type="text" name="option_data[comment]" id="elm_option_comment_{$id}" value="{$option_data.comment}" class="input-large" />
            <p class="muted description">{__("comment_hint")}</p>
            </div>
        </div>

        <div class="control-group">
            <label class="control-label" for="elm_option_file_required_{$id}">{__("required")}</label>
            <div class="controls">
            <label class="checkbox">
            <input type="hidden" name="option_data[required]" value="N" /><input type="checkbox" id="elm_option_file_required_{$id}" name="option_data[required]" value="Y" {if $option_data.required == "Y"}checked="checked"{/if}  />
            </label>
            </div>
        </div>

        {if !$option_data.option_type || (
            $option_data.option_type == "ProductOptionTypes::SELECTBOX"|enum
            || $option_data.option_type == "ProductOptionTypes::RADIO_GROUP"|enum
            || $option_data.option_type == "ProductOptionTypes::CHECKBOX"|enum
        )}
            <div class="control-group">
                <label class="control-label" for="elm_option_missing_variants_{$id}">{__("missing_variants_handling")}</label>
                <div class="controls">
                    {if $option_data.option_type && (
                        $option_data.option_type == "ProductOptionTypes::SELECTBOX"|enum
                        || $option_data.option_type == "ProductOptionTypes::RADIO_GROUP"|enum
                        || $option_data.option_type == "ProductOptionTypes::CHECKBOX"|enum
                    )}
                    <select id="elm_option_missing_variants_{$id}" name="option_data[missing_variants_handling]">
                        <option value="M" {if $option_data.missing_variants_handling == "M"}selected="selected"{/if}>{__("display_message")}</option>
                        <option value="H" {if $option_data.missing_variants_handling == "H"}selected="selected"{/if}>{__("hide_option_completely")}</option>
                    </select>
                {else}
                <p>-</p>
                {/if}
                </div>
            </div>
        {/if}

        <div id="extra_options_{$id}" {if $option_data.option_type != "ProductOptionTypes::INPUT"|enum && $option_data.option_type != "ProductOptionTypes::TEXT"|enum}class="hidden"{/if}>
            <div class="control-group">
                <label class="control-label" for="elm_option_regexp_{$id}">{__("regexp")}</label>
                <div class="controls">
                    <input type="text" name="option_data[regexp]" id="elm_option_regexp_{$id}" value="{$option_data.regexp nofilter}" class="input-large" />
                    <p class="description">{__("regexp_hint")}</p>
                </div>
            </div>

            <div class="control-group">
                <label class="control-label" for="elm_option_inner_hint_{$id}">{__("inner_hint")}</label>
                <div class="controls">
                    <input type="text" name="option_data[inner_hint]" id="elm_option_inner_hint_{$id}" value="{$option_data.inner_hint}" class="input-large" />
                    <p class="muted description">{__("tt_views_product_options_update_inner_hint")}</p>
                </div>
            </div>

            <div class="control-group">
                <label class="control-label" for="elm_option_incorrect_message_{$id}">{__("incorrect_filling_message")}</label>
                <div class="controls">
                    <input type="text" name="option_data[incorrect_message]" id="elm_option_incorrect_message_{$id}" value="{$option_data.incorrect_message}" class="input-large" />
                    <p class="muted description">{__("tt_views_product_options_update_incorrect_filling_message")}</p>
                </div>
            </div>
        </div>

        <div id="file_options_{$id}" {if $option_data.option_type != "ProductOptionTypes::FILE"|enum}class="hidden"{/if}>
            <div class="control-group">
                <label class="control-label" for="elm_option_allowed_extensions_{$id}">{__("allowed_extensions")}</label>
                <div class="controls">
                <input type="text" name="option_data[allowed_extensions]" id="elm_option_allowed_extensions_{$id}" value="{$option_data.allowed_extensions}" class="input-large" />
                <p class="muted description">{__("allowed_extensions_hint")}</p>
                </div>
            </div>
            <div class="control-group">
                <label class="control-label" for="elm_option_max_uploading_file_size_{$id}">{__("max_uploading_file_size")}</label>
                <div class="controls">
                <input type="text" name="option_data[max_file_size]" id="elm_option_max_uploading_file_size_{$id}" value="{$option_data.max_file_size}" class="input-large" />
                <p class="muted description">{__("max_uploading_file_size_hint")}</p>
                </div>
            </div>
            <div class="control-group">
                <label class="control-label" for="elm_option_multiupload_{$id}">{__("multiupload")}</label>
                <div class="controls">
                <label class="checkbox">
                <input type="hidden" name="option_data[multiupload]" value="N" /><input type="checkbox" id="elm_option_multiupload_{$id}" name="option_data[multiupload]" value="Y" {if $option_data.multiupload == "Y"}checked="checked"{/if}/>
                </label>
                </div>
            </div>
        </div>

        {hook name="product_options:properties"}
        {/hook}
    </fieldset>
    <!--content_tab_option_details_{$id}--></div>

     <div class="hidden" data-ca-variants-list="container" id="content_tab_option_variants_{$id}">
     <fieldset>

        {if $id
            && !""|fn_check_form_permissions
            && $allow_save
            && $option_data.variants
            && $option_data.option_type !== "ProductOptionTypes::CHECKBOX"|enum
        }
            <div class="control-toolbar cm-toggle-button">
                <div class="control-toolbar__btns">
                    <div class="control-toolbar__btns-right">
                        {btn type="button"
                            text=__("add_variant")
                            icon_first=true
                            icon="icon-plus icon__open-rotate"
                            class="btn variants-list__btn-add"
                            data=[
                                "data-ca-variants-list" => "btnAdd",
                                "data-ca-variants-list-is-show-add-variants" => "false"
                            ]
                        }
                    </div>
                </div>
            </div>
        {/if}

        <div class="table-responsive-wrapper">
            <table class="table table-middle table--relative table-responsive">
            <thead>
            {strip}
            <tr class="first-sibling">
                <th class="cm-non-cb{if $option_data.option_type == "ProductOptionTypes::CHECKBOX"|enum} hidden{/if}">
                    {__("position_short")}
                </th>
                <th class="cm-non-cb{if $option_data.option_type == "ProductOptionTypes::CHECKBOX"|enum} hidden{/if}" {""}
                    width="60%"
                >
                    {__("name")}
                </th>
                <th>{__("modifier")}&nbsp;/&nbsp;{__("type")}</th>
                <th class="cm-non-cb{if $option_data.option_type == "ProductOptionTypes::CHECKBOX"|enum} hidden{/if}">
                    {__("status")}
                </th>
                <th>
                    <div class="btn-expand-wrapper">
                        <div id="on_st_{$id}" {""}
                            alt="{__("expand_collapse_list")}" {""}
                            title="{__("expand_collapse_list")}" {""}
                            class="hand cm-combinations-options-{$id} icon-caret-right btn-expand btn-expand--header"
                        >
                        </div>
                        <div id="off_st_{$id}" {""}
                            alt="{__("expand_collapse_list")}" {""}
                            title="{__("expand_collapse_list")}" {""}
                            class="hand hidden cm-combinations-options-{$id} icon-caret-down btn-expand btn-expand--header"
                        >
                        </div>
                    </div>
                </th>
                <th class="cm-non-cb{if $option_data.option_type == "ProductOptionTypes::CHECKBOX"|enum} hidden{/if}">
                    &nbsp;
                </th>
            </tr>
            {/strip}
            </thead>

            {if (
                    $id
                    && !""|fn_check_form_permissions
                    && $allow_save
                    && $option_data.option_type !== "ProductOptionTypes::CHECKBOX"|enum
                )
                || !$id
            }
                {include file="views/product_options/components/variants_add.tpl"
                    option_type=$option_data.option_type
                    id=$id
                    num=$option_data.variants|@count
                }
            {/if}

            {foreach from=$option_data.variants item="vr" name="fe_v"}
            {assign var="num" value=$smarty.foreach.fe_v.iteration}
            <tbody class="hover cm-row-item" id="option_variants_{$id}_{$num}">
            {strip}
            <tr>
                <td class="cm-non-cb{if $option_data.option_type == "ProductOptionTypes::CHECKBOX"|enum} hidden{/if}" {""}
                    data-th="{__("position_short")}"
                >
                    <input type="text" {""}
                        name="option_data[variants][{$num}][position]" {""}
                        value="{$vr.position}" {""}
                        size="3" {""}
                        class="input-micro"
                    />
                </td>
                <td class="cm-non-cb{if $option_data.option_type == "ProductOptionTypes::CHECKBOX"|enum} hidden{/if}" {""}
                    data-th="{__("name")}"
                >
                    <input type="text" {""}
                        name="option_data[variants][{$num}][variant_name]" {""}
                        value="{$vr.variant_name}" {""}
                        class="input-full"
                    />
                </td>
                <td class="nowrap {if $runtime.company_id && $shared_product == "Y"} cm-no-hide-input{/if}" {""}
                    data-th="{__("modifier")}&nbsp;/&nbsp;{__("type")}"
                >
                    <input type="text" {""}
                        name="option_data[variants][{$num}][modifier]" {""}
                        value="{$vr.modifier}" {""}
                        size="5" {""}
                        class="input-mini cm-numeric" data-a-sep
                    />
                    &nbsp;/&nbsp;
                    <select class="input-xsmall" name="option_data[variants][{$num}][modifier_type]">
                        <option value="A" {if $vr.modifier_type == "A"}selected="selected"{/if}>
                            {$currencies.$primary_currency.symbol nofilter}
                        </option>
                        <option value="P" {if $vr.modifier_type == "P"}selected="selected"{/if}>%</option>
                    </select>
                    {include file="buttons/update_for_all.tpl"
                        display=$show_update_for_all
                        object_id=$vr.variant_id
                        name="update_all_vendors[`$num`]"
                        component="product_options.`$num`"
                    }
                </td>
                <td class="cm-non-cb{if $option_data.option_type == "ProductOptionTypes::CHECKBOX"|enum} hidden{/if}" {""}
                    data-th="{__("status")}"
                >
                    {include file="common/select_status.tpl"
                        input_name="option_data[variants][`$num`][status]"
                        display="select"
                        obj=$vr meta="input-mini"
                    }
                </td>
                <td class="nowrap">
                    <span id="on_extra_option_variants_{$id}_{$num}" {""}
                        alt="{__("expand_collapse_list")}" {""}
                        title="{__("expand_collapse_list")}" {""}
                        class="btn btn-expand hand cm-combination-options-{$id}"
                    >
                        <span class="icon-caret-right"></span>
                    </span>
                    <span id="off_extra_option_variants_{$id}_{$num}" {""}
                        alt="{__("expand_collapse_list")}" {""}
                        title="{__("expand_collapse_list")}" {""}
                        class="btn btn-expand hand hidden cm-combination-options-{$id}"
                    >
                        <span class="icon-caret-down"></span>
                    </span>
                    <input type="hidden" {""}
                        name="option_data[variants][{$num}][variant_id]" {""}
                        value="{$vr.variant_id}" {""}
                        class="{$cm_no_hide_input}"
                    />
                </td>
                <td class="right cm-non-cb
                    {if $option_data.option_type == "ProductOptionTypes::CHECKBOX"|enum} hidden{/if}"
                >
                    {include file="buttons/multiple_buttons.tpl"
                        item_id="option_variants_`$id`_`$num`"
                        tag_level="3"
                        only_delete="Y"
                    }
                </td>
            </tr>
            {/strip}
            {strip}
            <tr id="extra_option_variants_{$id}_{$num}" class="cm-ex-op hidden">
                <td colspan="7">
                    {hook name="product_options:edit_product_options"}
                    <div class="control-group cm-non-cb
                        {if $option_data.option_type == "ProductOptionTypes::CHECKBOX"|enum} hidden{/if}"
                    >
                        <label class="control-label">{__("icon")}</label>
                        <div class="controls">
                            {include file="common/attach_images.tpl"
                                image_name="variant_image"
                                image_key=$num
                                hide_titles=true
                                no_detailed=true
                                image_object_type="variant_image"
                                image_type="V"
                                image_pair=$vr.image_pair
                                prefix=$id
                            }
                        </div>
                    </div>

                    <div class="control-group">
                        <label class="control-label">{__("weight_modifier")}&nbsp;/&nbsp;{__("type")}:</label>
                        <div class="controls flex-vertical-centered--on-mobile">
                            <input type="text" {""}
                                name="option_data[variants][{$num}][weight_modifier]" {""}
                                value="{$vr.weight_modifier}" {""}
                                size="5" {""}
                                class="input-mini"
                            />
                            &nbsp;/&nbsp;
                            <select name="option_data[variants][{$num}][weight_modifier_type]">
                                <option value="A" {if $vr.weight_modifier_type == "A"}selected="selected"{/if}>
                                    {$settings.General.weight_symbol}
                                </option>
                                <option value="P" {if $vr.weight_modifier_type == "P"}selected="selected"{/if}>%</option>
                            </select>
                        </div>
                    </div>
                    {/hook}

                </td>
            </tr>
            {/strip}
            </tbody>
            {/foreach}

            </table>
        </div>
     </fieldset>
     <!--content_tab_option_variants_{$id}--></div>
</div>

<div class="buttons-container">
    {if $id}
        {if !$allow_save && $shared_product != "Y"}
            {assign var="hide_first_button" value=true}
        {/if}
    {/if}
    {include file="buttons/save_cancel.tpl" but_name="dispatch[product_options.update]" cancel_action="close" extra="" hide_first_button=$hide_first_button save=$id}
</div>

</form>

<!--content_group_product_option_{$id}--></div>
