{if $order_info.shipping}
    {foreach from=$order_info.shipping item="shipping" key="shipping_id" name="f_shipp"}
        {if "shipments.add"|fn_check_view_permissions}
            {capture name="add_new_picker"}
                {include file="views/shipments/components/new_shipment.tpl" group_key=$shipping.group_key current_shipping_id=$shipping.shipping_id}
            {/capture}
            {include file="common/popupbox.tpl" id="add_shipment_`$shipping.group_key`" content=$smarty.capture.add_new_picker text=__("new_shipment") act="hidden"}
        {/if}
    {/foreach}
{else}
    {foreach from=$order_info.product_groups item="group" key="group_id"}
        {if $group.all_free_shipping}
            {if "shipments.add"|fn_check_view_permissions}
                {capture name="add_new_picker"}
                    {include file="views/shipments/components/new_shipment.tpl" group_key=0}
                {/capture}
                {include file="common/popupbox.tpl" id="add_shipment_0" content=$smarty.capture.add_new_picker text=__("new_shipment") act="hidden"}
            {/if}
        {/if}
    {/foreach}
{/if}

{capture name="mainbox"}
{capture name="tabsbox"}

<form action="{""|fn_url}" method="post" name="order_info_form" id="order_info_form" class="form-horizontal form-edit order-info-form">
<input type="hidden" name="order_id" value="{$smarty.request.order_id}" />
<input type="hidden" name="order_status" value="{$order_info.status}" />
<input type="hidden" name="result_ids" value="content_general" />
<input type="hidden" name="selected_section" value="{$selected_section}" />

