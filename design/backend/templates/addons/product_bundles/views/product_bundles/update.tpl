{if $item.bundle_id}
    {$id = $item.bundle_id}
{else}
    {$id = ""}
    {$extra_mode = "product_bundles"}
{/if}

{script src="js/addons/product_bundles/backend/func.js"}

{$allow_save = fn_check_view_permissions("product_bundles.update", "POST")}
{$product_id = $product_id|default: 0}
{if empty($product_options)}
    {$aoc = "YesNo::NO"|enum}
{else}
    {$aoc = "YesNo::YES"|enum}
{/if}
{$item_product = ($product_data) ? ([["product_id" => $product_id, "product" => "`$product_data.product`", "price" => {$product_data.price}, "amount" => "1", "aoc" => "{$aoc}"]]) : ([])}
{$bundle_products = $item.products|default:$item_product}
{$return_url = $return_url|default:$config.current_url}

<div class="content-product-bundle" id="content_group_product_bundle_{$id}">
    <form action="{""|fn_url}" method="post" name="item_update_form_product_bundle" id="item_update_form_product_bundle_{$id}" class="form-horizontal form-edit {if !$allow_save}cm-hide-inputs{/if}" enctype="multipart/form-data">
        <input type="hidden" class="cm-no-hide-input" name="fake" value="1" />
        <input type="hidden" class="cm-no-hide-input" name="item_id" value="{$id}" />
        <input type="hidden" class="cm-no-hide-input" name="product_id" value="{$product_id}" />
        <input type="hidden" class="cm-no-hide-input" name="return_url" value="{$return_url}">

        <div class="tabs cm-j-tabs">
            <ul class="nav nav-tabs">
                <li id="tab_general_{$id}" class="cm-js active"><a>{__("general")}</a></li>
                <li id="tab_products_{$id}" class="cm-js"><a>{__("products")}</a></li>
            </ul>
        </div>

        <div class="cm-tabs-content" id="tabs_content_{$id}">
            <fieldset>
                <div id="content_tab_general_{$id}">
                    {include file="components/copy_on_type.tpl"
                        source_value=$item.name
                        source_name="item_data[name]"
                        target_value=$item.storefront_name
                        target_name="item_data[storefront_name]"
                        type="bundle_name"
                    }

                    {include file="views/companies/components/company_field.tpl"
                        name="item_data[company_id]"
                        id="product_bundle_company_id_{$id}"
                        selected=$item.company_id
                        tooltip=$companies_tooltip
                        disable_company_picker=$id
                    }

                    <div class="control-group">
                        <label class="control-label" for="elm_product_bundle_description_{$id}">{__("description")}:</label>
                        <div class="controls">
                            <textarea id="elm_product_bundle_description_{$id}" name="item_data[description]" cols="55" rows="8" class="cm-wysiwyg input-textarea-long">{$item.description}</textarea>
                        </div>
                    </div>
                    {if fn_check_permissions("promotions", "manage", "admin", "POST")}
                        <div class="control-group">
                            <label class="control-label" for="elm_product_bundle_promotions_{$id}">{__("product_bundles.display_in_promotions")}:</label>
                            <div class="controls">
                                <input type="hidden" name="item_data[display_in_promotions]" value="{"YesNo::NO"|enum}">
                                <input type="checkbox" name="item_data[display_in_promotions]" id="elm_product_bundle_promotions_{$id}" value="{"YesNO::YES"|enum}" {if $item.display_in_promotions == "{"YesNO::YES"|enum}"}checked="checked"{/if}>
                            </div>
                        </div>
                    {/if}
                    <div class="control-group">
                        <label class="control-label">{__("product_bundles.promo_image")}</label>
                        <div class="controls">
                            {include file="common/attach_images.tpl"
                                image_name="bundle_main"
                                image_object_type="product_bundle"
                                image_pair=$item.main_pair
                                image_object_id=$id
                                image_key=$id
                                no_detailed=true
                                hide_titles=true
                            }
                        </div>
                    </div>
                    {if !$item.date_from && !$item.date_to}
                        {$date_disabled = 'disabled="disabled"'}
                    {else}
                        {$date_disabled = false}
                    {/if}
                    <div class="control-group">
                        <label class="control-label" for="elm_use_avail_period">{__("use_avail_period")}:</label>
                        <div class="controls">
                            <input type="checkbox" name="avail_period" class="use_avail_period" data-id="{$id}"{if !$date_disabled} checked="checked"{/if} value="Y"/>
                        </div>
                    </div>

                    <div class="control-group">
                        <label class="control-label" for="elm_product_bundle_avail_from_{$id}">{__("avail_from")}:</label>
                        <div class="controls">
                            <input type="hidden" name="item_data[date_from]" value="0" />
                            {include file="common/calendar.tpl" date_id="elm_product_bundle_avail_from_`$id`" date_name="item_data[date_from]" date_val=$item.date_from|default:$smarty.const.TIME start_year=$settings.Company.company_start_year extra=$date_disabled}
                        </div>
                    </div>

                    <div class="control-group">
                        <label class="control-label" for="elm_product_bundle_avail_till_{$id}">{__("avail_till")}:</label>
                        <div class="controls">
                            <input type="hidden" name="item_data[date_to]" value="0" />
                            {include file="common/calendar.tpl" date_id="elm_product_bundle_avail_till_`$id`" date_name="item_data[date_to]" date_val=$item.date_to|default:$smarty.const.TIME start_year=$settings.Company.company_start_year extra=$date_disabled}
                        </div>
                    </div>

                    {include file="common/select_status.tpl" input_name="item_data[status]" id="elm_product_bundle_status_`$id`" obj=$item hidden=false}
                </div>

                <div id="content_tab_products_{$id}" {if !$allow_save}class="cm-hide-inputs"{/if}>
                {if fn_check_permissions('products', 'get_products_list', 'admin')}
                    {include file="common/subheader.tpl" title=__("product_bundles.bundle_products")}

                    {include file="views/products/components/picker/picker.tpl"
                        multiple=true
                        select_group_class="btn-toolbar"
                        display="options"
                        advanced_picker_id="add_new_bundles_`$id`_"
                        select_class="cm-object-product-add--product-bundles"
                        aoc=true
                        additional_query_params="product_type=P&aoc={'YesNo::YES'|enum}&any_variation={'YesNo::YES'|enum}{if $exclude_pid}&exclude_pid={$exclude_pid}{/if}"
                        segment="product_bundles"
                    }
                {/if}

                    {include file="pickers/products/picker.tpl"
                        picker_id="add_new_bundles_`$id`_"
                        input_name="item_data[products]"
                        item_ids=$bundle_products
                        get_option_info=false
                        view_mode="list"
                        type="table"
                        table_meta="product-bundles-table"
                        colspan="8"
                    }

                    <ul class="pull-right unstyled right span6">
                        <li>
                            <a class="btn" onclick="fn_product_bundles_recalculate('{$id}');">{__("recalculate")}</a><br><br>
                        </li>
                        <li>
                            <input id="elm_product_bundle_total_price_{$id}" type="hidden" name="item_data[total_price]" value="{$item.total_price}" />
                            <em>{__("product_bundles.total_cost")}:</em>
                            <strong>{include file="common/price.tpl" value=$item.total_price span_id="total_price_`$id`"}</strong>
                        </li>
                        <li>
                            <input id="elm_product_bundle_price_for_all_{$id}"  type="hidden" name="item_data[price_for_all]" value="{$item.discounted_price}" />
                            <em>{__("product_bundles.price_for_all")}:</em>
                            <strong>{include file="common/price.tpl" value=$item.discounted_price span_id="price_for_all_`$id`"}</strong>
                        </li>
                        <li><br>
                            <label for="elm_product_bundle_global_discount_{$id}">
                                <em>{__("product_bundles.share_discount")}&nbsp;({$currencies.$primary_currency.symbol nofilter}):</em>&nbsp;
                                <input type="text" class="input-mini" size="4"
                                       id="elm_product_bundle_global_discount_{$id}"
                                       onkeypress="fn_product_bundles_share_discount(event, '{$id}');" />&nbsp;
                                <a onclick="fn_product_bundles_apply_discount('{$id}');" class="btn">{__("apply")}</a>
                            </label>
                        </li>
                    </ul>
                </div>
            </fieldset>
        </div>

        <div class="buttons-container">
            {if !$id}
                {include file="buttons/save_cancel.tpl"
                    but_name="dispatch[product_bundles.update]"
                    cancel_action="close"
                    but_confirm_text=["emptyProductBundle" => {__("product_bundles.confirm_text_on_empty_bundle")}, "withOneProductBundle" => {__("product_bundles.confirm_text_with_one_product_bundle")}]|to_json
                }
            {else}
                {include file="buttons/save_cancel.tpl"
                    but_name="dispatch[product_bundles.update]"
                    but_confirm_text=["emptyProductBundle" => {__("product_bundles.confirm_text_on_empty_bundle")}, "withOneProductBundle" => {__("product_bundles.confirm_text_with_one_product_bundle")}]|to_json
                    cancel_action="close"
                    hide_first_button=false
                    hide_second_button=false
                    save=$id
                }
            {/if}
        </div>

    </form>

    <!--content_group_product_bundle_{$id}--></div>