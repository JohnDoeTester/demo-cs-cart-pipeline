<div id="sw_shippings_extra">
    <div class="sw-right-block">
        {include file="buttons/button.tpl" but_href="shippings.manage" but_text=__("sw.goto_shipping_methods") but_role="action" but_target="_blank"}
    </div>

    {foreach $shippings as $shipping}
        <div id="shippings_form_{$shipping.shipping_id}">
            <form name="shippings_form_{$shipping.shipping_id}" class="form-horizontal cm-ajax cm-ajax-force" action="{""|fn_url}" method="post">
                <input type="hidden" name="dispatch" value="setup_wizard.update_shippings" />
                <input type="hidden" name="result_ids" value="shippings_form_{$shipping.shipping_id}" />
                <input type="hidden" name="shipping_id" value="{$shipping.shipping_id}" />
                <input type="hidden" name="shipping_data[status]" value="{if $shipping.status == "ObjectStatuses::ACTIVE"|enum}D{else}A{/if}" />

                <div class="sw-columns-block">

                    <div class="control-group">
                        <div class="control-icon sw_{if stripos($shipping.shipping, "usps") !== false}usps{elseif stripos($shipping.shipping, "ups") !== false}ups{elseif stripos($shipping.shipping, "fedex") !== false}fedex{elseif stripos($shipping.shipping, "ems") !== false}ems_russian_post{else}spd{/if}"></div>
                        <label class="control-label">{$shipping.shipping}</label>
                        <div class="controls">
                            <div class="pull-right">
                                <button class="btn {if $shipping.status == "ObjectStatuses::ACTIVE"|enum}btn-disable{else}btn-primary{/if} ladda-button" type="submit" data-style="slide-right">
                                    <span class="ladda-label">{if $shipping.status == "ObjectStatuses::ACTIVE"|enum}{__("sw.disable")}{else}{__("sw.enable")}{/if}</span>
                                </button>
                                <a href="{"shippings.update?shipping_id=`$shipping.shipping_id`"|fn_url}" class="btn btn-disable">{__("sw.configure")}</a>
                            </div>
                        </div>
                    </div>
                </div>
            </form>
        <!--shippings_form_{$shipping.shipping_id}--></div>
    {/foreach}
<!--sw_shippings_extra--></div>