<div class="{if $selected_section !== "general"}hidden{/if}" id="content_general">
    <div class="row-fluid">
        <div class="span8">
            {* Products info *}
            <div class="table-responsive-wrapper">
                <table width="100%" class="table table-middle table--relative table-responsive table--order-details-products">
                <thead>
                    {hook name="orders:items_list_head"}
                        <tr>
                            <th width="50%">{__("product")}</th>
                            <th width="10%">{__("price")}</th>
                            <th class="center" width="10%">{__("quantity")}</th>
                            {if $order_info.use_discount}
                            <th width="5%">{__("discount")}</th>
                            {/if}
                            {if $order_info.taxes && $settings.Checkout.tax_calculation != "subtotal"}
                            <th width="10%">&nbsp;{__("tax")}</th>
                            {/if}
                            <th width="10%" class="right">&nbsp;{__("subtotal")}</th>
                        </tr>
                    {/hook}
                </thead>
                {foreach from=$order_info.products item="oi" key="key"}
                {hook name="orders:items_list_row"}
                {if !$oi.extra.parent}
                <tr>
                    <td data-th="{__("product")}">
                        <div class="order-product-image">
                            {include file="common/image.tpl" image=$oi.main_pair.icon|default:$oi.main_pair.detailed image_id=$oi.main_pair.image_id image_width=$settings.Thumbnails.product_admin_mini_icon_width image_height=$settings.Thumbnails.product_admin_mini_icon_height href="products.update?product_id=`$oi.product_id`"|fn_url}
                        </div>
                        <div class="order-product-info">
                            {if !$oi.deleted_product}<a href="{"products.update?product_id=`$oi.product_id`"|fn_url}">{/if}{$oi.product nofilter}{if !$oi.deleted_product}</a>{/if}
                            <div class="products-hint">
                            {hook name="orders:product_info"}
                                {if $oi.product_code}<p class="products-hint__code">{__("sku")}:{$oi.product_code}</p>{/if}
                            {/hook}
                            </div>
                            {if $oi.product_options}<div class="options-info">{include file="common/options_info.tpl" product_options=$oi.product_options}</div>{/if}
                        </div>
                    </td>
                    <td class="nowrap" data-th="{__("price")}">
                        {if $oi.extra.exclude_from_calculate}{__("free")}{else}{include file="common/price.tpl" value=$oi.original_price}{/if}</td>
                    {hook name="orders:product_quantity"}
                        <td class="center" data-th="{__("quantity")}">
                            {$oi.amount}<br />
                            {if $oi.shipped_amount > 0}
                                <span class="muted"><small>({$oi.shipped_amount}&nbsp;{__("shipped")})</small></span>
                            {/if}
                        </td>
                    {/hook}
                    {if $order_info.use_discount}
                    <td class="nowrap" data-th="{__("discount")}">
                        {if $oi.extra.discount|floatval}{include file="common/price.tpl" value=$oi.extra.discount}{else}-{/if}</td>
                    {/if}
                    {if $order_info.taxes && $settings.Checkout.tax_calculation != "subtotal"}
                    <td class="nowrap" data-th="{__("tax")}">
                        {if $oi.tax_value|floatval}{include file="common/price.tpl" value=$oi.tax_value}{else}-{/if}</td>
                    {/if}
                    <td class="right" data-th="{__("subtotal")}"><span>{if $oi.extra.exclude_from_calculate}{__("free")}{else}{include file="common/price.tpl" value=$oi.display_subtotal}{/if}</span></td>
                </tr>
                {/if}
                {/hook}
                {/foreach}
                {hook name="orders:extra_list"}
                {/hook}
                </table>
            </div>

            <!--{***** Customer note, Staff note & Statistics *****}-->
            {hook name="orders:totals"}
            <div class="order-notes statistic">

            <div class="clearfix">
                <div class="table-wrapper">
                    <table class="pull-right">
                        <tr class="totals">
                            <td class="totals-label">&nbsp;</td>
                            <td class="totals" width="100px"><h4>{__("totals")}</h4></td>
                        </tr>

                        <tr>
                            <td class="statistic-label">{__("subtotal")}:</td>
                            <td class="right" data-ct-totals="subtotal">{include file="common/price.tpl" value=$order_info.display_subtotal}</td>
                        </tr>

                        {if $order_info.display_shipping_cost|floatval}
                            <tr>
                                <td class="statistic-label">{__("shipping_cost")}:</td>
                                <td class="right" data-ct-totals="shipping_cost">{include file="common/price.tpl" value=$order_info.display_shipping_cost}</td>
                            </tr>
                        {/if}

                        {if $order_info.discount|floatval}
                            <tr>
                                <td class="statistic-label">{__("including_discount")}:</td>
                                <td class="right" data-ct-totals="including_discount">{include file="common/price.tpl" value=$order_info.discount}</td>
                            </tr>
                        {/if}

                        {if $order_info.subtotal_discount|floatval}
                            <tr>
                                <td class="statistic-label">{__("order_discount")}:</td>
                                <td class="right" data-ct-totals="order_discount">{include file="common/price.tpl" value=$order_info.subtotal_discount}</td>
                            </tr>
                        {/if}

                        {if $order_info.coupons}
                            {foreach from=$order_info.coupons key="coupon" item="_c"}
                                <tr>
                                    <td class="statistic-label">{__("discount_coupon")}:</td>
                                    <td class="right" data-ct-totals="discount_coupon">{$coupon}</td>
                                </tr>
                            {/foreach}
                        {/if}

                        {if $order_info.taxes}
                            <tr>
                                <td class="statistic-label">{__("taxes")}:</td>
                                <td class="right"></td>
                            </tr>

                            {foreach from=$order_info.taxes item="tax_data"}
                                <tr>
                                    <td class="statistic-label">&nbsp;<span>&middot;</span>&nbsp;{$tax_data.description}&nbsp;{include file="common/modifier.tpl" mod_value=$tax_data.rate_value mod_type=$tax_data.rate_type}{if $tax_data.price_includes_tax == "Y" && ($settings.Appearance.cart_prices_w_taxes != "Y" || $settings.Checkout.tax_calculation == "subtotal")}&nbsp;{__("included")}{/if}{if $tax_data.regnumber}&nbsp;({$tax_data.regnumber}){/if}</td>
                                    <td class="right" data-ct-totals="taxes-{$tax_data.description}">{include file="common/price.tpl" value=$tax_data.tax_subtotal}</td>
                                </tr>
                            {/foreach}
                        {/if}

                        {if $order_info.tax_exempt == "Y"}
                            <tr>
                                <td class="statistic-label">{__("tax_exempt")}</td>
                                <td class="right">&nbsp;</td>
                            </tr>
                        {/if}

                        {if $order_info.payment_surcharge|floatval && !$take_surcharge_from_vendor}
                            <tr>
                                <td class="statistic-label">{$order_info.payment_method.surcharge_title|default:__("payment_surcharge")}:</td>
                                <td class="right" data-ct-totals="payment_surcharge">{include file="common/price.tpl" value=$order_info.payment_surcharge}</td>
                            </tr>
                        {/if}

                        {hook name="orders:totals_content"}
                        {/hook}
                        <tr>
                            <td class="statistic-label"><h4>{__("total")}:</h4></td>
                            <td class="price right" data-ct-totals="total">{include file="common/price.tpl" value=$order_info.total}</td>
                        </tr>
                    </table>
                </div>
            </div>

            <div class="note clearfix">
                <div class="span6">
                    <label for="notes">{__("customer_notes")}</label>
                    <textarea class="span12" name="update_order[notes]" id="notes" cols="40" rows="5">{$order_info.notes}</textarea>
                </div>
                <div class="span6">
                    <label for="details">{__("staff_only_notes")}</label>
                    <textarea class="span12" name="update_order[details]" id="details" cols="40" rows="5">{$order_info.details}</textarea>
                </div>
            </div>

            </div>
            {/hook}

            <!--{***** /Customer note, Staff note & Statistics *****}-->

    {hook name="orders:staff_only_note"}
    {/hook}

        </div>
        <div class="span4">
            <div class="well orders-right-pane form-horizontal">
                <div class="control-group">
                    <div class="control-label"><h4 class="subheader">{__("status")}</h4></div>
                    <div class="controls">
                        {hook name="orders:order_status"}
                            {$get_additional_statuses=true}
                            {$order_status_descr=$smarty.const.STATUSES_ORDER|fn_get_simple_statuses:$get_additional_statuses:true}
                            {$extra_status=$config.current_url|escape:"url"}
                            {if "MULTIVENDOR"|fn_allowed_for}
                                {$notify_vendor=true}
                            {else}
                                {$notify_vendor=false}
                            {/if}

                            {$statuses = []}
                            {$order_statuses=$smarty.const.STATUSES_ORDER|fn_get_statuses:$statuses:$get_additional_statuses:true}
                            {include file="common/select_popup.tpl" suffix="o" id=$order_info.order_id status=$order_info.status items_status=$order_status_descr update_controller="orders" notify=true notify_department=true notify_vendor=$notify_vendor status_target_id="content_downloads" extra="&return_url=`$extra_status`" statuses=$order_statuses popup_additional_class="dropleft" text_wrap=true}
                        {/hook}
                    </div>
                </div>

                <div class="control-group shift-top">
                    {include file="common/subheader.tpl" title=__("payment_information")}
                </div>
                {hook name="orders:payment_info"}
                {* Payment info *}
                {if $order_info.payment_id}
                    <div class="control-group">
                        <div class="control-label">{__("method")}</div>
                        <div id="tygh_payment_info" class="controls">{$order_info.payment_method.payment}&nbsp;{if $order_info.payment_method.description}({$order_info.payment_method.description}){/if}
                        </div>
                    </div>

                    {if $order_info.payment_info}
                        {foreach from=$order_info.payment_info item=item key=key}
                        <div class="control-group">
                            {if $item && $key != "expiry_year"}
                                <div class="control-label">
                                {if $key == "card_number"}{assign var="cc_exists" value=true}{__("credit_card")}{elseif $key == "expiry_month"}{__("expiry_date")}{else}{__($key)}{/if}
                                </div>
                                <div class="controls">
                                    {if $key == "order_status"}
                                        {include file="common/status.tpl" status=$item display="view" status_type=""}
                                    {elseif $key == "reason_text"}
                                        {$item|nl2br}
                                    {elseif $key == "expiry_month"}
                                        {$item}/{$order_info.payment_info.expiry_year}
                                    {elseif $key == "card_number" || $key == "cvv" || $key == "cvv2"}
                                        <div class="wrap">{$item}</div>
                                    {else}
                                        {hook name="orders:payment_info_text_item"}
                                            <bdi>{$item}</bdi>
                                        {/hook}
                                    {/if}
                                </div>
                            {/if}
                        </div>
                        {/foreach}

                        {if $cc_exists}
                        <div class="control-group">
                            <div class="control-label">
                                <input type="hidden" name="order_ids[]" value="{$order_info.order_id}" />
                                {include file="buttons/button.tpl" but_text=__("remove_cc_info") but_meta="cm-ajax cm-comet" but_name="dispatch[orders.remove_cc_info]"}
                            </div>
                        </div>
                        {/if}
                    {/if}
                   {/if}
                {/hook}

                {include file="common/subheader.tpl" title=__("manager")}
                <div class="control-group shift-top" id="select_manager">
                    <div class="control">
                        {if "MULTIVENDOR"|fn_allowed_for}
                            {$extra_url = "&user_type=V"}
                        {else}
                            {$extra_url = "&user_type=A"}
                        {/if}

                        <div class="order-manager">
                            {include file="views/profiles/components/picker/picker.tpl"
                                input_name="update_order[issuer_id]"
                                item_ids=[$order_info.issuer_data.user_id]
                                company_id=$order_info.company_id
                            }
                            {if $auth.user_id != $order_info.issuer_id}
                                {btn type="text" title=__("assign_to_me") href="orders.assign_manager?order_id=`$order_info.order_id`" class="btn cm-ajax cm-post order-manager-assign-btn" data=["data-ca-target-id"=>"select_manager"] icon="icon-user"}
                            {/if}
                        </div>
                    </div>
                <!--select_manager--></div>

                {* Shipping info *}
                {hook name="orders:shipping_info"}
                    {if $order_info.shipping}
                        <div class="control-group shift-top">
                            {include file="common/subheader.tpl" title=__("shipping_information")}
                        </div>
                        {assign var="is_group_shippings" value=count($order_info.shipping)>1}

                        {foreach from=$order_info.shipping item="shipping" key="shipping_id" name="f_shipp"}

                            <div class="control-group" >
                                <span> {$shipping.group_name|default:__("none")}</span>
                            </div>

                            <div class="control-group">
                                <div class="control-label">{__("method")}</div>
                                <div id="tygh_shipping_info" class="controls">
                                    {$shipping.shipping}
                                </div>
                            </div>

                            {if $shipping.shipment_keys}
                                {* show created shipments *}
                                <p>
                                    <strong>{__("track_on_carrier_site")}</strong>
                                </p>
                                {foreach from=$shipping.shipment_keys item="shipment_key"}
                                    {$shipment = $shipments[$shipment_key]}

                                    {hook name="orders:data_shipping"}
                                    <div class="control-group">
                                        <div class="control-label">
                                            {if $shipment.carrier_info}
                                                {$shipment.carrier_info.name}
                                            {else}
                                                {__("tracking_number")}
                                            {/if}
                                        </div>
                                        <div class="controls">
                                            <a class="hand cm-tooltip icon-edit cm-combination tracking-number-edit-link" title="{__("edit")}" id="sw_tracking_number_{$shipment_key}"></a>
                                            {if $shipment.carrier_info.tracking_url}
                                                <a href="{$shipment.carrier_info.tracking_url nofilter}" target="_blank" id="on_tracking_number_{$shipment_key}">{if $shipment.tracking_number}{$shipment.tracking_number}{else}&mdash;{/if}</a>
                                            {else}
                                                <span id="on_tracking_number_{$shipment_key}">{$shipment.tracking_number}</span>
                                            {/if}
                                            <div class="hidden" id="tracking_number_{$shipment_key}">
                                                <input class="input-small" type="text" name="update_shipping[{$shipping.group_key}][{$shipment.shipment_id}][tracking_number]" size="45" value="{$shipment.tracking_number}" />
                                                <input type="hidden" name="update_shipping[{$shipping.group_key}][{$shipment.shipment_id}][shipping_id]" value="{$shipping.shipping_id}" />
                                                <input type="hidden" name="update_shipping[{$shipping.group_key}][{$shipment.shipment_id}][carrier]" value="{$shipment.carrier}" />
                                            </div>
                                        </div>
                                    </div>
                                    {/hook}
                                {/foreach}

                            {else}
                                {* show form for creating new full shipment *}
                                {$shipment_id = 0}
                                {$carrier = ""}
                                {hook name="orders:new_shipment"}
                                <div class="control-group">
                                    <label class="control-label" for="tracking_number">{__("tracking_number")}</label>
                                    <div class="controls">
                                        <input id="tracking_number" class="input-full" type="text" name="update_shipping[{$shipping.group_key}][{$shipment_id}][tracking_number]" size="45" value="" />
                                        <input type="hidden" name="update_shipping[{$shipping.group_key}][{$shipment_id}][shipping_id]" value="{$shipping.shipping_id}" />
                                    </div>
                                </div>
                                <div class="control-group">
                                    <label class="control-label" for="carrier_key">{__("carrier")}</label>
                                    <div class="controls">
                                        {include file="common/carriers.tpl" id="carrier_key" meta="input-full" name="update_shipping[`$shipping.group_key`][`$shipment_id`][carrier]" carrier=$carrier}
                                    </div>
                                </div>
                                {/hook}
                                <hr>
                            {/if}
                            <div class="clearfix">
                                {if $shipping.need_shipment}
                                    {if $shipping.shipment_keys}
                                        {assign var="shipment_btn" value=__("new_shipment")}
                                        {$align="left"}
                                    {else}
                                        {assign var="shipment_btn" value=__("create_detailed_shipment")}
                                        {$align="right"}
                                    {/if}
                                    <div class="clearfix">
                                    {if "shipments.add"|fn_check_view_permissions}
                                        {include_ext file="common/icon.tpl" class="icon icon-angle-right" assign=link_text_icon}
                                        {include file="common/popupbox.tpl" id="add_shipment_`$shipping.group_key`" content="" link_text="`$shipment_btn``$link_text_icon`" act="link" href=" " link_class="pull-`$align`"}
                                    {/if}
                                    </div>
                                {/if}

                                {if $is_group_shippings}<hr>{/if}

                                {if $shipping.shipment_keys}
                                    {if !$is_group_shippings}
                                        <div class="pull-right">
                                            <a href="{"shipments.manage?order_id=`$order_info.order_id`"|fn_url}">{__("shipments")}&nbsp;({$order_info.shipment_ids|count})</a>
                                        </div>
                                    {/if}
                                {/if}
                            </div>
                        {/foreach}

                        {if $is_group_shippings}
                        <div class="clearfix">
                            <a class="pull-right" href="{"shipments.manage?order_id=`$order_info.order_id`"|fn_url}">{__("shipments")}&nbsp;({$order_info.shipment_ids|count})</a>
                        </div>
                        {/if}
                    {else}

                    {foreach from=$order_info.product_groups item="group" key="group_id"}
                        {if $group.all_free_shipping}
                            <div class="clearfix">
                                {if $order_info.need_shipping}
                                    {if "shipments.add"|fn_check_view_permissions}
                                        <div class="clearfix">
                                            {include file="common/popupbox.tpl" id="add_shipment_0" content="" but_text=__("new_shipment") act="create" but_meta="btn"}
                                        </div>
                                    {/if}
                                {/if}

                                <a class="pull-right" href="{"shipments.manage?order_id=`$order_info.order_id`"|fn_url}">{__("shipments")}&nbsp;({$order_info.shipment_ids|count})</a>
                            </div>
                        {/if}
                    {/foreach}
                    {/if}
                {/hook}
            </div>
            {hook name="orders:customer_shot_info"}
            {/hook}
        </div>
    </div>
<!--content_general--></div>

<div class="{if $selected_section !== "addons"}hidden{/if}" id="content_addons">

{hook name="orders:customer_info"}
{/hook}

<!--content_addons--></div>

{if $downloads_exist}
<div class="{if $selected_section !== "downloads"}hidden{/if}" id="content_downloads">
    <input type="hidden" name="order_id" value="{$smarty.request.order_id}" />
    <input type="hidden" name="order_status" value="{$order_info.status}" />
    {foreach from=$order_info.products item="oi"}
    {if $oi.extra.is_edp == "Y"}
        {hook name="orders:download_products_list_item"}
            <div><a href="{"products.update?product_id=`$oi.product_id`"|fn_url}">{$oi.product}</a></div>
            {hook name="orders:product_info"}
            {/hook}
            {if $oi.files}
            <input type="hidden" name="files_exists[]" value="{$oi.product_id}" />
            <table cellpadding="5" cellspacing="0" border="0" class="table">
            <tr>
                <th>{__("filename")}</th>
                <th>{__("activation_mode")}</th>
                <th>{__("downloads_max_left")}</th>
                <th>{__("download_key_expiry")}</th>
                <th>{__("active")}</th>
            </tr>
            {foreach from=$oi.files item="file"}
            <tr>
                <td>{$file.file_name}</td>
                <td>
                    {if $file.activation_type == "M"}{__("manually")}</label>{elseif $file.activation_type == "I"}{__("immediately")}{else}{__("after_full_payment")}{/if}
                </td>
                <td>{if $file.max_downloads}{$file.max_downloads} / <input type="text" name="edp_downloads[{$file.ekey}][{$file.file_id}]" value="{math equation="a-b" a=$file.max_downloads b=$file.downloads|default:0}" size="3" />{else}{__("none")}{/if}</td>
                <td>
                    {if $oi.extra.unlimited_download == 'Y'}
                        {__("time_unlimited_download")}
                    {elseif $file.ekey}
                    <p><label>{__("download_key_expiry")}: </label><span>{$file.ttl|date_format:"`$settings.Appearance.date_format`, `$settings.Appearance.time_format`"|default:"n/a"}</span></p>

                    <p><label>{__("prolongate_download_key")}: </label>{include file="common/calendar.tpl" date_id="prolongate_date_`$file.file_id`" date_name="prolongate_data[`$file.ekey`]" date_val=$file.ttl|default:$smarty.const.TIME start_year=$settings.Company.company_start_year}</p>
                    {else}{__("file_doesnt_have_key")}{/if}
                </td>
                <td>
                    <select name="activate_files[{$oi.product_id}][{$file.file_id}]">
                        <option value="Y" {if $file.active == "Y"}selected="selected"{/if}>{__("active")}</option>
                        <option value="N" {if $file.active != "Y"}selected="selected"{/if}>{__("not_active")}</option>
                    </select>
                </td>
            </tr>
            {/foreach}
            </table>
            {/if}
        {/hook}
    {/if}
    {/foreach}
<!--content_downloads--></div>
{/if}

{if $order_info.promotions}
<div class="{if $selected_section !== "promotions"}hidden{/if}" id="content_promotions">
    {include file="views/orders/components/promotions.tpl" promotions=$order_info.promotions}
<!--content_promotions--></div>
{/if}

{hook name="orders:tabs_content"}
{/hook}

</form>

{hook name="orders:tabs_extra"}
{/hook}

{/capture}
{include file="common/tabsbox.tpl" content=$smarty.capture.tabsbox active_tab=$selected_section track=true}

{/capture}
{capture name="mainbox_title"}
    {__("order")} &lrm;#{$order_info.order_id} <span class="f-middle">{__("total")}: <span>{include file="common/price.tpl" value=$order_info.total}</span>{if $order_info.company_id} / {$order_info.company_id|fn_get_company_name}{/if}</span>

    <span class="f-small">
    {if $status_settings.appearance_type == "I" && $order_info.doc_ids[$status_settings.appearance_type]}
        ({__("invoice")} #{$order_info.doc_ids[$status_settings.appearance_type]})
    {elseif $status_settings.appearance_type == "C" && $order_info.doc_ids[$status_settings.appearance_type]}
        ({__("credit_memo")} #{$order_info.doc_ids[$status_settings.appearance_type]})
    {/if}
    {assign var="timestamp" value=$order_info.timestamp|date_format:"`$settings.Appearance.date_format`"|escape:url}
    / {$order_info.timestamp|date_format:"`$settings.Appearance.date_format`"},{$order_info.timestamp|date_format:"`$settings.Appearance.time_format`"}
    </span>
{/capture}

{capture name="sidebar"}
    {$sidebar_meta = "sidebar--order-details"}
    {hook name="orders:details_sidebar"}
    {include file="views/order_management/components/profiles_info.tpl" user_data=$order_info location="I" form_id="order_info_form"}
    {/hook}
{/capture}

{capture name="buttons"}
    {include file="common/view_tools.tpl" url="orders.details?order_id="}

    {$print_order = __("print_invoice")}
    {if $status_settings.appearance_type == "C" && $order_info.doc_ids[$status_settings.appearance_type]}
        {$print_order = __("print_credit_memo")}
    {elseif $status_settings.appearance_type == "O"}
        {$print_order = __("print_order_details")}
    {/if}
    {capture name="tools_list"}
        {hook name="orders:details_tools"}
            <li>{btn type="list" text=$print_order href="orders.print_invoice?order_id=`$order_info.order_id`" class="cm-new-window"}</li>
            {if $settings.Appearance.email_templates === "new"}
                <li>{btn type="list" text=__("edit_and_send_invoice") href="orders.modify_invoice?order_id=`$order_info.order_id`"}</li>
            {/if}
            <li>{btn type="list" text=__("print_packing_slip") href="orders.print_packing_slip?order_id=`$order_info.order_id`" class="cm-new-window"}</li>
            <li>{btn type="list" text=__("edit_order") href="order_management.edit?order_id=`$order_info.order_id`"}</li>
            <li>{btn type="list" text=__("copy") href="order_management.edit?order_id=`$order_info.order_id`&copy=1"}</li>
            <li>
                {btn type="list"
                    text=__("delete")
                    href="orders.delete?order_id={$order_info.order_id}&redirect_url=orders.manage"
                    class="cm-confirm"
                    method="POST"
                }
            </li>
            {$smarty.capture.adv_tools nofilter}
        {/hook}
    {/capture}
    {dropdown content=$smarty.capture.tools_list}

    <div class="btn-group btn-hover dropleft">
        {include file="buttons/save_changes.tpl" but_meta="cm-no-ajax dropdown-toggle" but_role="submit-link" but_target_form="order_info_form" but_name="dispatch[orders.update_details]" save=true}
        <ul class="dropdown-menu">
            {$notify_customer_status = false}
            {$notify_department_status = false}
            {$notify_vendor_status = false}

            {hook name="orders:notify_checkboxes"}
                <li><a><input type="checkbox" name="notify_user" {if $notify_customer_status == true} checked="checked" {/if} id="notify_user" value="Y" form="order_info_form" />
                    {__("notify_customer")}</a></li>
                <li><a><input type="checkbox" name="notify_department" {if $notify_department_status == true} checked="checked" {/if} id="notify_department" value="Y" form="order_info_form" />
                    {__("notify_orders_department")}</a></li>
                {if "MULTIVENDOR"|fn_allowed_for}
                <li>
                    <a><input type="checkbox" name="notify_vendor" {if $notify_vendor_status == true} checked="checked" {/if} id="notify_vendor" value="Y" form="order_info_form" />
                        {__("notify_vendor")}</a>
                </li>
                {/if}
            {/hook}
        </ul>
    </div>
{/capture}

{include file="common/mainbox.tpl" title=$smarty.capture.mainbox_title content=$smarty.capture.mainbox buttons=$smarty.capture.buttons adv_buttons=$smarty.capture.adv_buttons sidebar=$smarty.capture.sidebar sidebar_position="left" sidebar_icon="icon-user"}

{hook name="orders:detailed_after_content"}
{/hook}
